<?php

namespace Admin\serializers;

use Admin\layout\Time;
use Db\Model\ModelSet;
use Db\Where;
use Models\UserLogModel;
use Models\UserModel;

class UserLogSerializer {
    public static function rows(ModelSet $logs) {
        $users = null;
        $user_ids = array_filter(array_unique($logs->column('user_id')), function ($id) {
            return is_integer($id);
        });

        if (!empty($user_ids)) {
            $users = UserModel::select(Where::in('id', $user_ids));
        }

        return $logs->map(function (UserLogModel $log) use ($users) {
            /** @var UserModel|null $user */
            $user = $log->user_id ? $users->getItem($log->user_id) : null;
            return self::row($log, $user);
        });
    }

    public static function row(UserLogModel $log, UserModel $user = null): array {
        return [
            $log->id,
            ($user ? $user->login : '') . " ($log->user_id)",
            $log->action,
            $log->created_at_timestamp ? Time::withParams($log->created_at_timestamp): '',
            $log->extra ? $log->extra->tableColumn() : '',
            $log->extra ? $log->extra->getIp() : '',
            $log->extra ? ($log->extra->getDevice() ? 'Mobile' : 'Desktop') : '',
            $log->extra ? $log->extra->getBrowser() : '',
        ];
    }

    public static function userLogRows(ModelSet $logs) {
        return $logs->map(function (UserLogModel $log) {
            return [
                $log->action,
                Time::withParams($log->created_at_timestamp),
                $log->extra->getBrowser(),
                $log->extra->getDevice() ? 'Mobile' : 'Desktop',
                $log->extra->getIp(),
            ];
        });
    }
}