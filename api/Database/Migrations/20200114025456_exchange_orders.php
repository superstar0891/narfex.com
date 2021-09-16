<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class ExchangeOrders extends AbstractMigration
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
        if ($this->hasTable('exchange_orders')) {
            return;
        }
        $this->table('exchange_orders', [
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
                'default' => '0',
                'limit' => '10',
                'signed' => false,
                'after' => 'id',
            ])
            ->addColumn('wallet_from_id', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => '10',
                'signed' => false,
                'after' => 'user_id',
            ])
            ->addColumn('wallet_to_id', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => '10',
                'signed' => false,
                'after' => 'wallet_from_id',
            ])
            ->addColumn('amount', 'double', [
                'null' => false,
                'after' => 'wallet_to_id',
            ])
            ->addColumn('price', 'double', [
                'null' => false,
                'after' => 'amount',
            ])
            ->addColumn('pair', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'price',
            ])
            ->addColumn('processed', 'double', [
                'null' => false,
                'after' => 'pair',
            ])
            ->addColumn('available', 'double', [
                'null' => false,
                'after' => 'processed',
            ])
            ->addColumn('commision', 'double', [
                'null' => false,
                'default' => '0',
                'after' => 'available',
            ])
            ->addColumn('side', 'integer', [
                'null' => false,
                'limit' => '1',
                'after' => 'commision',
            ])
            ->addColumn('updated_at', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'side',
            ])
            ->addColumn('created_at', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'updated_at',
            ])
            ->addColumn('_delete', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => '1',
                'after' => 'created_at',
            ])
            ->addColumn('done', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => '1',
                'after' => '_delete',
            ])
            ->addColumn('bot', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => '1',
                'after' => 'done',
            ])
            ->addIndex(['_delete', 'done', 'bot'], [
                'name' => '_delete',
                'unique' => false,
            ])
            ->create();

    }
}
