<?php

namespace Modules;

use Models\Logs\LogHelper;
use Models\UserLogModel;
use Models\UserModel;

class UserLogModule {
    public static function addLog(
        string $action,
        LogHelper $log_extra,
        bool $admin,
        UserModel $user = null): UserLogModel {
        $log = new UserLogModel();

        $log->user_id = !is_null($user) ? $user->id : null;
        $log->action = $action;
        $log->extra = $log_extra->toJson();
        $log->admin = $admin;
        $log->save();

        return $log;
    }

}
