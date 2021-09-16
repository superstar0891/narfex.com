<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class ExchangeOrderMarket extends AbstractMigration
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
        if ($this->hasTable('exchange_order_market')) {
            return;
        }
        $this->table('exchange_order_market', [
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
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'id',
            ])
            ->addColumn('wallet_from_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'user_id',
            ])
            ->addColumn('wallet_to_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'wallet_from_id',
            ])
            ->addColumn('pair', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'wallet_to_id',
            ])
            ->addColumn('side', 'integer', [
                'null' => false,
                'limit' => '1',
                'after' => 'pair',
            ])
            ->addColumn('amount', 'double', [
                'null' => false,
                'after' => 'side',
            ])
            ->addColumn('status', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'amount',
            ])
            ->addColumn('message', 'string', [
                'null' => true,
                'limit' => 256,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'status',
            ])
            ->addColumn('processed_price', 'double', [
                'null' => false,
                'after' => 'message',
            ])
            ->addColumn('processed_exchange', 'string', [
                'null' => false,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'processed_price',
            ])
            ->addColumn('commission', 'double', [
                'null' => false,
                'default' => '0',
                'after' => 'processed_exchange',
            ])
            ->addColumn('commission_our', 'double', [
                'null' => false,
                'default' => '0',
                'after' => 'commission',
            ])
            ->addColumn('created_at', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'commission_our',
            ])
            ->addColumn('_delete', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'created_at',
            ])
            ->create();

    }
}
