<?php

namespace Core\Services\Log;

use Models\Logs\AssignUserPermissionLog;
use Models\Logs\AssignUserRoleLog;
use Models\UserModel;
use Models\UserPermissionModel;
use Models\UserRoleModel;
use Modules\UserLogModule;

class UserRoleLog {

    public static function log(UserModel $user, array $new_roles, array $new_permissions, bool $is_admin, ?UserModel $admin = null): void {
        [$added_roles, $deleted_roles] = self::getDiffValues(
            $user->rolesAsArray(),
            $new_roles
        );

        [$added_permissions, $deleted_permissions] = self::getDiffValues(
            $user->permissionsAsArray(),
            $new_permissions
        );

        foreach ($added_roles as $role_name) {
            UserLogModule::addLog(
                AssignUserRoleLog::ASSIGN_ROLE_ACTION,
                new AssignUserRoleLog([
                    'role_id' => self::getRoleByName($role_name)->id,
                    'user_id' => $user->id,
                    'role_name' => $role_name,
                ]),
                $is_admin,
                $admin
            );
        }

        foreach ($deleted_roles as $role_name) {
            UserLogModule::addLog(
                AssignUserRoleLog::REMOVE_ROLE_ACTION,
                new AssignUserRoleLog([
                    'role_id' => self::getRoleByName($role_name)->id,
                    'role_name' => $role_name,
                    'user_id' => $user->id,
                ]),
                $is_admin,
                $admin
            );
        }

        foreach ($added_permissions as $permission_name) {
            UserLogModule::addLog(
                AssignUserPermissionLog::ASSIGN_PERMISSION_ACTION,
                new AssignUserPermissionLog([
                    'permission_id' => self::getPermissionByName($permission_name)->id,
                    'permission_name' => $permission_name,
                    'user_id' => $user->id,
                ]),
                $is_admin,
                $admin
            );
        }

        foreach ($deleted_permissions as $permission_name) {
            UserLogModule::addLog(
                AssignUserPermissionLog::REMOVE_PERMISSION_ACTION,
                new AssignUserPermissionLog([
                    'permission_id' => self::getPermissionByName($permission_name)->id,
                    'permission_name' => $permission_name,
                    'user_id' => $user->id
                ]),
                $is_admin,
                $admin
            );
        }
    }

    private static function getDiffValues(array $old_items, array $new_items) {
        $added = array_filter($new_items, function ($item) use ($old_items) {
            return !in_array($item, $old_items, true);
        });
        $deleted = array_filter($old_items, function ($item) use ($new_items) {
            return !in_array($item, $new_items, true);
        });

        return [$added, $deleted];
    }

    public static function getRoleByName(string $role_name): ?UserRoleModel {
        $result = null;
        foreach (getRoles() as $role) {
            /** @var UserRoleModel $role */
            if ($role_name === $role->role_name) {
                $result = $role;
                break;
            }
        }

        return $result;
    }

    private static function getPermissionByName(string $permission_name): ?UserPermissionModel {
        $result = null;
        foreach (getPermissions() as $permission) {
            /** @var UserPermissionModel $role */
            if ($permission_name === $permission->name) {
                $result = $permission;
                break;
            }
        }

        return $result;
    }
}
