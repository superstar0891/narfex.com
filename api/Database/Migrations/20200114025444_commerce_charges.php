<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class CommerceCharges extends AbstractMigration
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
        if ($this->hasTable('commerce_charges')) {
            return;
        }
        $this->table('commerce_charges', [
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
            ->addColumn('app_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'id',
            ])
            ->addColumn('invoice_id', 'integer', [
                'null' => true,
                'limit' => '10',
                'signed' => false,
                'after' => 'app_id',
            ])
            ->addColumn('code', 'string', [
                'null' => false,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'invoice_id',
            ])
            ->addColumn('opened', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'code',
            ])
            ->addColumn('expiration', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'opened',
            ])
            ->addColumn('description', 'text', [
                'null' => false,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'expiration',
            ])
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'description',
            ])
            ->addColumn('amount', 'double', [
                'null' => false,
                'after' => 'user_id',
            ])
            ->addColumn('charged', 'double', [
                'null' => false,
                'default' => '0',
                'after' => 'amount',
            ])
            ->addColumn('done', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => '1',
                'after' => 'charged',
            ])
            ->addColumn('commission', 'double', [
                'null' => false,
                'after' => 'done',
            ])
            ->addColumn('currency', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'commission',
            ])
            ->addColumn('rates', 'string', [
                'null' => false,
                'limit' => 2048,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'currency',
            ])
            ->addColumn('btc_address', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'rates',
            ])
            ->addColumn('meta_data', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'btc_address',
            ])
            ->addColumn('closed_at', 'datetime', [
                'null' => true,
                'after' => 'meta_data',
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => false,
                'after' => 'closed_at',
            ])
            ->addColumn('_delete', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => '1',
                'after' => 'created_at',
            ])
            ->create();

    }
}
