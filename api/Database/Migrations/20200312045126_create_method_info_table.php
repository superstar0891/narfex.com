<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class CreateMethodInfoTable extends AbstractMigration
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
        $this->table('methods_info')
            ->addColumn('method_key', 'string', [
                'null' => false,
                'limit' => '150',
            ])
            ->addColumn('lang', 'string', [
                'null' => false,
                'limit' => '2',
            ])
            ->addColumn('description', 'text', [
                'null' => true,
                'limit' => MysqlAdapter::TEXT_LONG,
            ])
            ->addColumn('result', 'text', [
                'null' => true,
                'limit' => MysqlAdapter::TEXT_LONG,
            ])
            ->addColumn('result_example', 'text', [
                'null' => true,
                'limit' => MysqlAdapter::TEXT_LONG,
            ])
            ->addColumn('param_descriptions', 'text', [
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
            ->addIndex(['method_key', 'lang'], [
                'unique' => true,
            ])
            ->create();
    }
}
