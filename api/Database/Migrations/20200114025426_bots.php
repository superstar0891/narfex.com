<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class Bots extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        if ($this->hasTable('bots')) {
            return;
        }
        $this->table('bots', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'comment' => '',
            'row_format' => 'COMPACT',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('created_at_timestamp', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'id',
            ])
            ->addColumn('updated_at_timestamp', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'created_at_timestamp',
            ])
            ->addColumn('deleted_at', 'integer', [
                'null' => true,
                'limit' => '10',
                'signed' => false,
                'after' => 'updated_at_timestamp',
            ])
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'deleted_at',
            ])
            ->addColumn('type', 'string', [
                'null' => false,
                'default' => 'default',
                'limit' => 30,
                'collation' => 'utf8mb4_general_ci',
                'encoding' => 'utf8mb4',
                'after' => 'user_id',
            ])
            ->addColumn('symbol', 'string', [
                'null' => false,
                'limit' => 30,
                'collation' => 'utf8mb4_general_ci',
                'encoding' => 'utf8mb4',
                'after' => 'type',
            ])
            ->addColumn('balance', 'double', [
                'null' => false,
                'default' => '0',
                'after' => 'symbol',
            ])
            ->addColumn('name', 'string', [
                'null' => true,
                'limit' => 125,
                'collation' => 'utf8mb4_general_ci',
                'encoding' => 'utf8mb4',
                'after' => 'balance',
            ])
            ->addColumn('exchange', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_general_ci',
                'encoding' => 'utf8mb4',
                'after' => 'name',
            ])
            ->addColumn('trade_amount', 'double', [
                'null' => false,
                'default' => '0',
                'after' => 'exchange',
            ])
            ->addColumn('max_trade_amount', 'double', [
                'null' => false,
                'after' => 'trade_amount',
            ])
            ->addColumn('indicators', 'text', [
                'null' => false,
                'limit' => 65535,
                'collation' => 'utf8mb4_general_ci',
                'encoding' => 'utf8mb4',
                'after' => 'max_trade_amount',
            ])
            ->addColumn('leverage', 'integer', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'indicators',
            ])
            ->addColumn('time_frame', 'string', [
                'null' => false,
                'default' => '15m',
                'limit' => 256,
                'collation' => 'utf8mb4_general_ci',
                'encoding' => 'utf8mb4',
                'after' => 'leverage',
            ])
            ->addColumn('position', 'string', [
                'null' => false,
                'default' => 'none',
                'limit' => 256,
                'collation' => 'utf8mb4_general_ci',
                'encoding' => 'utf8mb4',
                'after' => 'time_frame',
            ])
            ->addColumn('position_amount', 'double', [
                'null' => false,
                'default' => '0',
                'after' => 'position',
            ])
            ->addColumn('roe', 'double', [
                'null' => false,
                'default' => '0',
                'after' => 'position_amount',
            ])
            ->addColumn('status', 'string', [
                'null' => false,
                'default' => 'deactivated',
                'limit' => 30,
                'collation' => 'utf8mb4_general_ci',
                'encoding' => 'utf8mb4',
                'after' => 'roe',
            ])
            ->addColumn('start_date', 'integer', [
                'null' => true,
                'limit' => '10',
                'after' => 'status',
            ])
            ->addColumn('take_profit', 'double', [
                'null' => false,
                'default' => '0',
                'after' => 'start_date',
            ])
            ->addColumn('liquidation_price', 'double', [
                'null' => false,
                'default' => '0',
                'after' => 'take_profit',
            ])
            ->create();

    }
}
