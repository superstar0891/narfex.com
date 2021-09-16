<?php

namespace Core\Services\Exchange;

use Core\App;
use Core\Exceptions\Exchange\AmountTooSmallException;
use Core\Exceptions\Exchange\DailyTransactionsLimitException;
use Core\Exceptions\Exchange\InsufficientFundsException;
use Core\Exceptions\Exchange\RateException;
use Core\Exceptions\FloodControl\FloodControlException;
use Core\Exceptions\Token\TokenException;
use Core\Exceptions\Token\TokenPermissionException;
use Core\Services\Hedging\Hedging;
use Db\Transaction;
use Exceptions\WithdrawalRequests\WalletNotFoundException;
use Models\BalanceModel;
use Models\SwapModel;
use Models\UserBalanceHistoryModel;
use Models\UserModel;
use Models\WalletModel;
use Modules\BalanceModule;
use Modules\FiatWalletModule;
use Modules\WalletModule;

class Exchange {

    private $from_currency;
    private $to_currency;
    private $amount;
    private $amount_type;
    private $user;

    private $from;
    private $to;

    public function __construct(string $from_currency, string $to_currency, float $amount, string $amount_type, UserModel $user) {
        $this->from_currency = $from_currency;
        $this->to_currency = $to_currency;
        $this->amount = $amount;
        $this->amount_type = $amount_type;
        $this->user = $user;
    }

    /**
     * @return ExchangeResponse
     * @throws AmountTooSmallException
     * @throws DailyTransactionsLimitException
     * @throws FloodControlException
     * @throws InsufficientFundsException
     * @throws RateException
     * @throws TokenException
     * @throws TokenPermissionException
     * @throws \Db\Exception\DbAdapterException
     * @throws \Db\Exception\InvalidSelectQueryException
     * @throws \Db\Exception\InvalidWhereOperatorException
     * @throws \Db\Model\Exception\ModelUndefinedFieldsException
     * @throws \Db\Model\Exception\TableNameUndefinedException
     * @throws WalletNotFoundException
     */
    public function execute(): ExchangeResponse {
        if ($this->checkIfTokenExchange()) {
            throw new TokenPermissionException('Can not exchange NRFX now.');
        } else {
            $history_item = $this->executeDefault();
        }

        return new ExchangeResponse($this->from, $this->to, $history_item);
    }

    /**
     * @return bool
     * @throws TokenException
     * @throws TokenPermissionException
     */
    public function checkIfTokenExchange(): bool {
        if ($this->to_currency === CURRENCY_FNDR || $this->from_currency === CURRENCY_FNDR) {
            return true;
        }

        return false;
//
//        if ($this->from_currency === TOKEN_ID) {
//            if ($this->to_currency !== 'btc') {
//                throw new TokenException('You can sell the token to btc currency only.');
//            }
//            if (!$this->user->hasRole('agent')) {
//                throw new TokenPermissionException();
//            }
//            return true;
//        }
    }

