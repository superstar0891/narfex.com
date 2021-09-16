<?php 

namespace ClickHouseMigrations\CreateNew;

use ClickHouse\ClickHouse;
use Db\Migration\MigrationInterface;

class Migration_1568289177_MakeOrdersTable implements MigrationInterface {
	public static function up() {

        $fields = [
            'market' => 'String',
            'amount' => 'Float64',
            'price' => 'Float64',
            'action_time' => 'DateTime',
        ];

        $fields_result = [];
        foreach ($fields as $name => $type) {
            $fields_result[] = "{$name} {$type}";
        }
        $fields_result = implode(',', $fields_result);

        ClickHouse::shared()->query("
CREATE TABLE IF NOT EXISTS exchange_orders ({$fields_result})
ENGINE=MergeTree() PARTITION BY (toYYYYMM(action_time), market, toYYYYMMDD(action_time), toMinute(action_time), toHour(action_time))
ORDER BY action_time
PRIMARY KEY (action_time)      
");
    }

	public static function down() {

    }
}
