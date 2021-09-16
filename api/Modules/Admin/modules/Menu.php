<?php

namespace Admin\modules;

use Db\Where;
use Models\UserModel;
use Models\UserPermissionModel;
use Models\UserRoleModel;
use Serializers\AdminSerializer;

class Menu {
    /** @var array */
    private $items = [];
    /** @var UserModel */
    private $user;
    /** @var array */
    private $user_permissions;

    public function addItem(string $title, array $params = [], array $sub_items = []) {
        $added_sub_items = [];
        foreach ($sub_items as $item) {
            $params = isset($item['params']) ? $item['params'] : [];
            $permissions = isset($item['permissions']) ? $item['permissions'] : [];
            $item = $this->addSubItem($item['title'], $params, $permissions);
            if (is_null($item)) {
                continue;
            }
            $added_sub_items[] = $item;
        }

        if (!empty($added_sub_items)) {
            $key = count($this->items);
            $this->items[$key] = ['title' => $title, 'params' => $params, 'items' => $added_sub_items];
        }
    }

    private function permitted(array $params): bool {
        $permissions = [];
        if (isset($params['action'])) {
            if (isset($params['action']['params']['page'])) {
                $class_name = $class_name = "\Admin\modules\\" . $params['action']['params']['page'];
                $inst = new $class_name();
                $permissions = $inst::$permission_list;
            }
        }

        if (
            (empty($inst::$permission_list) && !$this->user->isAdmin())
            ||
            !$this->user->hasPermissions($permissions)
        ) {
            return false;
        }

        return true;
    }

    private function addSubItem(string $title, array $params = [], array $permissions = []): ?array {
        if (!$this->permitted($params)) {
            return null;
        }

        return ['title' => $title, 'params' => $params];
    }

    public function getItems(): array {
        $items = [];
        foreach ($this->items as $item) {
            if (empty($item['items']) && empty($item['params'])) {
                continue;
            }

            $sub_items = [];
            if ($item['items']) {
                foreach ($item['items'] as $sub_item) {
                    $sub_items[] = AdminSerializer::menuItem($sub_item['title'], $sub_item['params']);
                }
            }

            $items[] = AdminSerializer::menuItem($item['title'], $item['params'], $sub_items);
        }

        return $items;
    }

    public function __construct(UserModel $user) {
        $this->user = $user;

        $user_permissions = [];
        $permission_ids = [];
        if (!empty($role_ids = explode(',', $this->user->roles))) {

            $roles = UserRoleModel::select(Where::in('id', $role_ids));

            foreach ($roles as $role) {
                /** @var UserRoleModel $role */
                $permission_ids = array_merge($permission_ids, explode(',', $role->permissions));
            }
        }

        $permission_ids = array_merge($permission_ids, explode(',', $user->permissions));

        if (!empty($permission_ids)) {
            $user_permissions = UserPermissionModel::select(
                Where::in('id', $permission_ids)
            )->map(function (UserPermissionModel $permission_model) {
                return $permission_model->name;
            });
        }

        $this->user_permissions = $user_permissions;
    }

}
