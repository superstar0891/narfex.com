<?php

namespace Modules;

use Core\App;
use Core\Exceptions\Exchange\InsufficientFundsException;
use Core\Exceptions\Wallet\Withdrawal\WithdrawalDisabledException;
use Core\Exceptions\Wallet\Withdrawal\WithdrawalFloodException;
use Core\Exceptions\Wallet\Withdrawal\WithdrawalIncorrectLoginException;
use Core\Exceptions\Wallet\Withdrawal\WithdrawalIncorrectWalletException;
use Core\Exceptions\Wallet\Withdrawal\WithdrawalInsufficientFundsException;
use Core\Exceptions\Wallet\Withdrawal\WithdrawalMinAmountException;
use Core\Services\BalanceHistory\BalanceHistorySaver;
use Core\Services\Mail\MailAdapter;
use Core\Services\Mail\Templates;
use Core\Services\Promo\CodeGeneratorService;
use Core\Services\Telegram\SendService;
use Db\Model\Exception\ModelNotFoundException;
use Db\Model\ModelSet;
use Db\Transaction;
use Db\Transaction as DbTransaction;
use Db\Where;
use Exception;
use Exceptions\WithdrawalRequests\WalletNotFoundException;
use Exceptions\BuyTokenExceptions\InvalidPromoCodeException;
use Models\AddressModel;
use Models\BalanceModel;
use Models\ProfitModel;
use Models\SettingsModel;
use Models\SwapModel;
use Models\TransactionModel;
use Models\TransferModel;
use Models\UserBalanceHistoryModel;
use Models\UserModel;
use Models\WalletModel;
use Models\WithdrawalRequest;

class WalletModule {
    public static function getWallets(int $user_id): ModelSet {
        $builder = WalletModel::queryBuilder()
            ->columns([])
            ->orderBy(["FIELD(currency, '" . CURRENCY_FNDR . "', 'btc', 'eth', 'ltc')" => 'ASC'])
            ->where(Where::equal('user_id', $user_id))
            ->select();

        $wallets = WalletModel::rowsToSet($builder);

        $user = UserModel::get($user_id);
        if (!UserModule::isWithdrawDisabled($user)) {
            $is_token_wallets_exist = false;
            /* @var WalletModel $wallet */
            foreach ($wallets as $wallet) {
                if ($wallet->currency === CURRENCY_FNDR) {
                    $is_token_wallets_exist = true;
                    break;
                }
            }

            // Init token wallets if not exist
            if (!$is_token_wallets_exist) {
                $wallets->push(self::generateTokenWallet($user_id));
            }
        }

        return $wallets;
    }

    public static function withdrawalRequests($user_id): ModelSet {
        $builder = WithdrawalRequest::queryBuilder()
            ->columns([])
            ->where(Where::and()
                ->set('user_id', Where::OperatorEq, $user_id)
                ->set('status', Where::OperatorIN, [
                    WithdrawalRequest::STATUS_PENDING,
                    WithdrawalRequest::STATUS_PAUSED,
                    WithdrawalRequest::STATUS_BOOST,
                ])
            )
            ->orderBy(['id' => 'DESC'])
            ->select();
        return WithdrawalRequest::rowsToSet($builder);
    }

    public static function sortByDate(array $transactions) {
        usort($transactions, function ($item1, $item2) {
            return $item2['created_at'] <=> $item1['created_at'];
        });
        return $transactions;
    }

    public static function transactions($user_id, $count, $start_from = null) {
        $where = Where::and()->set('user_id', Where::OperatorEq, $user_id);
        if ($start_from) {
            $where->set('created_at', Where::OperatorLower, $start_from);
        }

        $builder = TransactionModel::queryBuilder()
            ->where($where)
            ->orderBy(['id' => 'DESC'])
            ->limit($count)
            ->select();

        $transactions = TransactionModel::rowsToSet($builder);

        return [
            $transactions->map('Serializers\BalanceHistory\TransactionSerializer::serialize'),
            $transactions->count() == $count ? $transactions->last()->created_at : null
        ];
    }

