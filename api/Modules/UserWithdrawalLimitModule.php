<?php

namespace Modules;

use Models\UserWithdrawalLimitModel;

class UserWithdrawalLimitModule {
    public static function create(int $user_id, float $amount) {
        $limit = new UserWithdrawalLimitModel();
        $limit->user_id = $user_id;
        $limit->amount = $amount;
        $limit->started_at = time();
        $limit->save();

        return $limit;
    }

    public static function updateLimit(UserWithdrawalLimitModel $limit) {
        $started_at = $limit->started_at;
        $now = time();
        $diff = floatval(($now - $started_at)/3600);
        if ($diff >= UserWithdrawalLimitModel::LIMIT_UPDATE_HOURS) {
            $limit->started_at = time();
            $limit->amount = 0;
            $limit->save();
        }

        return $limit;
    }
}