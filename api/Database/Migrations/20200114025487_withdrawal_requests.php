<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class WithdrawalRequests extends AbstractMigration
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
        if ($this->hasTable('withdrawal_requests')) {
            return;
        }
        $this->table('withdrawal_requests', [
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
            ->addColumn('user_address', 'string', [
                'null' => false,
                'limit' => 256,
                'collation' => 'utf8_unicode_ci',
                'encoding' => 'utf8',
                'after' => 'user_id',
            ])
            ->addColumn('amount', 'decimal', [
                'null' => false,
                'precision' => '15',
                'scale' => '8',
                'after' => 'user_address',
            ])
            ->addColumn('currency', 'string', [
                'null' => false,
                'limit' => 16,
                'collation' => 'utf8_unicode_ci',
                'encoding' => 'utf8',
                'after' => 'amount',
            ])
            ->addColumn('to_address', 'string', [
                'null' => false,
                'limit' => 256,
                'collation' => 'utf8_unicode_ci',
                'encoding' => 'utf8',
                'after' => 'currency',
            ])
            ->addColumn('exec_at', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'to_address',
            ])
            ->addColumn('status', 'set', [
                'null' => false,
                'limit' => 27,
                'after' => 'exec_at',
                'values' => ['pending', 'done', 'rejected', 'boost']
            ])
            ->addColumn('updated_at', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'status',
            ])
            ->addColumn('created_at', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'updated_at',
            ])
            ->addColumn('created_at_timestamp', 'integer', [
                'null' => true,
                'limit' => '10',
                'after' => 'created_at',
            ])
            ->addColumn('updated_at_timestamp', 'integer', [
                'null' => true,
                'limit' => '10',
                'after' => 'created_at_timestamp',
            ])
            ->addColumn('deleted_at', 'integer', [
                'null' => true,
                'limit' => '10',
                'after' => 'updated_at_timestamp',
            ])
            ->create();

    }
}
