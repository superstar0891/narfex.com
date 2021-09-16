<?php

use Phinx\Migration\AbstractMigration;

class CreateWithdrawalModeratorRole extends AbstractMigration
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
            $withdrawal_moderator = \Models\UserRoleModel::select(\Db\Where::equal('role_name', \Models\UserRoleModel::WITHDRAWAL_MODERATOR));
            if ($withdrawal_moderator->isEmpty()) {
                $withdrawal_moderator = new \Models\UserRoleModel();
                $withdrawal_moderator->role_name = \Models\UserRoleModel::WITHDRAWAL_MODERATOR;
                $withdrawal_moderator->permissions = '';
                $withdrawal_moderator->save();
            } else {
                $withdrawal_moderator = $withdrawal_moderator->first();
            }

            $withdrawal_fiat_permission = \Models\UserPermissionModel::select(\Db\Where::equal('name', \Models\UserPermissionModel::WITHDRAWAL_FIAT_PERMISSION));
            if ($withdrawal_fiat_permission->isEmpty()) {
                $withdrawal_fiat_permission = new \Models\UserPermissionModel();
                $withdrawal_fiat_permission->name = \Models\UserPermissionModel::WITHDRAWAL_FIAT_PERMISSION;
                $withdrawal_fiat_permission->save();
            } else {
                $withdrawal_fiat_permission = $withdrawal_fiat_permission->first();
            }

            if (!in_array($withdrawal_fiat_permission->name, $withdrawal_moderator->permissionsAsArray())) {
                $permissions = $withdrawal_moderator->permissionsAsArray();
                $permissions[] = $withdrawal_fiat_permission->name;
                $withdrawal_moderator->permissions = implode(',', $permissions);
                $withdrawal_moderator->save();
            }
        });
    }
}
