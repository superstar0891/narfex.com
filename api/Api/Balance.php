<?php

namespace Api\Balance;

use Api\Errors;
use Core\Response\JsonResponse;
use Core\Services\BalanceHistory\BalanceHistoryGetter;
use Db\Transaction;
use Db\Where;
use Engine\Request;
use Models\BalanceModel;
use Models\InternalTransactionModel;
use Models\UserBalanceHistoryModel;
use Models\WalletModel;
use Modules\BalanceModule;
use Modules\FiatWalletModule;
use Modules\UserModule;
use Modules\WalletModule;
use Serializers\BalanceHistory\HistorySerializer;
use Serializers\BalanceHistory\TransactionSerializer;
use Serializers\BalanceSerializers;
use Serializers\ErrorSerializer;

class Balance {
    public static function getBalance($request) {
        /**
         * @var int $id
         */
        extract($request['params']);
        $user = Request::getUser();

        $balance = BalanceModule::getBalance($id, $user->id);
        if (!$balance) {
            JsonResponse::errorMessage('balance_not_found');
        }

        JsonResponse::ok(BalanceSerializers::listItem($balance));
    }

    public static function retrieve($request) {
        /* @var string $category */
        extract($request['params']);

        $user = getUser($request);

        if (UserModule::isWithdrawDisabled($user)) {
            JsonResponse::apiError(Errors::WITHDRAW_DISABLED);
        }

        $balances = BalanceModule::getBalances($user->id, $category);
        $wallets = WalletModule::getWallets($user->id);

        $avail_currencies = [];
        /* @var WalletModel $wallet */
        foreach ($wallets as $wallet) {
            $avail_currencies[$wallet->currency] = true;
        }

        $balances = $balances->filter(function ($balance) use ($avail_currencies) {
            /* @var BalanceModel $balance */
            return isset($avail_currencies[$balance->currency]);
        })->map('Serializers\BalanceSerializer::listItem');

        $wallets = $wallets
            ->filter(function (WalletModel $wallet) use ($category) {
                return $wallet->currency !== 'nrfx' || $category === BalanceModel::CATEGORY_EXCHANGE;
            })
            ->map('Serializers\WalletSerializer::listItem');

        JsonResponse::ok(compact('balances', 'wallets'));
    }

    public static function balance($request) {
        /* @var int $id */
        extract($request['params']);

        $user = getUser($request);

        /** @var BalanceModel|null $balance */
        $balance = BalanceModel::first(Where::and()
            ->set('id', Where::OperatorEq, $id)
            ->set('user_id', Where::OperatorEq, $user->id)
        );

        if (!$balance) {
            JsonResponse::errorMessage('not_found');
        }

        JsonResponse::ok(BalanceSerializers::item($balance));
    }

    public static function withdraw($request) {
        /* @var int $balance_id
         * @var double $amount
         */
        extract($request['params']);

        $user = getUser($request);

        if (UserModule::isWithdrawDisabled($user)) {
            JsonResponse::apiError(Errors::WITHDRAW_DISABLED);
        }

        /* @var \Models\BalanceModel $balance */
        $balance = BalanceModel::first(
            Where::and()
                ->set(Where::equal('user_id', $user->id))
                ->set(Where::equal('id', $balance_id))
                ->set(
                    Where::or()
                        ->set(Where::equal('category', BalanceModel::CATEGORY_EXCHANGE))
                        ->set(Where::equal('category', BalanceModel::CATEGORY_PARTNERS))
                )
        );

        if (!$balance) {
            JsonResponse::errorMessage('balance_not_found');
        }

        if ($balance->currency === CURRENCY_FNDR && $balance->category !== BalanceModel::CATEGORY_EXCHANGE) {
            JsonResponse::apiError();
        }

        if ($balance->amount < $amount) {
            JsonResponse::errorMessage('insufficient_funds', Errors::INSUFFICIENT_FUNDS);
        }

        /* @var WalletModel $wallet */
        $wallet = WalletModule::getWallet($user->id, $balance->currency);

        if (!$wallet) {
            JsonResponse::errorMessage('wallet_not_found', Errors::AMOUNT_INCORRECT);
        }

        if (!floodControl('balance_withdraw_' . $user->id, KERNEL_CONFIG['flood_control']['balance_withdraw'])) {
            JsonResponse::floodControlError();
        }

        $transaction = Transaction::wrap(function () use ($balance, $amount, $wallet, $user) {
            if (!$balance->checkAmount($amount)) {
                throw new \Exception();
            }

            if (!$balance->decrAmount($amount)) {
                throw new \Exception();
            }

            if (!$wallet->addAmount($amount)) {
                throw new \Exception();
            }

            $from_category_type = $balance->category === BalanceModel::CATEGORY_EXCHANGE
                ? InternalTransactionModel::CATEGORY_EXCHANGE
                : InternalTransactionModel::CATEGORY_PARTNERS;
            $transaction = new InternalTransactionModel();
            $transaction->currency = $balance->currency;
            $transaction->amount = $amount;
            $transaction->from_category = $from_category_type;
            $transaction->to_category = InternalTransactionModel::CATEGORY_WALLET;
            $transaction->setFrom($balance);
            $transaction->setTo($wallet);
            $transaction->save();

            return $transaction;
        });

        $history_getter = new BalanceHistoryGetter;
        $history_getter->setUsersIds([$user->id]);
        $history_getter->setOperations([UserBalanceHistoryModel::OPERATION_INTERNAL_TRANSACTION]);
        $history_getter->setObjectIds([$transaction->id]);
        $serialized_history = HistorySerializer::serializeItems($history_getter->get(), $user);

        JsonResponse::ok(
            [
                'balance' => BalanceSerializers::listItem($balance),
                'transaction' => $serialized_history[0],
            ]
        );
    }

