<?php

namespace Modules;

use ClickHouse\ClickHouse;
use Db\Db;

class StatsModule {
    private static function write(string $table_name, array $row) {
        $fields = array_map(function ($row) {
            return '`' . Db::escape($row) . '`';
        }, array_keys($row));
        $fields = implode(',', $fields);

        $values = array_map(function ($row) {
            if (is_string($row)) {
                return "'" . Db::escape($row) . "'";
            } else if (is_double($row)) {
                return (double) $row;
            } else if (is_numeric($row)) {
                return (int) $row;
            } else {
                return $row;
            }
        }, array_values($row));
        $values = implode(',', $values);

        ClickHouse::shared()->exec("INSERT INTO `{$table_name}` ({$fields}) VALUES ({$values})");
    }

    // Project profits
    public static function profit(string $type, float $amount, string $currency, int $target_id = 0) {
//        $row = [
//            'type' => $type,
//            'amount' => $amount,
//            'currency' => $currency,
//            'usd_amount' => WalletModule::getUsdPrice($currency) * $amount,
//            'target_id' => $target_id,
//            'action_time' => date('Y-m-d H:i:s'),
//        ];
//
//        try {
//            self::write('profits', $row);
//        } catch (\Exception $e) { }
    }
}
