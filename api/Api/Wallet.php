<?php


namespace Api\Wallet;

use Api\Errors;
use Core\App;
use Core\Exceptions\Exchange\InsufficientFundsException;
use Core\Exceptions\Wallet\Withdrawal\WithdrawalDisabledException;
use Core\Exceptions\Wallet\Withdrawal\WithdrawalFloodException;
use Core\Exceptions\Wallet\Withdrawal\WithdrawalIncorrectLoginException;
use Core\Exceptions\Wallet\Withdrawal\WithdrawalIncorrectWalletException;
use Core\Exceptions\Wallet\Withdrawal\WithdrawalInsufficientFundsException;
use Core\Exceptions\Wallet\Withdrawal\WithdrawalMinAmountException;
use Core\Response\JsonResponse;
use Core\Services\BalanceHistory\BalanceHistoryGetter;
use Db\Where;
use Exceptions\BuyTokenExceptions\InvalidPromoCodeException;
use Models\TransactionModel;
use Models\TransferModel;
use Models\UserBalanceHistoryModel;
use Models\UserModel;
use Models\WalletModel;
use Models\WithdrawalRequest;
use Modules\InvestmentModule;
use Modules\UserModule;
use Modules\WalletModule;
use Serializers\BalanceHistory\HistorySerializer;
use Serializers\BalanceHistory\SwapSerializer;
use Serializers\BalanceHistory\TransactionSerializer;
use Serializers\BalanceHistory\TransferSerializer;
use Serializers\ErrorSerializer;
use Serializers\PagingSerializer;
use Serializers\WalletSerializer;

class Wallet {
    public static function retrieve($request) {
        /* @var int $count */
        extract($request['params']);
        $user = getUser($request);

        $transactions = (new BalanceHistoryGetter())
            ->setOperations([UserBalanceHistoryModel::OPERATION_TRANSACTION, UserBalanceHistoryModel::OPERATION_WITHDRAWAL_REQUEST])
            ->setUsersIds([$user->id])
            ->paginate(0, (int) $count);

        $transfers = (new BalanceHistoryGetter())
            ->setOperations([UserBalanceHistoryModel::OPERATION_TRANSFER])
            ->setUsersIds([$user->id])
            ->paginate(0, (int) $count);

        $balances = WalletModule::getWallets($user->id)
            ->map('Serializers\WalletSerializer::listItem');

        $transactions_items = HistorySerializer::serializeItems($transactions->getItems(), $user);
        $transfers_items = HistorySerializer::serializeItems($transfers->getItems(), $user);

        JsonResponse::ok([
            'balances' => $balances,
            'transactions' => PagingSerializer::detail($transactions->getNext(), $transactions_items),
            'transfers' => PagingSerializer::detail($transfers->getNext(), $transfers_items),
        ]);
    }

    public static function wallet($request) {
        /* @var int $id */
        extract($request['params']);

        $user = getUser($request);

        /** @var WalletModel|null $wallet */
        $wallet = WalletModel::first(Where::and()
            ->set('id', Where::OperatorEq, $id)
            ->set('user_id', Where::OperatorEq, $user->id)
        );

        if (!$wallet) {
            JsonResponse::errorMessage('wallet_not_found');
        }

        JsonResponse::ok(WalletSerializer::listItem($wallet));
    }

    public static function transactionRetrieveList($request) {
        /* @var string $start_from
         * @var int $count
        */
        extract($request['params']);

        $user = getUser($request);

        $transactions = (new BalanceHistoryGetter())
            ->setOperations([UserBalanceHistoryModel::OPERATION_TRANSACTION, UserBalanceHistoryModel::OPERATION_WITHDRAWAL_REQUEST])
            ->setUsersIds([$user->id])
            ->paginate((int) $start_from, (int) $count);

        $transactions_items = HistorySerializer::serializeItems($transactions->getItems(), $user);
        JsonResponse::ok(PagingSerializer::detail($transactions->getNext(), $transactions_items));
    }

    public static function transferRetrieveList($request) {
        /**
         * @var string $start_from
         * @var int $count
         * @var string $currency
         * @var string $wallet_id
         * @var string $order_by
         */
        extract($request['params']);

        $user = getUser($request);

        $transfers = (new BalanceHistoryGetter())
            ->setOperations([UserBalanceHistoryModel::OPERATION_TRANSFER])
            ->setUsersIds([$user->id])
            ->paginate($start_from, $count);

        $transfers_items = HistorySerializer::serializeItems($transfers->getItems(), $user);
        JsonResponse::ok(PagingSerializer::detail($transfers->getNext(), $transfers_items));
    }