    public static function transferSend($user, $to_login, $wallet_id, $amount): array {
        if (!floodControl('transfer_' . $user->id, KERNEL_CONFIG['flood_control']['transfer'])) {
            throw new WithdrawalFloodException('Too many attempts');
        }

        /** @var UserModel $to_user */
        $to_user = UserModel::first(Where::equal('login', $to_login));
        if (!$to_user) {
            throw new WithdrawalIncorrectLoginException('User not found');
        }

        $from_wallet = WalletModel::select(Where::and()
            ->set('user_id', Where::OperatorEq, $user->id)
            ->set('id', Where::OperatorEq, $wallet_id)
        );
        if ($from_wallet->isEmpty()) {
            throw new WithdrawalIncorrectWalletException('Wallet "from" not found');
        }
        $from_wallet = $from_wallet->first();
        /* @var WalletModel $from_wallet */

        $to_wallet = WalletModel::select(Where::and()
            ->set('user_id', Where::OperatorEq, $to_user->id)
            ->set('currency', Where::OperatorEq, $from_wallet->currency)
        );

        if ($to_wallet->isEmpty()) {
            throw new WithdrawalIncorrectWalletException('Wallet "to" not found');
        }

        /* @var WalletModel $to_wallet */
        $to_wallet = $to_wallet->first();

        if ($from_wallet->amount < $amount) {
            throw new WithdrawalInsufficientFundsException('The amount is too much for this wallet');
        }

        return DbTransaction::wrap(function () use ($user, $to_user, $from_wallet, $to_wallet, $amount) {
            if (!$from_wallet->checkAmount($amount)) {
                throw new Exception();
            }

            $t = new TransferModel();
            $t->withUser($to_user);
            $t->from_user_id = $user->id;
            $t->to_user_id = $to_user->id;
            $t->from_wallet_id = $from_wallet->id;
            $t->to_wallet_id = $to_wallet->id;
            $t->currency = $from_wallet->currency;
            $t->amount = $amount;
            $t->created_at = date('Y-m-d H:i:s');
            $t->save();

            NotificationsModule::sendTransferNotification($t);

            if (!$from_wallet->subAmount($amount)) {
                throw new Exception();
            }

            if (!$to_wallet->addAmount($amount)) {
                throw new Exception();
            }
            return [$t, $from_wallet];
        });
    }

    public static function transactionSend(UserModel $user, $address, WalletModel $wallet, $amount): array {
        if (!floodControl('transaction_' . $user->id, KERNEL_CONFIG['flood_control']['transaction'])) {
            throw new WithdrawalFloodException();
        }

        if (!settings()->wallet_withdraw_enabled) {
            throw new WithdrawalDisabledException('Withdrawals are not allowed now');
        }

        if (strlen($address) < 15) {
            throw new WithdrawalIncorrectWalletException('The address is too short');
        }

        $from_wallet = $wallet;

        $limits = KERNEL_CONFIG['wallet']['withdraw_limits'][$from_wallet->currency];
        if ($amount < $limits['min']) {
            throw new WithdrawalMinAmountException();
        }

        $fee = $limits['fee'];

         if ($from_wallet->amount < $amount + $fee) {
             throw new WithdrawalInsufficientFundsException('The amount is too big');
         }

        [$transaction, $from_wallet] = DbTransaction::wrap(function () use ($user, $address, $from_wallet, $amount, $fee) {
            if (!$from_wallet->checkAmount($amount + $fee)) {
                throw new Exception();
            }

            $r = new WithdrawalRequest();
            $r->amount = $amount;
            $r->to_address = $address;
            $r->user_address = $from_wallet->address;
            $r->user_id = $user->id;
            $r->wallet_id = $from_wallet->id;
            $r->status = 'pending';
            $r->currency = $from_wallet->currency;
            $r->exec_at = time() + settings()->wallet_withdraw_delay * 60;
            $r->created_at = time();
            $r->updated_at = time();
            $r->save();

            if (!$from_wallet->subAmount($amount + $fee)) {
                throw new Exception();
            }

            StatsModule::profit('wallet_withdraw_fee', $fee, $from_wallet->currency, $user->id);
            return [$r, $from_wallet];
        });

        if (settings()->wallet_withdraw_email_notif) {
            $message = <<<HTML
User ID: {user_id}<br/>
Amount: {amount} {currency}<br/>
Delayed for {delay} minutes<br/>
Date: {date}
HTML;
            $message = str_replace([
                '{user_id}',
                '{amount}',
                '{currency}',
                '{delay}',
                '{date}',
            ], [
                $user->id,
                number_format($amount, 6, '.', ''),
                strtoupper($from_wallet->currency),
                settings()->wallet_withdraw_delay,
                date('d/m/Y H:i'),
            ], $message);

            foreach (explode(',', settings()->wallet_withdraw_emails) as $to) {
                MailAdapter::send(trim($to), 'New withdrawal', Templates::SIMPLE, [
                    'caption' => $message,
                ]);
            }
        }

        self::telegramNotify($transaction, $user);

        return [$transaction, $from_wallet];
    }

