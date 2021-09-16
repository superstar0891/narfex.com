<?php

namespace Modules;

use Db\Model\ModelSet;
use Db\Where;
use Models\LogModel;
use Models\Logs\UserAuthorizeLog;
use Models\UserLogModel;

class LogModule {
    public static function get(int $user_id, string $action): ModelSet {
        return LogModel::rowsToSet(LogModel::queryBuilder()
            ->columns([])
            ->where(Where::and()
                ->set('user_id', Where::OperatorEq, $user_id)
                ->set('action', Where::OperatorEq, $action)
            )
            ->orderBy(['id' => 'DESC'])
            ->limit(10)
            ->select()
        );
    }

    public static function add(int $user_id, string $action) {
        $browser_info = getBrowserInfo();
        $log = new LogModel();
        $log->user_id = $user_id;
        $log->action = $action;
        $log->device = isMobile() ? 1 : 0;
        $log->ip = ipAddress();
        $log->browser = sprintf('%s %s, %s %s',
            $browser_info['platform_name'],
            $browser_info['platform_version'],
            $browser_info['browser_name'],
            $browser_info['browser_version']
        );
        $log->created_at = date('Y-m-d H:i:s');
        $log->save();
    }

    public static function getAuthLogs(int $user_id) {
        $legacy_logs = LogModel::queryBuilder()
            ->columns([])
            ->where(Where::and()
                ->set('user_id', Where::OperatorEq, $user_id)
                ->set('action', Where::OperatorEq, 'auth_signin')
            )
            ->orderBy(['id' => 'DESC'])
            ->limit(10)
            ->select();

        $logs = UserLogModel::queryBuilder()
            ->where(Where::and()
                ->set(Where::equal('action', UserAuthorizeLog::USER_AUTHORIZE_ACTION))
                ->set(Where::equal('user_id', $user_id))
            )
            ->limit(10)
            ->select();

        $logs = array_merge($legacy_logs, $logs);

        // adding created_at_timestamp to legacy logs
        $logs = array_map(function($log){
            if ($log['action'] === 'auth_signin') {
                $log['created_at_timestamp'] = (int) strtotime($log['created_at']);
            }
            return $log;
        }, $logs);

        // filtering logs by date
        array_multisort(array_column($logs, 'created_at_timestamp'), SORT_DESC, $logs);

        // getting only 10 items
        $logs = array_slice($logs, 0, 10);
        $res = [];
        foreach ($logs as $log) {
            if ($log['action'] === 'auth_signin') {
                // if legacy logs
                $ip = $log['ip'];
                $browser = trim($log['browser']) ? $log['browser'] : 'Unknown';
            } else {
                // if current logs
                $extra = json_decode($log['extra'], true);
                $extra = UserLogModel::parseHelper($extra);
                $ip = $extra->getIp();
                $browser = $extra->getBrowser();
            }
            $res[] = [
                'action' => 'auth_signin',
                'created_at' => (int) $log['created_at_timestamp'],
                'browser' => $browser,
                'ip' => $ip,
            ];
        }

        return $res;
    }
}
