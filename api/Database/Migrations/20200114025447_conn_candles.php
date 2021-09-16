<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class ConnCandles extends AbstractMigration
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
        if ($this->hasTable('conn_candles')) {
            return;
        }
        $this->table('conn_candles', [
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
            ->addColumn('exchange', 'string', [
                'null' => false,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('pair', 'string', [
                'null' => false,
                'limit' => 16,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'exchange',
            ])
            ->addColumn('time_from', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'pair',
            ])
            ->addColumn('time_to', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'time_from',
            ])
            ->addColumn('low', 'double', [
                'null' => false,
                'after' => 'time_to',
            ])
            ->addColumn('high', 'double', [
                'null' => false,
                'after' => 'low',
            ])
            ->addColumn('open_price', 'double', [
                'null' => false,
                'default' => '-1',
                'after' => 'high',
            ])
            ->addColumn('close_price', 'double', [
                'null' => false,
                'default' => '-1',
                'after' => 'open_price',
            ])
            ->addColumn('created_at', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'close_price',
            ])
            ->addIndex(['exchange', 'pair', 'time_from'], [
                'name' => 'exchange',
                'unique' => false,
            ])
            ->create();

    }
}
