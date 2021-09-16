<?php

namespace Modules;

use Core\Services\ExternalExchange\Base;
use Core\Services\ExternalExchange\Binance;
use Core\Services\ExternalExchange\Bitmex;
use Db\Transaction;
use Exception;
use Models\HedgingExAccount;
use Models\StackHistoryModel;
use Models\StackModel;

class HedgingExchangeModule {
    /**
     * @param StackModel $stack
     * @param HedgingExAccount $account
     * @return array
     * @throws Exception
     */
    public static function createShort(StackModel $stack, HedgingExAccount $account): array {
        if ($stack->primary_currency !== CURRENCY_BTC) {
            throw new \Exception('Only btc available now');
        }
        return Transaction::wrap(function () use ($stack, $account) {
            $short_amount = ceil(($stack->buy_rate / $stack->fiat_to_usd) * $stack->primary_amount);
            $exchange = self::exchangeByAccount($account);
            $order = $exchange->openMarketOrder(\Symbols::BTCUSD, Bitmex::SIDE_SELL, $short_amount);

            $stack->short_rate = $order['price'];
            $stack->short_fee = $order['fee'] ?? 0;
            $stack->account_id = $account->id;
            $stack->save();

            return $order;
        });
    }

    /**
     * @param StackHistoryModel $stack_history
     * @param HedgingExAccount $account
     * @return array
     * @throws Exception
     */
    public static function createLong(StackHistoryModel $stack_history, HedgingExAccount $account): array {
        if ($stack_history->currency !== CURRENCY_BTC) {
            throw new \Exception('Only btc available now');
        }
        return Transaction::wrap(function () use ($stack_history, $account) {
            $long_amount = ceil(($stack_history->sale_rate / $stack_history->fiat_to_usd) * $stack_history->amount);
            $exchange = self::exchangeByAccount($account);
            $order = $exchange->openMarketOrder(\Symbols::BTCUSD, Bitmex::SIDE_BUY, $long_amount);

            $stack_history->account_id = $account->id;
            $stack_history->long_rate = $order['price'];
            $stack_history->long_fee = $order['fee'] ?? 0;
            $stack_history->save();

            return $order;
        });
    }

    /**
     * @param HedgingExAccount $account
     * @return Base
     * @throws Exception
     */
    public static function exchangeByAccount(HedgingExAccount $account): Base {
        return self::getExchange($account->exchange, $account->public_key, $account->getPrivateKeyDecoded());
    }

    public static function getExchange(string $exchange_name, string $public_key, string $secret_key) {
        $exchange = null;

        switch ($exchange_name) {
            case HedgingExAccount::EXCHANGE_BINANCE:
                $exchange = new Binance($public_key, $secret_key);
                break;
            case HedgingExAccount::EXCHANGE_BITMEX:
                $exchange = new Bitmex($public_key, $secret_key);
                break;
            default:
                throw new Exception('Incorrect exchange');
        }

        return $exchange;
    }
}