    public static function telegramNotify(WithdrawalRequest $transaction, UserModel $user): void {
        $telegram_service = new SendService();
        $message = '#crypto_transaction_send' .
            PHP_EOL . sprintf('ID: %s, %s(%s), Date: %s, %s',
                $transaction->id,
                $user->login,
                $user->id,
                (new \DateTime())->setTimestamp($transaction->created_at_timestamp)->format('d.m.Y H:i:s'),
                formatNum($transaction->amount, 8) . ' ' . strtoupper($transaction->currency)
            );
        $telegram_service->sendMessageSafety($message);
    }

    public static function generateAddress($user_id, $currency, bool $is_development = false): array {
        $currencies = WalletModel::availableCurrencies([], false);
        if (!isset($currencies[$currency])) {
            return ['error', 'unknown_currency'];
        }

        $wallet_exist = WalletModel::select(Where::and()
            ->set('user_id', Where::OperatorEq, $user_id)
            ->set('currency', Where::OperatorEq, $currency)
        );

        if (!$wallet_exist->isEmpty()) {
            return ['error', 'address_already_generated'];
        }

        $address_builder = AddressModel::queryBuilder()
            ->columns([])
            ->limit(1)
            ->where(Where::and()
                ->set('currency', Where::OperatorEq, $currency)
                ->set('user_id', Where::OperatorEq, -1)
            )
            ->select();
        $address = AddressModel::rowsToSet($address_builder);

        if ($address->isEmpty() && !$is_development) {
            return ['error', 'address error'];
        }

        if (!App::isTestEnvironment()) {
            $address = $address->first();
            /* @var \Models\AddressModel $address */
        }

        $wallet = new WalletModel();
        $wallet->user_id = $user_id;
        $wallet->address = $is_development ? bin2hex(openssl_random_pseudo_bytes(64)) : $address->address;
        $wallet->currency = $currency;
        $wallet->amount = 0;
        $wallet->status = 'generated';

        $address->user_id = $user_id;

        Transaction::wrap(function () use ($address, $wallet) {
            $wallet->save();
            if (!App::isTestEnvironment()) {
                $address->save();
            }
        });

        return ['ok', $wallet];
    }

    public static function generateWallets($user_id, bool $is_development = false) {
        foreach (['btc', 'eth', 'ltc'] as $currency) {
            self::generateAddress($user_id, $currency, $is_development);
        }
    }

    public static function getUsdPrice($currency): float {
        static $cache = [];

        if ($currency === CURRENCY_FNDR) {
            return settings()->token_price;
        }

        if ($currency === null) {
            return 1;
        }

        $currency = strtolower($currency);
        if ($currency === 'xbt') {
            $currency = 'btc';
        }

        if (strtolower($currency) == 'usd' || strtolower($currency) == 'usdt') {
            return 1;
        }

        if (isset($cache[$currency])) {
            return $cache[$currency];
        }

        $key_base_to_usd = strtolower($currency) . '_usd';
        $key_base_to_btc = strtolower($currency) . '_btc';
        $key_btc_to_usd = 'btc_usd';

        $keys = WalletModel::$symbol_rate_keys_stage;

        if (array_key_exists($key_base_to_usd, $keys)) {
            return $cache[$currency] = WalletModel::getRateFromCache($key_base_to_usd);
        } elseif (array_key_exists($key_base_to_btc, $keys)) {
            $base_to_btc_rate = WalletModel::getRateFromCache($key_base_to_btc);
            $btc_to_usd_rate = WalletModel::getRateFromCache($key_btc_to_usd);
            return $cache[$currency] = $base_to_btc_rate * $btc_to_usd_rate;
        } else if ($keys['btc_' . $currency]) {
            $rate1 = WalletModel::getRateFromCache('btc_usd');
            $rate2 = WalletModel::getRateFromCache('btc_' . $currency);

            if ($rate2 == 0) {
                return 0;
            }

            return $cache[$currency] = $rate1 / $rate2;
        }
    }