    public static function transactionRetrieve($request) {
        /* @var int $id */
        extract($request['params']);
        $user = getUser($request);

        if ($id < 0) {
            /** @var WithdrawalRequest $withdrawal_request */
            $withdrawal_request = WithdrawalRequest::first(Where::and()
                ->set('id', Where::OperatorEq, -$id)
                ->set('user_id', Where::OperatorEq, $user->id)
            );

            if (!$withdrawal_request) {
                JsonResponse::errorMessage('transaction_not_found');
            }

            $transaction = TransactionSerializer::withdrawalRequestListItem($withdrawal_request);
        } else {
            /** @var TransactionModel $transaction */
            $transaction = TransactionModel::first(Where::and()
                ->set('id', Where::OperatorEq, $id)
                ->set('user_id', Where::OperatorEq, $user->id)
            );

            if (!$transaction) {
                JsonResponse::errorMessage('transaction_not_found');
            }

            $transaction = TransactionSerializer::serialize($transaction);
        }

        JsonResponse::ok($transaction);
    }

    public static function transferRetrieve($request) {
        /* @var int $id */
        extract($request['params']);
        $user = getUser($request);

        /** @var TransferModel $transfer */
        $transfer = TransferModel::first(
                Where::and()
                    ->set('id', Where::OperatorEq, $id)
                    ->set(Where::or()
                        ->set(Where::equal('from_user_id', $user->id))
                        ->set(Where::equal('to_user_id', $user->id))
                    )
        );

        if (!$transfer) {
            JsonResponse::errorMessage('transfer_not_found');
        }

        /** @var UserModel $second_user */
        $second_user = $transfer->from_user_id === $user->id ?
            UserModel::get($transfer->to_user_id) :
            UserModel::get($transfer->from_user_id);

        $transfer->withUser($second_user);
        $transfer = TransferSerializer::serializeWithUser($transfer, $user);

        JsonResponse::ok($transfer);
    }

    public static function sendCoinsRetrieve($request) {

        $user = getUser($request);

        if (UserModule::isWithdrawDisabled($user)) {
            JsonResponse::apiError(Errors::WITHDRAW_DISABLED);
        }

        JsonResponse::ok([
            'limits' => KERNEL_CONFIG['wallet']['withdraw_limits'],
        ]);
    }

    public static function currencies($request) {
        $user = getUser($request);
        $userCurrencies = [];
        if ($user) {
            $userCurrencies = WalletModule::getWallets($user->id)->column('currency');
        }

        JsonResponse::ok(WalletModel::availableCurrencies($userCurrencies, true));
    }

    public static function generateAddress($request) {
        /**
         * @var string $currency    Currency for generation
         */
        extract($request['params']);

        $user = getUser($request);
        [$status, $result] = WalletModule::generateAddress($user->id, $currency);

        if ($status === 'ok') {
            JsonResponse::ok(WalletSerializer::listItem($result));
        } else {
            switch ($result) {
                case 'unknown_currency':
                    $error_messages = 'Unknown currency';
                    break;
                case 'address_already_generated':
                    $error_messages = 'Wallet already generated';
                    break;
                default:
                    $error_messages = 'Error occurred';
            }

            JsonResponse::error(ErrorSerializer::detail($result, $error_messages));
        }
    }

    public static function sendTransfer($request) {
        /**
         * @var string $login     User login
         * @var string $wallet_id   From wallet
         * @var string $amount      Amount to send
         */
        extract($request['params']);
        $user = getUser($request);
        self::checkIfUserWithdrawalDisabled($user);
        try {
            /** @var WalletModel $wallet */
            $wallet = WalletModel::first(
                Where::and()
                    ->set(Where::equal('user_id', $user->id))
                    ->set(Where::equal('id', $wallet_id))
            );

            if (!$wallet) {
                JsonResponse::errorMessage('wallet_not_found');
            }

            [$transfer_model, $wallet] = WalletModule::transferSend($user, $login, $wallet_id, $amount);

            JsonResponse::ok([
                'wallet' => WalletSerializer::listItem($wallet),
                'transfer' => TransferSerializer::serializeWithUser($transfer_model, $user)
            ]);
        } catch (WithdrawalFloodException $e) {
            JsonResponse::floodControlError(Errors::FLOOD);
        } catch (WithdrawalIncorrectLoginException $e) {
            JsonResponse::errorMessage('address_incorrect', Errors::ADDRESS_INCORRECT);
        } catch (WithdrawalIncorrectWalletException $e) {
            JsonResponse::errorMessage('address_incorrect', Errors::ADDRESS_INCORRECT);
        } catch (WithdrawalInsufficientFundsException $e) {
            JsonResponse::errorMessage('insufficient_funds', Errors::INSUFFICIENT_FUNDS);
        } catch (\Exception $e) {
            JsonResponse::apiError();
        }
    }

