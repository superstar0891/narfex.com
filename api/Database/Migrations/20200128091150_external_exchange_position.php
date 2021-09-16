<?php

use Phinx\Migration\AbstractMigration;

class ExternalExchangePosition extends AbstractMigration
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
    public function change() {
        $this->table('external_exchange_position', [
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
                'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'after' => 'id',
            ])
            ->addColumn('exchange', 'string', [
                'null' => false,
                'limit' => 32,
                'after' => 'user_id',
            ])
            ->addColumn('amount', 'double', [
                'null' => false,
                'after' => 'exchange',
            ])
            ->addColumn('currency', 'string', [
                'null' => false,
                'limit' => 12,
                'after' => 'amount',
            ])
            ->addColumn('rate', 'double', [
                'null' => false,
                'after' => 'currency',
            ])
            ->addColumn('real_rate', 'double', [
                'null' => false,
                'after' => 'rate',
            ])
            ->addColumn('close_rate', 'double', [
                'null' => false,
                'after' => 'real_rate',
            ])
            ->addColumn('status', 'string', [
                'null' => false,
                'limit' => 32,
                'after' => 'close_rate',
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
            ->addIndex(['status'], [
                'name' => 'status',
                'unique' => false,
            ])
            ->addIndex(['exchange'], [
                'name' => 'exchange',
                'unique' => false,
            ])
            ->create();
    }
}
