<?php

use Phinx\Migration\AbstractMigration;

class CreateWithdrawalsTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html
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
        $this->table('withdrawals')
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false
            ])
            ->addColumn('amount', 'double')
            ->addColumn('currency', 'string', [
                'limit' => 10
            ])
            ->addColumn('from_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false
            ])
            ->addColumn('from_type', 'integer', [
                'null' => true,
                'limit' => 1,
                'signed' => false
            ])
            ->addColumn('fee', 'double', [
                'null' => true
            ])
            ->addColumn('provider', 'string')
            ->addColumn('status', 'integer', [
                'limit' => 1
            ])
            ->addColumn('bank_code', 'string', [
                'null' => true
            ])->addColumn('external_id', 'string', [
                'null' => true
            ])->addColumn('admin_id', 'integer', [
                'signed' => false,
                'null' => true
            ])->addColumn('reject_message', 'string', [
                'null' => true
            ])->addColumn('account_number', 'string', [
                'null' => true,
            ])->addColumn('account_holder_name', 'string', [
                    'null' => true,
            ])->addColumn('approved_at_timestamp', 'integer', [
                    'null' => true,
                    'limit' => '10',
                    'signed' => false,
            ])->addColumn('created_at_timestamp', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
            ])->addColumn('updated_at_timestamp', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'created_at_timestamp',
            ])->addColumn('deleted_at', 'integer', [
                'null' => true,
                'limit' => '10',
                'signed' => false,
                'after' => 'updated_at_timestamp',
            ])->addIndex(['user_id', 'from_id', 'from_type'])
            ->create();
    }
}
