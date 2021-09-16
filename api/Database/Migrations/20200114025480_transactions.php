<?php

use Phinx\Migration\AbstractMigration;

class Transactions extends AbstractMigration
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
        if ($this->hasTable('transactions')) {
            return;
        }
        $this->table('transactions', [
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
            ->addColumn('txid', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('amount', 'double', [
                'null' => false,
                'after' => 'txid',
            ])
            ->addColumn('currency', 'string', [
                'null' => false,
                'limit' => 16,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'amount',
            ])
            ->addColumn('confirmations', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => '10',
                'signed' => false,
                'after' => 'currency',
            ])
            ->addColumn('status', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'confirmations',
            ])
            ->addColumn('category', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'status',
            ])
            ->addColumn('user_id', 'integer', [
                'null' => true,
                'limit' => '10',
                'after' => 'category',
            ])
            ->addColumn('user_wallet', 'string', [
                'null' => true,
                'limit' => 256,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'user_id',
            ])
            ->addColumn('wallet_to', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'user_wallet',
            ])
            ->addColumn('commerce_tx', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => '1',
                'after' => 'wallet_to',
            ])
            ->addColumn('commerce_app_id', 'integer', [
                'null' => true,
                'limit' => '10',
                'signed' => false,
                'after' => 'commerce_tx',
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => false,
                'after' => 'commerce_app_id',
            ])
            ->addColumn('updated_at', 'datetime', [
                'null' => false,
                'after' => 'created_at',
            ])
            ->addColumn('created_at_timestamp', 'integer', [
                'null' => true,
                'limit' => '10',
                'after' => 'updated_at',
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
