<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class MarketOrders extends AbstractMigration
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
        if ($this->hasTable('market_orders')) {
            return;
        }
        $this->table('market_orders', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'COMPACT',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('exchange_order_id', 'string', [
                'null' => true,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('exchange', 'string', [
                'null' => false,
                'limit' => 256,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'exchange_order_id',
            ])
            ->addColumn('pair', 'string', [
                'null' => false,
                'limit' => 16,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'exchange',
            ])
            ->addColumn('category', 'string', [
                'null' => false,
                'limit' => 16,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'pair',
            ])
            ->addColumn('price', 'double', [
                'null' => false,
                'after' => 'category',
            ])
            ->addColumn('amount', 'double', [
                'null' => false,
                'after' => 'price',
            ])
            ->addColumn('fulfilled', 'double', [
                'null' => false,
                'default' => '0',
                'after' => 'amount',
            ])
            ->addColumn('status', 'set', [
                'null' => false,
                'default' => 'pending',
                'limit' => 12,
                'after' => 'fulfilled',
                'values' => ['pending', 'done']
            ])
            ->addColumn('commission', 'float', [
                'null' => false,
                'default' => '0',
                'after' => 'status',
            ])
            ->addColumn('created_at', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'commission',
            ])
            ->addColumn('trader_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'created_at',
            ])
            ->addColumn('initializer_id', 'integer', [
                'null' => true,
                'limit' => '10',
                'signed' => false,
                'after' => 'trader_id',
            ])
            ->addColumn('initializer_type', 'set', [
                'null' => true,
                'limit' => 24,
                'after' => 'initializer_id',
                'values' => ['tradebot', 'inter_arbitrage']
            ])
            ->addColumn('_delete', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => '1',
                'after' => 'initializer_type',
            ])
            ->create();

    }
}
