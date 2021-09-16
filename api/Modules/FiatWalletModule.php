<?php

namespace Modules;

use Core\App;
use Core\Exceptions\Withdrawal\InsufficientFundsException;
use Core\Services\Curl\CurlAdapter;
use Core\Services\Hedging\Hedging;
use Core\Services\Redis\RedisAdapter;
use Core\Services\Merchant\XenditService;
use Core\Services\Telegram\SendService;
use DateTime;
use Db\Exception\DbAdapterException;
use Db\Exception\InvalidSelectQueryException;
use Db\Exception\InvalidWhereOperatorException;
use Db\Model\Exception\ModelUndefinedFieldsException;
use Db\Model\Exception\TableNameUndefinedException;
use Db\Model\ModelSet;
use Db\Transaction;
use Db\Where;
use Exception;
use Exceptions\WithdrawalRequests\IncorrectStatusException;
use Libs\Pdf\FPDF;
use Libs\Pdf\Invoice;
use LogicException;
use Models\BalanceHistoryModel;
use Models\BalanceModel;
use Models\ExternalInvoiceModel;
use Models\FiatPaymentModel;
use Models\SwapModel;
use Models\UserBalanceHistoryModel;
use Models\UserModel;
use Models\WalletModel;
use Models\WithdrawalModel;

class FiatWalletModule {
    const FEE_DIRECTION_DOWN = 'down';
    const FEE_DIRECTION_UP = 'up';

    const CURRENCY_USD = 'usd',
        CURRENCY_IDR = 'idr';

    public static function getRate($base, $currency, $fee_needed = true, $skip_cache = false, $fee_direction = self::FEE_DIRECTION_UP) {
        $currencies = WalletModel::availableCurrencies([], false);

        if ($currencies[$base]['type'] === 'fiat' && $currencies[$currency]['type'] === 'fiat') {
            $base_btc_rate = self::getRate($base, 'btc', false);
            $currency_btc_rate = self::getRate($currency, 'btc', false);
            return $base_btc_rate / $currency_btc_rate;
        }

        if ($base === CURRENCY_FNDR) {
            $currency_to_usd = self::getRate('usd', $currency);
            return $currency_to_usd / settings()->token_price;
        }

        if ($currencies[$base]['type'] === 'fiat') {
            $tmp = $base;
            $base = $currency;
            $currency = $tmp;
        }

        $cache_key = 'fiat_rate_' . $base . '_' . $currency;
        $price = false;
        if (!$skip_cache) {
            $price = (double) RedisAdapter::shared()->get($cache_key);
        }

        if (!$price || $price < 0) {
            $curl = new CurlAdapter();
            $rate_type = $fee_direction === self::FEE_DIRECTION_UP ? 'buy' : 'sell';
            $resp = $curl->fetchGet('https://api.coinbase.com/v2/prices/' . $base . '-' . $currency . '/' . $rate_type);
            $resp = json_decode($resp, true);
            if (!$resp || !isset($resp['data'])) {
                return false;
            }

            $data = $resp['data'];
            if (!isset($data['amount'])) {
                try {
                    $telegram = new SendService(SendService::CHAT_BITCOINOVNET);
                    $telegram->sendMessage('#WARNING #coinbase_failed' . PHP_EOL . 'Could not get an answer with rates from coinbase');
                } catch (\Exception $e) {
                    //
                }
                return false;
            }

            $price = floatval($data['amount']);
            RedisAdapter::shared()->set($cache_key, $price, 5);
        }

        if ($fee_needed) {
            if (App::isBitcoinovnet()) {
                $fee = settings()->getBitcoinovnetSwapFee();
            } else {
                $fee = settings()->getFiatExchangeFee($fee_direction === self::FEE_DIRECTION_UP);
            }
            if ($fee_direction === self::FEE_DIRECTION_DOWN) {
                $price -= $price * $fee / 100;
            } else {
                $price += $price * $fee / 100;
            }
        }

        return $price;
    }

