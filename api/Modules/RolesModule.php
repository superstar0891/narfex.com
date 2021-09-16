<?php

namespace Modules;

use Db\Where;
use Models\UserModel;
use Models\UserRoleModel;

class RolesModule {
    public static function getAdminRoleId(): int {
        /** @var UserRoleModel|null $admin_role */
        $admin_role = UserRoleModel::select(Where::equal('role_name', 'admin'))->first();
        if (is_null($admin_role)) {
            throw new \Exception('Could not find role admin');
        }
        return $admin_role->id;
    }

    public static function onDelete(string $deleted_role): void {
        self::syncUsers($deleted_role);
    }

    private static function syncUsers(string $deleted_role): void {
        $users = UserModel::select(
            Where::and()->set('roles', Where::OperatorLike, '%' . $deleted_role . '%')
        );

        if (!$users->isEmpty()) {
            foreach ($users as $user) {
                /** @var UserModel $user */
                if (!$user->roles) {
                    continue;
                }
                $user->roles = implode(
                    ',',
                    array_filter($user->rolesAsArray(), function ($item) use ($deleted_role) {
                        return $item != $deleted_role;
                    })
                );
                $user->save();
            }
        }
    }

}