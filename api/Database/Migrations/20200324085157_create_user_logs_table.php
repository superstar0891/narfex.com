<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class CreateUserLogsTable extends AbstractMigration {
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
    public function change() {
        $this->table('user_logs')
            ->addColumn('user_id', 'integer', [
                'null' => true,
                'limit' => '11',
                'after' => 'id',
            ])
            ->addColumn('action', 'string', [
                'null' => false,
                'limit' => 256,
            ])
            ->addColumn('admin', 'boolean', [
                'limit' => MysqlAdapter::INT_TINY,
                'null' => false
            ])
            ->addColumn('extra', 'text', [
                'null' => true,
                'limit' => MysqlAdapter::TEXT_LONG,
            ])
            ->addColumn('created_at_timestamp', 'integer', [
                'null' => true,
                'limit' => '10',
            ])
            ->addColumn('updated_at_timestamp', 'integer', [
                'null' => true,
                'limit' => '10',
            ])
            ->addColumn('deleted_at', 'integer', [
                'null' => true,
                'limit' => '10',
            ])
            ->create();
    }
}