    public static function generateInvoice(UserModel $user, $amount, $currency): ?FPDF {
        $conf = [
            'usd' => [
                'intermediary' => ['RZBAATWW - RAIFFEISEN BANK', 'INTERNATIONAL AG'],
                'number' => '099480001102'
            ],
            'eur' => [
                'intermediary' => ['RZBAATWW - RAIFFEISEN BANK', 'INTERNATIONAL AG'],
                'number' => '099790001101'
            ],
            'rub' => [
                'intermediary' => ['ALFARUMM - ALFABANK MOSCOW RUSSIA'],
                'number' => '099460001103'
            ],
            'gbp' => [
                'intermediary' => ['RZBAATWW - RAIFFEISEN BANK', 'INTERNATIONAL AG'],
                'number' => '099280001104'
            ],
            'none' => [
                'intermediary' => ['NONE'],
                'number' => 'NONE'
            ],
        ];

        $fee_conf = KERNEL_CONFIG['fiat']['invoice_fee'];
        if (!isset($fee_conf[$currency])) {
            return null;
        }
        $fee_conf = $fee_conf[$currency];

        $amount += max($fee_conf['min'], $amount * $fee_conf['percent'] / 100);

        $pdf = new Invoice('P','mm','A4');
        $title = 'Bitcoinbot invoice';
        $pdf->SetTitle($title);
        $pdf->SetAuthor('Bitcoinbot support');
        $pdf->PrintChapter(
            $amount,
            date('d F Y'),
            time(),
            strtoupper($currency),
            $user->first_name . ' ' . $user->last_name,
            isset($conf[strtolower($currency)]) ? $conf[strtolower($currency)] : $conf['none']
            );
        return $pdf;
    }

    public static function addPayment(string $type, BalanceModel $balance, float $amount, UserModel $user, $invoice_id = null, ?string $fee = null) {
        Transaction::wrap(function () use ($type, $balance, $amount, $user, $invoice_id, $fee) {
            if (!$balance->incrAmount($amount)) {
                throw new Exception();
            }

            $from_balance = BalanceModule::getFakeBalance(BalanceModel::FAKE_INVOICE, $balance->currency);

            $extra = [
                'currency' => $balance->currency,
                'payment_method' => $type,
                'fee' => $fee
            ];

            BalanceModule::addHistory(
                'receive',
                $amount,
                $user->id,
                $from_balance,
                $balance,
                $extra
            );

            $p = new FiatPaymentModel();
            $p->amount = $amount;
            $p->currency = $balance->currency;
            $p->status = 'approved';
            $p->user_id = $user->id;
            $p->payment_type = $type;
            $p->save();

            if ($invoice_id) {
                $i = new ExternalInvoiceModel();
                $i->invoice_id = $invoice_id;
                $i->merchant = $type;
                $i->save();
            }
        });
    }

    public static function exchange(
        UserModel $user,
        BalanceModel $balance,
        WalletModel $wallet,
        string $type,
        float $fiat_amount,
        float $rate,
        float $amount,
        float $price_usd,
        string $fiat_currency
    ): BalanceHistoryModel {

        return Transaction::wrap(function () use (
            $user,
            $balance,
            $wallet,
            $type,
            $fiat_amount,
            $rate,
            $amount,
            $price_usd,
            $fiat_currency
        ) {
            if ($type === 'buy') {
                if (!$balance->checkAmount($fiat_amount)) {
                    throw new Exception('checkAmount');
                }

                if (!$balance->decrAmount($fiat_amount)) {
                    throw new Exception('decrAmount');
                }

                if (!$wallet->addAmount($amount)) {
                    throw new Exception('addAmount');
                }

                if (in_array($wallet->currency, [Hedging::CURRENCY_BTC, Hedging::CURRENCY_ETH], true)) {
                    if ($fiat_currency !== 'usd') {
                        $usd_rate = FiatWalletModule::getRate(
                            'usd',
                            $wallet->currency,
                            true,
                            false,
                            FiatWalletModule::FEE_DIRECTION_UP
                        );
                    } else {
                        $usd_rate = $rate;
                    }

                    Hedging::addToQueue(
                        $wallet->currency,
                        $amount,
                        $user->id,
                        $rate,
                        $usd_rate,
                        $fiat_amount,
                        $fiat_currency
                    );
                }

                $history_type = 'send';
                $history_amount = $amount;
                $from_balance = $balance;
                $to_balance = BalanceModule::getFakeBalance(BalanceModel::FAKE_WALLET, $wallet->currency);
            } else {
                if (!$wallet->checkAmount($amount)) {
                    throw new Exception();
                }

                if (!$balance->incrAmount($fiat_amount)) {
                    throw new Exception();
                }

                if (!$wallet->subAmount($amount)) {
                    throw new Exception();
                }

                $history_type = 'receive';
                $history_amount = $fiat_amount;
                $from_balance = BalanceModule::getFakeBalance(BalanceModel::FAKE_WALLET, $wallet->currency);
                $to_balance = $balance;
            }

            $extra = [
                'fiat_amount' => $fiat_amount,
                'from_currency' => $from_balance->currency,
                'to_currency' => $to_balance->currency,
                'crypto_amount' => $amount,
                'price' => $rate,
                'price_usd' => $price_usd
            ];

            return BalanceModule::addHistory(
                $history_type,
                $history_amount,
                $user->id,
                $from_balance,
                $to_balance,
                $extra
            );
        });
    }

