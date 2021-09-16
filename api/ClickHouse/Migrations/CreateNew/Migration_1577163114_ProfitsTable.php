<?php 

namespace ClickHouseMigrations\CreateNew;

use ClickHouse\ClickHouse;
use Db\Migration\MigrationInterface;

class Migration_1577163114_ProfitsTable implements MigrationInterface {
	public static function up() {
        $fields = [
            'type' => 'String',
            'amount' => 'Float64',
            'currency' => 'String',
            'usd_amount' => 'Float64',
            'target_id' => 'Int64',
            'action_time' => 'DateTime',
        ];

        $fields_result = [];
        foreach ($fields as $name => $type) {
            $fields_result[] = "{$name} {$type}";
        }
        $fields_result = implode(',', $fields_result);

        ClickHouse::shared()->query("
CREATE TABLE IF NOT EXISTS profits ({$fields_result})
ENGINE=MergeTree() PARTITION BY (toYYYYMM(action_time), type, target_id, toYYYYMMDD(action_time), toMinute(action_time), toHour(action_time))
ORDER BY action_time
PRIMARY KEY (action_time)      
");
    }

	public static function down() {}
}
