<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class ExchangeOrderPartialLimit extends AbstractMigration
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
        if ($this->hasTable('exchange_order_partial_limit')) {
            return;
        }
        $this->table('exchange_order_partial_limit', [
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
            ->addColumn('order_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'user_id',
            ])
            ->addColumn('pair', 'string', [
                'null' => false,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'order_id',
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
            ->addColumn('price', 'double', [
                'null' => false,
                'after' => 'amount',
            ])
            ->addColumn('exchange_price', 'double', [
                'null' => false,
                'after' => 'price',
            ])
            ->addColumn('exchange_name', 'string', [
                'null' => false,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'exchange_price',
            ])
            ->addColumn('created_at', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'exchange_name',
            ])
            ->create();

    }
}