    public static function deposit($request) {
        /* @var int $wallet_id
         * @var double $amount
         */
        extract($request['params']);

        $user = getUser($request);
        if (UserModule::isWithdrawDisabled($user)) {
            JsonResponse::apiError(Errors::WITHDRAW_DISABLED);
        }

        $wallet = WalletModel::select(Where::and()
            ->set('id', Where::OperatorEq, $wallet_id)
            ->set('user_id', Where::OperatorEq, $user->id)
        );

        if ($wallet->isEmpty()) {
            JsonResponse::errorMessage('access_denied');
        }

        $wallet = $wallet->first();
        /* @var WalletModel $wallet */

        if ($wallet->amount < $amount) {
            JsonResponse::errorMessage('insufficient_funds', Errors::INSUFFICIENT_FUNDS);
        }

        if (!floodControl('balance_deposit_' . $user->id, KERNEL_CONFIG['flood_control']['balance_deposit'])) {
            JsonResponse::floodControlError();
        }

        $balance = BalanceModule::getBalanceOrCreate($user->id, $wallet->currency, BalanceModel::CATEGORY_EXCHANGE);

        Transaction::wrap(function () use ($balance, $amount, $wallet, $user) {
            if (!$wallet->checkAmount($amount)) {
                throw new \Exception();
            }

            if (!$wallet->subAmount($amount)) {
                throw new \Exception();
            }

            if (!$balance->incrAmount($amount)) {
                throw new \Exception();
            }

            $to_category_type = $balance->category === BalanceModel::CATEGORY_EXCHANGE
                ? InternalTransactionModel::CATEGORY_EXCHANGE
                : InternalTransactionModel::CATEGORY_PARTNERS;
            $transaction = new InternalTransactionModel();
            $transaction->currency = $balance->currency;
            $transaction->amount = $amount;
            $transaction->from_category = InternalTransactionModel::CATEGORY_WALLET;
            $transaction->to_category = $to_category_type;
            $transaction->setFrom($wallet);
            $transaction->setTo($balance);
            $transaction->save();
        });

        JsonResponse::ok(BalanceSerializers::listItem($balance));
    }

    public static function getBalances($request) {
        $user = Request::getUser();
        $fiat_balances = BalanceModule::getBalances($user->id, BalanceModel::CATEGORY_FIAT);
        $crypto_wallets = WalletModule::getWallets($user->id);
        $exchange_balances = BalanceModule::getBalances($user->id, BalanceModel::CATEGORY_EXCHANGE);

        $response = [
            'fiat' => [],
            'crypto' => [],
            'exchange' => [],
            'usd_amount' => 0,
            'btc_amount' => 0
        ];

        foreach ($fiat_balances as $fiat_balance) {
            /** @var BalanceModel $fiat_balance */

            $usd_amount = FiatWalletModule::getAmountInAnotherCurrency($fiat_balance->currency, 'usd', $fiat_balance->amount);
            $response['fiat'][$fiat_balance->currency] = [
                'amount' => $fiat_balance->amount,
                'usd_amount' => $usd_amount
            ];
            $response['usd_amount'] += $usd_amount;
        }

        foreach ($exchange_balances as $exchange_balance) {
            /** @var BalanceModel $exchange_balance */

            $usd_amount = FiatWalletModule::getAmountInAnotherCurrency($exchange_balance->currency, 'usd', $exchange_balance->amount);
            $response['exchange'][$exchange_balance->currency] = [
                'amount' => $exchange_balance->amount,
                'usd_amount' => $usd_amount
            ];
            $response['usd_amount'] += $usd_amount;
        }

        foreach ($crypto_wallets as $wallet) {
            /** @var WalletModel $wallet */
            $usd_amount = FiatWalletModule::getAmountInAnotherCurrency($wallet->currency, 'usd', $wallet->amount);
            $response['crypto'][$wallet->currency] = [
                'amount' => $wallet->amount,
                'usd_amount' => $usd_amount
            ];
            $response['usd_amount'] += $usd_amount;
        }

        $btc_amount = FiatWalletModule::getAmountInAnotherCurrency('usd', 'btc', $response['usd_amount']);
        $response['btc_amount'] = $btc_amount;

        JsonResponse::ok($response);
    }
}
