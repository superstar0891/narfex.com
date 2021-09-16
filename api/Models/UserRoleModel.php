<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Model;
use Engine\Request;
use Models\Logs\CrudRoleLog;
use Modules\RolesModule;
use Modules\UserLogModule;

/**
 * @property string role_name
 * @property string permissions
 */
class UserRoleModel extends Model {
    const ADMIN_ROLE = 'admin',
        USER_ROLE = 'user',
        TRANSLATOR_ROLE = 'translator',
        AGENT_ROLE = 'agent',
        WITHDRAWAL_MODERATOR = 'withdrawal_moderator',
        BANK_CARDS_MANAGER = 'bank_cards_manager',
        AGENT_BITCOINOVNET = 'agent_bitcoinovnet';

    protected static $table_name = 'roles';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'role_name' => CharField::init()->setLength(256),
            'permissions' => CharField::init()->setLength(256),
        ];
    }

    protected function onCreate() {
        if ($user = Request::getUser()) {
            UserLogModule::addLog(
                CrudRoleLog::ADD_ROLE_ACTION,
                new CrudRoleLog(['role_id' => $this->id, 'role_name' => $this->role_name]),
                true,
                $user
            );
        }
    }

    protected function onDelete() {
        RolesModule::onDelete($this->role_name);
        if ($user = Request::getUser()) {
            UserLogModule::addLog(
                CrudRoleLog::DELETE_ROLE_ACTION,
                new CrudRoleLog(['role_id' => $this->id, 'role_name' => $this->role_name]),
                true,
                $user
            );
        }
    }
    public function permissionsAsArray(): array {
        return array_filter(
            array_map('trim', explode(',', $this->permissions)), function ($permission) {
                return boolval($permission);
            }
        );
    }
}
