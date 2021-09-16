<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class MarketTraders extends AbstractMigration
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
        if ($this->hasTable('market_traders')) {
            return;
        }
        $this->table('market_traders', [
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
                'identity' => 'enable',
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 256,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('order_size', 'double', [
                'null' => false,
                'after' => 'name',
            ])
            ->addColumn('order_count', 'integer', [
                'null' => false,
                'limit' => '3',
                'after' => 'order_size',
            ])
            ->addColumn('order_spread', 'float', [
                'null' => false,
                'after' => 'order_count',
            ])
            ->addColumn('order_freq', 'integer', [
                'null' => false,
                'limit' => '5',
                'after' => 'order_spread',
            ])
            ->addColumn('expiration_time', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'order_freq',
            ])
            ->addColumn('account', 'integer', [
                'null' => true,
                'limit' => '10',
                'signed' => false,
                'after' => 'expiration_time',
            ])
            ->addColumn('_delete', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => '1',
                'after' => 'account',
            ])
            ->create();

    }
}
