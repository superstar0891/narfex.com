<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class ExchangeTrades extends AbstractMigration
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
        if ($this->hasTable('exchange_trades')) {
            return;
        }
        $this->table('exchange_trades', [
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
            ->addColumn('taker_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'id',
            ])
            ->addColumn('maker_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'taker_id',
            ])
            ->addColumn('taker_order_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'maker_id',
            ])
            ->addColumn('maker_order_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'taker_order_id',
            ])
            ->addColumn('taker_wallet_from_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'maker_order_id',
            ])
            ->addColumn('taker_wallet_to_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'taker_wallet_from_id',
            ])
            ->addColumn('maker_wallet_from_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'taker_wallet_to_id',
            ])
            ->addColumn('maker_wallet_to_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'maker_wallet_from_id',
            ])
            ->addColumn('pair', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'maker_wallet_to_id',
            ])
            ->addColumn('amount', 'double', [
                'null' => false,
                'after' => 'pair',
            ])
            ->addColumn('price', 'double', [
                'null' => false,
                'after' => 'amount',
            ])
            ->addColumn('price_maker', 'double', [
                'null' => false,
                'after' => 'price',
            ])
            ->addColumn('created_at', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'price_maker',
            ])
            ->addColumn('bot', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => '1',
                'after' => 'created_at',
            ])
            ->create();

    }
}
