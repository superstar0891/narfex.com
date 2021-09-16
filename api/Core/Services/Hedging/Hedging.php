<?php

namespace Core\Services\Hedging;

use Core\Services\ExternalExchange\Bitmex;
use Db\Transaction;
use Models\ExternalExchangePositionModel;

class Hedging {

    const EXCHANGE_BITMEX = 'bitmex';

    const CURRENCY_BTC = 'btc';
    const CURRENCY_ETH = 'eth';
    const AVAIL_CURRENCIES = [self::CURRENCY_BTC, self::CURRENCY_ETH];

    /*
     * @deprecated
     */
//    public static function openLong(
//        string $currency,
//        float $amount,
//        string $exchange,
//        int $user_id,
//        float $rate,
//        float $price_usd,
//        float $fiat_amount,
//        string $fiat_currency
//    ): float {
//        if (!in_array($currency, self::AVAIL_CURRENCIES)) {
//            throw new \Exception('Unknown currency: ' . $currency);
//        }
//
//        switch ($exchange) {
//            case self::EXCHANGE_BITMEX:
//                return self::openBitmex($currency, $amount, $user_id, $rate, $price_usd, $fiat_amount, $fiat_currency);
//            default:
//                throw new \Exception('Unknown exchange: ' . $exchange);
//        }
//    }

    public static function getBitmexSymbol(string $currency): string {
        $symbols_map = [
            self::CURRENCY_BTC => 'XBTUSD',
            self::CURRENCY_ETH => 'ETHUSD',
        ];

        if (!isset($symbols_map[$currency])) {
            throw new \Exception('Currency ' . $currency . ' unsupported on bitmex');
        }

        return $symbols_map[$currency];
    }

    public static function getPosition(string $currency): array {
        $credentials = KERNEL_CONFIG['hedging']['bitmex'];
        $bitmex = new Bitmex(
            $credentials['key'],
            $credentials['secret']
        );

        $symbol = self::getBitmexSymbol($currency);
        return $bitmex->getPosition($symbol);
    }
//
//    private static function openBitmex(
//        string $currency,
//        float $amount,
//        int $user_id,
//        float $rate,
//        float $usd_price,
//        float $fiat_amount,
//        string $fiat_currency
//    ): float {
//        $symbol = self::getBitmexSymbol($currency);
//        $credentials = KERNEL_CONFIG['hedging']['bitmex'];
//        $bitmex = new Bitmex(
//            $credentials['key'],
//            $credentials['secret']
//        );
//        $last_price = $bitmex->getLastPrice($symbol);
//
//        $row = new ExternalExchangePositionModel;
//        $row->exchange = ExternalExchangePositionModel::EXCHANGE_BITMEX;
//        $row->amount = $amount;
//        $row->fiat_amount = $fiat_amount;
//        $row->fiat_currency = $fiat_currency;
//        $row->currency = $currency;
//        $row->rate = $last_price;
//        $row->exchange_rate = $usd_price;
//        $row->fiat_rate = $rate;
//        $row->status = ExternalExchangePositionModel::STATUS_PENDING;
//        $row->user_id = $user_id;
//
//        $order = Transaction::wrap(function () use ($last_price, $bitmex, $symbol, $amount, $row) {
//            $usd_amount = ceil($last_price * $amount);
//            $usd_balance = ($bitmex->getBalance() * $last_price);
//
//            if ($usd_balance - $usd_amount < 0) {
//                throw new \Exception('Exchange funds error');
//            }
//
//            $order = $bitmex->openMarketOrder($symbol, Bitmex::SIDE_BUY, ceil($last_price * $amount));
//
//            if (!$order) {
//                throw new \Exception('Cant place order');
//            }
//
//            $row->real_rate = (float) $order['price'];
//            $row->save();
//            return $order;
//        });
//
//        return (float) $order['price'];
//    }

    private static function closeBitmex(ExternalExchangePositionModel ...$positions) {
        $symbol = self::getBitmexSymbol($positions[0]->currency);
        $credentials = KERNEL_CONFIG['hedging']['bitmex'];
        $bitmex = new Bitmex(
            $credentials['key'],
            $credentials['secret']
        );

        Transaction::wrap(function () use ($bitmex, $symbol, $positions) {
            $amount = 0;
            foreach ($positions as $position) {
                $amount += ceil($position->amount * $position->rate);
            }
            $order = $bitmex->openMarketOrder($symbol, Bitmex::SIDE_SELL, $amount);

            foreach ($positions as $position) {
                $position->close_rate = (float) $order['price'];
                $position->status = ExternalExchangePositionModel::STATUS_CLOSED;
                $position->save();
            }
        });
    }

    public static function closePosition(ExternalExchangePositionModel ...$positions) {
        switch ($positions[0]->exchange) {
            case ExternalExchangePositionModel::EXCHANGE_BITMEX:
                self::closeBitmex(...$positions);
                break;
            default:
                throw new \Exception('Unknown exchange: ' . $positions[0]->exchange);
        }
    }

    public static function addToQueue(
        string $currency,
        float $amount,
        int $user_id,
        float $rate,
        float $price_usd,
        float $fiat_amount,
        string $fiat_currency
    ) {
        if (!in_array($currency, self::AVAIL_CURRENCIES)) {
            throw new \Exception('Unknown currency: ' . $currency);
        }

        $row = new ExternalExchangePositionModel;
        $row->exchange = ExternalExchangePositionModel::EXCHANGE_BITMEX;
        $row->amount = $amount;
        $row->fiat_amount = $fiat_amount;
        $row->fiat_currency = $fiat_currency;
        $row->currency = $currency;
        $row->rate = 0;
        $row->exchange_rate = $price_usd;
        $row->fiat_rate = $rate;
        $row->status = ExternalExchangePositionModel::STATUS_IN_QUEUE;
        $row->user_id = $user_id;
        $row->real_rate = 0;
        $row->save();
    }
}
