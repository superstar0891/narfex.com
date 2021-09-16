<?php

use Phinx\Migration\AbstractMigration;

class UserWithdrawalLimitsTable extends AbstractMigration
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
        $this->table('user_withdrawal_limits')
            ->addColumn('user_id', 'integer', [
                'signed' => false,
                'null' => false,
                'limit' => '10',
            ])
            ->addColumn('amount', 'float', [
                'default' => 0,
                'null' => false,
                'limit' => '10',
                'after' => 'created_at',
            ])
            ->addColumn('started_at', 'integer', [
                'signed' => false,
                'null' => true,
                'limit' => '10',
            ])
            ->addColumn('created_at_timestamp', 'integer', [
                'null' => true,
                'limit' => '10',
            ])
            ->addColumn('updated_at_timestamp', 'integer', [
                'signed' => false,
                'null' => true,
                'limit' => '10',
            ])
            ->addColumn('deleted_at', 'integer', [
                'signed' => false,
                'null' => true,
                'limit' => '10',
            ])
            ->addIndex('user_id', [
                'unique' => true
            ])
            ->create();
    }
}
