<?php

use Phinx\Migration\AbstractMigration;

class BankCardOperationTableCreate extends AbstractMigration
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
        $this->table('bank_cards_operations')
            ->addColumn('card_id', 'integer', [
                'null' => false,
                'limit' => '10',
            ])
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'limit' => '10',
            ])
            ->addColumn('operation', 'string', [
                'null' => false,
                'limit' => '32',
            ])
            ->addColumn('amount', 'double', [
                'null' => false,
            ])
            ->addColumn('got_amount', 'double', [
                'null' => true,
            ])
            ->addColumn('status', 'string', [
                'null' => false,
                'limit' => '32',
            ])
            ->addColumn('manager_id', 'integer', [
                'null' => true,
                'limit' => '10',
            ])
            ->addColumn('created_at_timestamp', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
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
            ->addIndex(['operation'])
            ->addIndex(['operation', 'status'])
            ->create();
    }
}
