<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class CreateUserBalanceHistoriesTable extends AbstractMigration
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
        $this->table('user_balance_histories')
            ->addColumn('from_user_id', 'integer', [
                'signed' => false,
                'null' => true,
                'limit' => MysqlAdapter::INT_REGULAR
            ])
            ->addColumn('to_user_id', 'integer', [
                'signed' => false,
                'null' => true,
                'limit' => MysqlAdapter::INT_REGULAR
            ])
            ->addColumn('from_type', 'integer', [
                'null' => true,
                'limit' => 1,
                'signed' => false
            ])
            ->addColumn('from_id', 'integer', [
                'signed' => false,
                'null' => true,
                'limit' => MysqlAdapter::INT_REGULAR
            ])
            ->addColumn('to_type', 'integer', [
                'null' => true,
                'limit' => 1,
                'signed' => false
            ])
            ->addColumn('to_id', 'integer', [
                'signed' => false,
                'null' => true,
                'limit' => MysqlAdapter::INT_REGULAR
            ])
            ->addColumn('from_amount', 'double', [
                'null' => true,
            ])
            ->addColumn('to_amount', 'double', [
                'null' => true,
            ])
            ->addColumn('from_currency', 'string', [
                'null' => true,
            ])
            ->addColumn('to_currency', 'string', [
                'null' => true,
            ])
            ->addColumn('operation', 'integer', [
                'null' => true,
                'limit' => 2
            ])
            ->addColumn('object_id', 'integer', [
                'signed' => false,
                'null' => true,
                'limit' => MysqlAdapter::INT_REGULAR
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
            ->addIndex(['from_user_id', 'to_user_id', 'operation'])
            ->addIndex(['from_type', 'from_id', 'to_type', 'to_id'])
            ->create();
    }
}
