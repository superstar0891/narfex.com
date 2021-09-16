<?php

use Phinx\Migration\AbstractMigration;

class Transfers extends AbstractMigration
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
        if ($this->hasTable('transfers')) {
            return;
        }
        $this->table('transfers', [
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
            ->addColumn('from_user_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'id',
            ])
            ->addColumn('to_user_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'from_user_id',
            ])
            ->addColumn('from_wallet_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'to_user_id',
            ])
            ->addColumn('to_wallet_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'from_wallet_id',
            ])
            ->addColumn('amount', 'double', [
                'null' => false,
                'after' => 'to_wallet_id',
            ])
            ->addColumn('currency', 'string', [
                'null' => false,
                'limit' => 16,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'amount',
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => false,
                'after' => 'currency',
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
