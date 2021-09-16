<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Model;

/**
 * @property string name
 */
class PermissionModel extends Model {
    //legacy table
    protected static $table_name = 'user_permissions';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'name' => CharField::init()->setLength(256),
        ];
    }

    public static function permissionName(string $model, $method = null) {
        $all_methods_permissions = [
            'create' => "model__{$model}__create",
            'read' => "model__{$model}__read",
            'list' => "model__{$model}__list",
            'update' => "model__{$model}__update",
            'delete' => "model__{$model}__delete",
        ];

        if ($method === null) {
            return $all_methods_permissions;
        }

        return array_key_exists($method, $all_methods_permissions) ? $all_methods_permissions[$method] : null;
    }

}
