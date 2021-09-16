<?php

use Phinx\Migration\AbstractMigration;

class BankCardTableCreate extends AbstractMigration
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
        $this->table('bank_cards')
            ->addColumn('added_by', 'integer', [
                'null' => false,
                'limit' => '10',
            ])
            ->addColumn('bank', 'string', [
                'null' => false,
                'limit' => '32',
            ])
            ->addColumn('number', 'string', [
                'null' => false,
                'limit' => '20',
            ])
            ->addColumn('holder_name', 'string', [
                'null' => false,
                'limit' => '64',
            ])
            ->addColumn('expiration_date', 'string', [
                'null' => false,
                'limit' => '5',
            ])
            ->addColumn('code', 'integer', [
                'null' => false,
                'limit' => '4',
            ])
            ->addColumn('booked_by', 'integer', [
                'null' => true,
                'limit' => '10',
            ])
            ->addColumn('book_expiration', 'integer', [
                'null' => true,
                'limit' => '10',
            ])
            ->addColumn('active', 'integer', [
                'null' => false,
                'limit' => '1',
            ])
            ->addColumn('mobile_number', 'string', [
                'null' => false,
                'limit' => '20',
            ])
            ->addColumn('balance', 'double', [
                'null' => false,
                'default' => 0,
            ])
            ->addColumn('managed_by', 'integer', [
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
            ->addIndex(['bank', 'booked_by'])
            ->create();
    }
}
