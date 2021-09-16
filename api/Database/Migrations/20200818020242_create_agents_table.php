<?php

use Phinx\Migration\AbstractMigration;

class CreateAgentsTable extends AbstractMigration
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
        \Db\Transaction::wrap(function () {
            if (\Models\UserPermissionModel::select(\Db\Where::equal('name', 'agent_bitcoinovnet'))->isEmpty()) {
                $permission = new \Models\UserPermissionModel();
                $permission->name = 'agent_bitcoinovnet';
                $permission->save();
            }

            $this->table('site_settings')
                ->addColumn('bitcoinovnet_agent_max_percent', 'double', [
                    'null' => false,
                    'default' => 30,
                ])
                ->update();

            $this->table('agents')
                ->addColumn('user_id', 'integer', [
                    'null' => false,
                    'limit' => '10',
                    'signed' => false,
                ])
                ->addColumn('platform', 'string', [
                    'null' => false,
                    'limit' => 32,
                    'collation' => 'utf8mb4_general_ci',
                    'encoding' => 'utf8mb4',
                ])
                ->addColumn('promo_code_percent_profit', 'decimal', [
                    'null' => false,
                    'precision' => '5',
                    'scale' => '2',
                    'default' => 50
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
                ])
                ->addColumn('deleted_at', 'integer', [
                    'null' => true,
                    'limit' => '10',
                    'signed' => false,
                ])
                ->addIndex('user_id')
                ->addIndex(['user_id', 'platform'], ['unique' => true])
                ->create();
        });
    }
}
