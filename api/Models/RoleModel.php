<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Model;

/**
 * @property string role_name
 * @property string permissions
 */
class RoleModel extends Model {
    //legacy table
    protected static $table_name = 'user_roles';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'role_name' => CharField::init()->setLength(256),
            'permissions' => CharField::init()->setLength(256),
        ];
    }

}
