<?php

namespace Modules;

use Db\Where;
use Models\UserModel;
use Models\UserRoleModel;

class PermissionsModule {
    public static function onDelete($deleted_permission): void {
        self::syncUsers($deleted_permission);
        self::syncRoles($deleted_permission);
    }

    private static function syncUsers($deleted_permission) {
        $users = UserModel::select(
            Where::and()->set('permissions', Where::OperatorLike, '%' . $deleted_permission . '%')
        );

        if (!$users->isEmpty()) {
            foreach ($users as $user) {
                /** @var UserModel $user */
                if (!$user->permissions) {
                    continue;
                }
                $user->permissions = implode(
                    ',',
                    array_filter($user->permissionsAsArray(), function ($item) use ($deleted_permission) {
                        return $item != $deleted_permission;
                    })
                );
                $user->save();
            }
        }
    }

    private static function syncRoles($deleted_permission) {
        $roles = UserRoleModel::select(
            Where::and()->set('permissions', Where::OperatorLike, '%' . $deleted_permission . '%')
        );

        if (!$roles->isEmpty()) {
            foreach ($roles as $role) {
                /** @var UserRoleModel $role */
                if (!$role->permissions) {
                    continue;
                }
                $role->permissions = implode(
                    ',',
                    array_filter($role->permissionsAsArray(), function ($item) use ($deleted_permission) {
                        return $item !== $deleted_permission;
                    })
                );
                $role->save();
            }
        }
    }

}
