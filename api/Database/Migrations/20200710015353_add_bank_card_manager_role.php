<?php

use Phinx\Migration\AbstractMigration;

class AddBankCardManagerRole extends AbstractMigration
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
        \Db\Transaction::wrap(function () {
            $role = \Models\UserRoleModel::select(\Db\Where::equal('role_name', \Models\UserRoleModel::BANK_CARDS_MANAGER));
            if ($role->isEmpty()) {
                $role = new \Models\UserRoleModel();
                $role->role_name = \Models\UserRoleModel::BANK_CARDS_MANAGER;
                $role->permissions = '';
                $role->save();
            } else {
                $role = $role->first();
            }

            $permission = \Models\UserPermissionModel::select(\Db\Where::equal('name', \Models\UserPermissionModel::BANK_CARD_MANAGE));
            if ($permission->isEmpty()) {
                $permission = new \Models\UserPermissionModel();
                $permission->name = \Models\UserPermissionModel::BANK_CARD_MANAGE;
                $permission->save();
            }

            if (!in_array(\Models\UserPermissionModel::BANK_CARD_MANAGE, $role->permissionsAsArray())) {
                $permissions = $role->permissionsAsArray();
                $permissions[] = \Models\UserPermissionModel::BANK_CARD_MANAGE;
                $role->permissions = implode(',', $permissions);
                $role->save();
            }
        });
    }
}
