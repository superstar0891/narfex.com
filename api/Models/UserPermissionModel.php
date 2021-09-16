<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Model;
use Engine\Request;
use Models\Logs\CrudPermissionLog;
use Modules\PermissionsModule;
use Modules\UserLogModule;

/**
 * @property string name
 */
class UserPermissionModel extends Model {
    const DOCS_EDITOR_PERMISSION = 'docs_editor',
        WITHDRAWAL_FIAT_PERMISSION = 'withdrawal_fiat',
        BANK_CARD_MANAGE = 'bank_card_manage',
        HEDGING_STACKS = 'hedging_stacks',
        HEDGING = 'hedging',
        AGENT_BITCOINOVNET = 'agent_bitcoinovnet',
        ADMIN_BITCOINOVNET = 'admin_bitcoinovnet',
        REVIEW_BITCOINOVNET = 'review_bitcoinovnet',
        RESERVATION_BITCOINOVNET = 'reservation_bitcoinovnet';

    protected static $table_name = 'permissions';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'name' => CharField::init()->setLength(256),
        ];
    }

    protected function onUpdate() {
        if ($user = Request::getUser()) {
            UserLogModule::addLog(
                CrudPermissionLog::UPDATE_PERMISSION_ACTION,
                new CrudPermissionLog([
                    'permission_id' => $this->id,
                    'permission_name' => $this->name,
                    'old_permission_name' => static::get($this->id)->name,
                ]),
                true,
                $user
            );
        }
    }

    public function onDelete() {
        PermissionsModule::onDelete($this->id);
        if ($user = Request::getUser()) {
            UserLogModule::addLog(
                CrudPermissionLog::DELETE_PERMISSION_ACTION,
                new CrudPermissionLog(['permission_id' => $this->id, 'permission_name' => $this->name]),
                true,
                $user
            );
        }
    }

    public function onCreate() {
        if ($user = Request::getUser()) {
            UserLogModule::addLog(
                CrudPermissionLog::ADD_PERMISSION_ACTION,
                new CrudPermissionLog(['permission_id' => $this->id, 'permission_name' => $this->name]),
                true,
                $user
            );
        }
    }

}
