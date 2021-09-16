<?php

namespace Serializers;

use Models\ExMarketModel;
use Models\ExOrderModel;
use Modules\WalletModule;

class ExchangeSerializer {
    public static function orderListItem(ExOrderModel $order): array {
        return $order->toJson();
    }

    public static function marketListItem(ExMarketModel $market): array {
        $decimals = $market->getDecimals();
        return [
            'name' => strtoupper($market->getName()),
            'max_amount' => (double) $market->max_amount,
            'min_amount' => (double) $market->min_amount,
            'config' => [
                'primary_coin' => [
                    'name' => strtolower($market->primary_coin),
                    'decimals' => (int) $decimals['primary'],
                ],
                'secondary_coin' => [
                    'name' => strtolower($market->secondary_coin),
                    'decimals' => (int) $decimals['secondary'],
                ]
            ]
        ];
    }

    public static function tickerListItem(string $market, \stdClass $ticker): array {
        list($primary, $secondary) = explode('/', $market);

        return [
            'market' => strtoupper($market),
            'price' => (double) $ticker->last_price,
            'usd_price' => (double) WalletModule::getUsdPrice($secondary) * $ticker->last_price,
            'percent' => (double) $ticker->last_price > 0 ? ($ticker->last_price - $ticker->first_price) / $ticker->last_price * 100 : 0,
            'diff' => (double) $ticker->last_price - $ticker->first_price,
            'volume' => (double) $ticker->volume,
            'usd_volume' => (double) WalletModule::getUsdPrice($primary) * $ticker->volume,
            'max' => (double) $ticker->max_price,
            'min' => (double) $ticker->min_price,
        ];
    }

    public static function tradeListItem($row): array {
        $date = new \DateTime($row['action_time'], new \DateTimeZone('UTC'));
        return [
            'price' => (double) $row['price'],
            'amount' => (double) $row['amount'],
            'date' => $date->getTimestamp(),
        ];
    }
}
