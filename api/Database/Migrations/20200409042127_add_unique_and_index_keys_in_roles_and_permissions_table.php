<?php

use Phinx\Migration\AbstractMigration;

class AddUniqueAndIndexKeysInRolesAndPermissionsTable extends AbstractMigration
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
        $this->table('roles')->truncate();
        $this->table('permissions')->truncate();

        $this->table('user_logs')
            ->addIndex(['user_id'])
            ->update();
        $this->table('roles')
            ->addIndex(['role_name'], [
                'unique' => true,
            ])
            ->update();
        $this->table('permissions')
            ->addIndex(['name'], [
                'unique' => true,
            ])
            ->update();

        $role = new \Models\UserRoleModel();
        $role->role_name = 'admin';
        $role->permissions = '';
        $role->save();
    }

}