    /**
     * Method returns user limit
     *
     * @param int $user_id
     * @param int|float $amount
     * @return float
     * @throws DbAdapterException
     * @throws InvalidSelectQueryException
     * @throws InvalidWhereOperatorException
     * @throws ModelUndefinedFieldsException
     * @throws TableNameUndefinedException
     */
    public static function checkUserLimit(int $user_id, $amount): bool {
        $usd_amount = 0;
        $swaps = SwapModel::select(
            Where::and()
                ->set(Where::equal('user_id', $user_id))
                ->set('created_at_timestamp', Where::OperatorGreater, time() - 86400)
        );

        foreach ($swaps as $swap) {
            /** @var SwapModel $swap */
            $usd_amount += $swap->toUSD();
        }
        $user_limit = settings()->swap_usd_daily_limit - $usd_amount;

        return $user_limit - $amount > 0;
    }

    public static function getAmountInUsd(string $current_currency, float $amount): float {
        if (WalletModel::isFiat($current_currency)) {
            return 0;
        }

        $rate = self::getRate($current_currency, 'usd', false);
        return $rate * $amount;
    }

    /**
     * Amount must equals to current currency.
     *
     * @param string $current_currency
     * @param string $needed_currency
     * @param float $amount
     * @param bool $fee_needed
     * @return float
     */
    public static function getAmountInAnotherCurrency(string $current_currency, string $needed_currency, float $amount, bool $fee_needed = false): float {
        if ($current_currency === CURRENCY_FNDR) {
            return self::getAmountInAnotherCurrency('usd', $needed_currency, $amount * settings()->token_price);
        }

        if ($needed_currency === CURRENCY_FNDR) {
            return self::getAmountInAnotherCurrency($current_currency, 'usd', $amount * settings()->token_price);
        }

        if ($current_currency === $needed_currency) {
            return $amount;
        }

        if (WalletModel::isFiat($current_currency)) {
            if (WalletModel::isFiat($needed_currency)) {
                $current_currency_btc_rate = self::getRate($current_currency, 'btc', $fee_needed);
                $btc_value = $amount / $current_currency_btc_rate;
                $to_currency_rate = self::getRate('btc', $needed_currency, $fee_needed);
                return $btc_value * $to_currency_rate;
            } else {
                $current_currency_crypto_rate = self::getRate($current_currency, $needed_currency, $fee_needed);
                return $amount / $current_currency_crypto_rate;
            }
        } else {
            if (WalletModel::isFiat($needed_currency)) {
                $rate = self::getRate($needed_currency, $current_currency, $fee_needed);
                return $amount * $rate;
            } else {
                $current_currency_usd_rate = self::getRate($current_currency, 'usd', $fee_needed);
                $current_currency_usd_price = $current_currency_usd_rate / $amount;
                $needed_currency_rate = self::getRate('usd', $needed_currency, $fee_needed);
                return $current_currency_usd_price / $needed_currency_rate;
            }
        }
    }