    /**
     * @return SwapModel
     * @throws AmountTooSmallException
     * @throws DailyTransactionsLimitException
     * @throws FloodControlException
     * @throws InsufficientFundsException
     * @throws RateException
     * @throws \Db\Exception\DbAdapterException
     * @throws \Db\Exception\InvalidSelectQueryException
     * @throws \Db\Exception\InvalidWhereOperatorException
     * @throws \Db\Model\Exception\ModelUndefinedFieldsException
     * @throws \Db\Model\Exception\TableNameUndefinedException
     * @throws WalletNotFoundException
     */
    private function executeDefault(): SwapModel {
        $from_is_fiat = WalletModel::isFiat($this->from_currency);
        $user = $this->user;

        $currency = $from_is_fiat ? $this->to_currency : $this->from_currency;
        $wallet = WalletModule::getWallet($user->id, $currency);
        if (!$wallet) {
            throw new WalletNotFoundException();
        }

        $balance = BalanceModule::getBalanceOrCreate($user->id, $from_is_fiat ? $this->from_currency : $this->to_currency, BalanceModel::CATEGORY_FIAT);
        $rate = FiatWalletModule::getRate(
            $this->from_currency,
            $this->to_currency,
            true,
            true,
            $from_is_fiat ? FiatWalletModule::FEE_DIRECTION_UP : FiatWalletModule::FEE_DIRECTION_DOWN
        );
        $type = $from_is_fiat ? 'buy' : 'sell';

        if ($from_is_fiat) {
            $this->from = $balance;
            $this->to = $wallet;
        } else {
            $this->from = $wallet;
            $this->to = $balance;
        }

        if ($this->amount_type === 'fiat') {
            $fiat_amount = $this->amount;
            $amount = $this->amount / $rate;
        } else {
            $fiat_amount = $this->amount * $rate;
            $amount = $this->amount;
        }

        $current_currency = $from_is_fiat ? $this->to_currency : $this->from_currency;
        $price_usd = FiatWalletModule::getAmountInAnotherCurrency($current_currency,'usd', $amount);

        if (!$rate) {
            throw new RateException();
        }

        if ($type === 'buy') {
            if ($balance->amount < $fiat_amount) {
                throw new InsufficientFundsException();
            }
        } else {
            if ($wallet->amount < $amount) {
                throw new InsufficientFundsException();
            }
        }

        if ($price_usd < settings()->swap_min_fiat_wallet_transaction_in_usd) {
            $precision = WalletModel::isFiat($this->to_currency) ? 2 : 8;
            $min_amount = FiatWalletModule::getAmountInAnotherCurrency(
                'usd',
                $this->to_currency,
                settings()->swap_min_fiat_wallet_transaction_in_usd
            );
            $error_currency = mb_strtoupper($this->to_currency);
            $min_amount = round($min_amount, $precision);
            $min_amount .= " $error_currency";
            $min_amount = lang('amount_too_small', [
                'min_amount' => $min_amount
            ]);
            throw new AmountTooSmallException($min_amount);
        }

        if (!FiatWalletModule::checkUserLimit($user->id, $price_usd)) {
            $precision = WalletModel::isFiat($this->to_currency) ? 2 : 8;
            $error_amount = FiatWalletModule::getAmountInAnotherCurrency('usd', $this->to_currency, settings()->swap_usd_daily_limit);
            $error_currency = mb_strtoupper($this->to_currency);
            $error_amount = round($error_amount, $precision);
            $error_amount .= " $error_currency";
            $error_amount = lang('user_daily_transactions_limit', [
                'limit' => $error_amount
            ]);
            throw new DailyTransactionsLimitException($error_amount);
        }

        if (!floodControl('fiat_exchange_' . $user->id, KERNEL_CONFIG['flood_control']['fiat_exchange'])) {
            throw new FloodControlException();
        }

        return Transaction::wrap(function () use ($user, $balance, $wallet, $type, $fiat_amount, $rate, $amount, $price_usd, $from_is_fiat) {
            if ($type === 'buy') {
                if (!$balance->checkAmount($fiat_amount)) {
                    throw new \Exception();
                }

                if (!$balance->decrAmount($fiat_amount)) {
                    throw new \Exception();
                }

                if (!$wallet->addAmount($amount)) {
                    throw new \Exception();
                }

                if (App::isProduction() && $from_is_fiat && in_array($wallet->currency, [Hedging::CURRENCY_BTC, Hedging::CURRENCY_ETH], true)) {
                    $fiat_currency = $balance->currency;
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
            } else {
                if (!$wallet->checkAmount($amount)) {
                    throw new \Exception();
                }

                if (!$balance->incrAmount($fiat_amount)) {
                    throw new \Exception();
                }

                if (!$wallet->subAmount($amount)) {
                    throw new \Exception();
                }
            }

            $fee = settings()->getFiatExchangeFee($from_is_fiat);

            $from_amount = $from_is_fiat ? $fiat_amount : $amount;
            $to_amount = $from_is_fiat ? $amount : $fiat_amount;
            $swap = new SwapModel();
            $swap->setFrom($from_is_fiat ? $balance : $wallet);
            $swap->setTo($from_is_fiat ? $wallet : $balance);
            $swap->from_amount = $from_amount;
            $swap->to_amount = $to_amount;
            $swap->user_id = $user->id;
            $swap->fee = $from_amount * $fee / 100;
            $swap->rate = $rate;
            $swap->status = UserBalanceHistoryModel::STATUS_COMPLETED;
            return $swap->save();
        });
    }
}
