<?php

use Phinx\Migration\AbstractMigration;

class Payments extends AbstractMigration
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
        if ($this->hasTable('payments')) {
            return;
        }
        $this->table('payments', [
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
                'after' => 'id',
            ])
            ->addColumn('wallet_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'after' => 'user_id',
            ])
            ->addColumn('wallet_address', 'string', [
                'null' => true,
                'limit' => 256,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'wallet_id',
            ])
            ->addColumn('status', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_general_ci',
                'encoding' => 'utf8mb4',
                'after' => 'wallet_address',
            ])
            ->addColumn('amount', 'double', [
                'null' => false,
                'after' => 'status',
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => false,
                'after' => 'amount',
            ])
            ->addColumn('_delete', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => '1',
                'after' => 'created_at',
            ])
            ->addColumn('created_at_timestamp', 'integer', [
                'null' => true,
                'limit' => '10',
                'after' => '_delete',
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