    public static function createTransaction(
        string $category,
        string $currency,
        float $amount,
        array $options = []): TransactionModel {
        $options += [
            'status' => null,
            'txid' => null,
            'user_id' => null,
            'from' => null,
            'to' => null,
            'created_at' => null,
            'wallet_id' => null,
        ];

        $t = new TransactionModel();
        $t->user_id = $options['user_id'];
        $t->status = $options['status'];
        $t->currency = $currency;
        $t->amount = $amount;
        $t->wallet_id = $options['wallet_id'];
        $t->wallet_to = $options['to'];
        $t->created_at = date('Y-m-d H:i:s');
        $t->updated_at = date('Y-m-d H:i:s');
        $t->category = $category;
        $t->txid = $options['txid'];
        $t->platform = array_get_val($options, 'platform', PLATFORM_FINDIRI);
        $t->user_wallet = $options['from'];
        if (!is_null($options['created_at'])) {
            $t->created_at_timestamp = $options['created_at'];
        }
        $t->save();

        return $t;
    }

    public static function getTokenRate(string $currency): float {
        if ($currency === 'usd') {
            return settings()->token_price;
        }

        $usd_price = ceil(WalletModel::getRate('usd', $currency));
        if ($usd_price <= 0) {
            if (App::isDevelopment()) {
                return settings()->token_price;
            }
            throw new Exception('Price is incorrect');
        }

        $rate = settings()->token_price / $usd_price;
        if ($rate <= 0) {
            throw new Exception('Rate is incorrect');
        }

        return $rate;
    }

    public static function buyToken(int $user_id, string $currency, float $amount, ?string $promo_code = null): array {
        $from_wallet = self::getWallet($user_id, $currency);

        if (!$from_wallet) {
            throw new WalletNotFoundException('wallet_not_found');
        }
        /* @var WalletModel $from_wallet */

        $to_wallet = self::getWallet($user_id, CURRENCY_FNDR);

        if (!$to_wallet) {
            $to_wallet = self::generateTokenWallet($user_id);
        }
        /* @var WalletModel $to_wallet */

        $rate = self::getTokenRate($currency);
        $sub_amount = $rate * $amount;

        if ($from_wallet->amount < $sub_amount) {
            throw new InsufficientFundsException(lang('insufficient_funds'));
        }

        $swap = Transaction::wrap(function () use (
            $user_id, $from_wallet, $sub_amount, $to_wallet, $amount, $rate, $promo_code, $currency
        ) {
            if (!$from_wallet->subAmount($sub_amount)) {
                throw new Exception(lang('api_error'));
            }

            if (!$to_wallet->addAmount($amount)) {
                throw new Exception(lang('api_error'));
            }

            $total_amount = static::calcRewardByAmount($amount);
            if ($promo_code !== null) {
                self::sendReferralReward($user_id, $sub_amount, $currency, $promo_code);
                $total_amount += $amount
                    *
                    (float) SettingsModel::getSettingByKey('coin_promo_buy_with_code_reward')->value;
            }

            $swap = new SwapModel();
            $swap->setFrom($from_wallet)
                ->setTo($to_wallet);
            $swap->from_amount = $sub_amount;
            $swap->to_amount = $total_amount;
            $swap->rate = $rate;
            $swap->status = UserBalanceHistoryModel::STATUS_COMPLETED;
            $swap->save();
            return $swap;
        });

        return  [
            'swap' => $swap,
            'from_wallet' => $from_wallet,
            'to_wallet' => $to_wallet,
        ];
    }