    /**
     * @param WithdrawalModel $withdrawal
     * @param string $reason
     * @return WithdrawalModel
     * @throws IncorrectStatusException
     */
    public static function rejectWithdrawal(WithdrawalModel $withdrawal, string $reason): WithdrawalModel {
        if ($withdrawal->status !== UserBalanceHistoryModel::STATUS_CONFIRMATION) {
            throw new IncorrectStatusException('Withdrawal not in confirmation state');
        }

        return Transaction::wrap(function() use ($withdrawal, $reason) {
            /** @var BalanceModel $balance */
            $balance = BalanceModel::get($withdrawal->from_id);
            $total_amount = $withdrawal->amount + $withdrawal->fee;

            $withdrawal->status = UserBalanceHistoryModel::STATUS_FAILED;
            $withdrawal->reject_message = $reason;
            $withdrawal->save();

            if (!$balance->decrLockedAmount($total_amount)) {
                throw new \Exception('Failed to decrement locked balance amount');
            }
            if (!$balance->incrAmount($total_amount)) {
                throw new \Exception('Failed to increment balance amount');
            }

            NotificationsModule::sendWithdrawalNotification($withdrawal);

            return $withdrawal;
        });
    }

    /**
     * @param WithdrawalModel $withdrawal
     * @param UserModel $user
     * @return WithdrawalModel
     * @throws LogicException
     * @throws Exception
     */
    public static function approveWithdrawal(WithdrawalModel $withdrawal, UserModel $user = null): WithdrawalModel {
        return Transaction::wrap(function () use ($withdrawal, $user) {
            $check_withdrawal = WithdrawalModel::queryBuilder()
                ->columns([])
                ->where(Where::and()
                    ->set('status', Where::OperatorEq, UserBalanceHistoryModel::STATUS_CONFIRMATION)
                    ->set('id', $withdrawal->id))
                ->forUpdate(true)
                ->select();
            $check_withdrawal = WithdrawalModel::rowsToSet($check_withdrawal);

            if ($check_withdrawal->isEmpty()) {
                throw new LogicException('Fiat withdrawal has an invalid status or type');
            }

            /** @var WithdrawalModel $withdrawal */
            $withdrawal = $check_withdrawal->first();

            //IDR logic
            if (!XenditService::checkAvailableBalance($withdrawal->amount)) {
                throw new InsufficientFundsException();
            }

            $xendit_response = XenditService::createDisbursement(
                $withdrawal->id,
                $withdrawal->bank_code,
                $withdrawal->account_holder_name,
                $withdrawal->account_number,
                'xendit disbursement #' . $withdrawal->id,
                $withdrawal->amount,
                json_decode($withdrawal->email_to, true) ?? []
            );

            $withdrawal->external_id = $xendit_response['id'];
            $withdrawal->status = UserBalanceHistoryModel::STATUS_PENDING;
            $withdrawal->save();

            NotificationsModule::sendWithdrawalNotification($withdrawal);

            return $withdrawal;
        });
    }

    public static function telegramWithdrawalsApprove(array $withdrawals, ModelSet $users): string {
        return '#auto_approve' .
            self::telegramWithdrawals($withdrawals, $users, PHP_EOL . PHP_EOL);
    }

    public static function telegramWithdrawalsManual(array $withdrawals, ModelSet $users): string {
        return '#need_manual_approve' .
            self::telegramWithdrawals($withdrawals, $users, PHP_EOL . PHP_EOL);
    }

    private static function telegramWithdrawals(array $withdrawals, ModelSet $users, string $separator): string {
        $message_items = array_map(function (WithdrawalModel $withdrawal) use ($users) {
            /** @var UserModel $user */
            $user = $users->getItem($withdrawal->user_id);
            return self::generateTelegramWithdrawalMessageItem($withdrawal, $user);
        }, $withdrawals);
        return $separator . implode($separator, $message_items);
    }

    public static function generateTelegramWithdrawalMessageItem(WithdrawalModel $withdrawal, UserModel $user): string {
        return sprintf('ID: %s, %s(%s), Date: %s, %s',
            $withdrawal->id,
            $user->login,
            $user->id,
            (new DateTime())->setTimestamp($withdrawal->created_at_timestamp)->format('d.m.Y H:i:s'),
            formatNum($withdrawal->amount, 2) . ' ' . strtoupper($withdrawal->currency)
        );
    }

    public static function swapRate(string $base, string $currency) {
        $from_is_fiat = WalletModel::isFiat($base);

        return static::getRate(
            $base,
            $currency,
            true,
            false,
            $from_is_fiat ? static::FEE_DIRECTION_UP : static::FEE_DIRECTION_DOWN
        );
    }
}