    public static function sendTransaction($request) {
        /**
         * @var string $address     Wallet address
         * @var string $wallet_id   From wallet
         * @var string $amount      Amount to send
         */
        extract($request['params']);
        $user = getUser($request);
        self::checkIfUserWithdrawalDisabled($user);
        $wallet = WalletModule::getWalletByUserAndId($user->id, $wallet_id);
        if (!$wallet) {
            JsonResponse::errorMessage('wallet_not_found');
        }
        try {
            [$transaction, $wallet] = WalletModule::transactionSend($user, $address, $wallet, $amount);
            JsonResponse::ok([
                'wallet' => WalletSerializer::listItem($wallet),
                'transaction' => TransactionSerializer::withdrawalRequestListItem($transaction)
            ]);
        } catch (WithdrawalDisabledException $e) {
            JsonResponse::errorMessage('withdrawal_disabled', Errors::WITHDRAW_DISABLED);
        } catch (WithdrawalFloodException $e) {
            JsonResponse::floodControlError(Errors::FLOOD);
        } catch (WithdrawalIncorrectWalletException $e) {
            JsonResponse::errorMessage('address_incorrect', Errors::ADDRESS_INCORRECT);
        } catch (WithdrawalInsufficientFundsException $e) {
            JsonResponse::errorMessage('insufficient_funds', Errors::INSUFFICIENT_FUNDS);
        } catch (WithdrawalMinAmountException $e) {
            JsonResponse::errorMessage('incorrect_min_amount', Errors::WITHDRAW_MIN_AMOUNT);
        } catch (\Exception $e) {
            if (App::isDevelopment()) {
                JsonResponse::errorMessage($e->getMessage(), Errors::FATAL, false);
            }
            JsonResponse::apiError();
        }
    }

    /**
     * Determine if user has no access to do withdrawal
     * @param $user
     */
    public static function checkIfUserWithdrawalDisabled($user) {
        if (UserModule::isWithdrawDisabled($user)) {
            JsonResponse::apiError(Errors::WITHDRAW_DISABLED);
        }
    }

    public static function getTokenRate($request) {
        /**
         * @var string $currency
         */
        extract($request['params']);

        JsonResponse::ok([
            'rate' => number_format(WalletModule::getTokenRate($currency), 10, '.', ''),
        ]);
    }

    public static function buyToken($request) {
        /**
         * @var string $currency
         * @var float $amount
         * @var string|null $promo_code
         */
        extract($request['params']);

        $user = getUser($request);
        self::checkIfUserWithdrawalDisabled($user);

        try {
            $ret = WalletModule::buyToken($user->id, $currency, $amount, $promo_code);

            if ($agent_id = $user->getInviterId()) {
                /** @var UserModel $agent */
                $agent = UserModel::get($agent_id);
                InvestmentModule::addTokenProfit($agent, $ret['swap']->to_amount, $user->id);
            }

            JsonResponse::ok([
                'history' =>  SwapSerializer::serialize($ret['swap']),
                'from_wallet' => WalletSerializer::listItem($ret['from_wallet']),
                'to_wallet' => WalletSerializer::listItem($ret['to_wallet']),
            ]);
        } catch (InvalidPromoCodeException $e) {
            JsonResponse::errorMessage($e->getMessage(), Errors::INVALID_PROMO_CODE);
        }catch (InsufficientFundsException $e) {
            JsonResponse::errorMessage($e->getMessage(), Errors::INSUFFICIENT_FUNDS);
        } catch (\Exception $e) {
            JsonResponse::errorMessage($e->getMessage());
        }
    }

    public static function tokenSoldAmountRetrieve() {
        JsonResponse::ok([
            'amount' => (double) settings()->token_sold_amount,
        ]);
    }

    public static function enabledSaving($request) {
        /* @var int $id */
        extract($request['params']);

        $user = getUser($request);

        $wallet = WalletModule::getWalletByUserAndId($user->id, $id);
        if (!$wallet) {
            JsonResponse::errorMessage('wallet_not_found');
        }

        if ($wallet->currency !== CURRENCY_FNDR) {
            JsonResponse::errorMessage('api_error');
        }

        $wallet->saving_enabled = true;
        $wallet->save();

        JsonResponse::ok();
    }
}