    /**
     * @param float $amount
     * @param float $sub_amount
     * @param string $currency
     * @param string|null $promo_code
     * @return float
     * @throws InvalidPromoCodeException
     */
    private static function calcRewardByAmount(float $amount): float {
        $periods = promoPeriods();

        $reward = 0;
        $current_date = time();
        foreach ($periods as $period) {
            $from = (int) $period['from']->value;
            $to = (int) $period['to']->value;
            $percent = (float) $period['percent']->value;
            $balance = (float) $period['balance']->value;

            if ($current_date >= $from && $current_date < $to) {
                if (($amount + $amount * $percent) <= $balance) {
                    $reward = $amount * $percent;

                    /** @var SettingsModel $setting */
                    $setting = $period['balance'];

                    $setting->value = (float) $setting->value - ($amount + $reward);
                    $setting->save();
                }
                break;
            }
        }

        return $amount + $reward;
    }

    private static function sendReferralReward(int $user_id, float $sub_amount, string $currency, $promo_code) {
        $referral_id = CodeGeneratorService::decodeUserCode($promo_code);
        try {
            $referral = UserModel::get($referral_id);
        } catch (ModelNotFoundException $e) {
            throw new InvalidPromoCodeException(lang('invalid_promo_code'));
        }

        if ($user_id == $referral_id) {
            throw new InvalidPromoCodeException(lang('invalid_promo_code'));
        }

        /** @var UserModel|null $referral */
        if (!is_null($referral)) {
            $ret = self::addReferralReward($referral, $user_id, $sub_amount, $currency);

            if (!$ret) {
                throw new \Exception(lang('api_error'));
            }
        }
    }

    private static function addReferralReward(UserModel $referral, int $target_id, float $amount, string $currency): bool {
        $referral_percent = SettingsModel::getSettingByKey('coin_promo_referral_reward')->value;
        $reward_balance = BalanceModule::getBalanceOrCreate($referral->id, $currency, BalanceModel::CATEGORY_PARTNERS);

        $profit = new ProfitModel();
        $profit->amount = $amount * $referral_percent;
        $profit->type = ProfitModel::TYPE_PROMO_CODE_REWARD;
        $profit->user_id = $referral->id;
        $profit->currency = $currency;
        $profit->wallet_id = $reward_balance->id;
        $profit->target_id = $target_id;
        $profit->created_at = date('Y-m-d H:i:s');
        $profit->rate = WalletModel::getRate(CURRENCY_BTC, $currency);
        $profit->save();

        BalanceHistorySaver::make()
            ->setToRaw(UserBalanceHistoryModel::TYPE_BALANCE, $profit->wallet_id, $profit->user_id, $profit->currency)
            ->setCreatedAt($profit->created_at_timestamp)
            ->setToAmount($profit->amount)
            ->setOperation(UserBalanceHistoryModel::OPERATION_PROMO_REWARD)
            ->setObjectId($profit->id)
            ->save();

        return $reward_balance->incrAmount($amount * $referral_percent);
    }

    public static function generateTokenWallet(int $user_id): WalletModel {
        $wallet = new WalletModel();
        $wallet->user_id = $user_id;
        $wallet->address = null;
        $wallet->currency = CURRENCY_FNDR;
        $wallet->amount = 0;
        $wallet->status = 'generated';
        $wallet->save();

        return $wallet;
    }

    public static function getWallet(int $user_id, string $currency): ?WalletModel {
        /** @var \Models\WalletModel|null $wallet */
        $wallet = WalletModel::first(Where::and()
            ->set('user_id', Where::OperatorEq, $user_id)
            ->set('currency', Where::OperatorEq, $currency)
        );

        return $wallet;
    }

    /**
     * @param int $user_id
     * @param int $wallet_id
     * @return WalletModel|null
     * @throws \Db\Exception\InvalidWhereOperatorException
     */
    public static function getWalletByUserAndId(int $user_id, int $wallet_id): ?WalletModel {
        return WalletModel::first(
            Where::and()
                ->set(Where::equal('user_id', $user_id))
                ->set(Where::equal('id', $wallet_id))
        );
    }

    /**
     * @param string $address
     * @param string $currency
     * @return WalletModel|null
     * @throws \Db\Exception\InvalidWhereOperatorException
     */
    public static function getWalletByAddress(string $address, string $currency): ?WalletModel {
        return WalletModel::first(
            Where::and()
                ->set( Where::equal('address', $address))
                ->set( Where::equal('currency', $currency))
        );
    }
}
