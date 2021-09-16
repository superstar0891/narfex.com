<?php

namespace Modules;

use Db\Where;
use Models\AppTokenModel;
use Models\UserModel;
use Models\WithdrawDisabledModel;

class UserModule {
    public static function isWithdrawDisabled($user) {
        return !WithdrawDisabledModel::select(Where::equal('user_id', $user->id))->isEmpty();
    }

    public static function removeAccessTokens(UserModel $user, ?int $app_id = null) {
        $where = Where::and()
            ->set(Where::equal('owner_id', $user->id));

        if ($app_id) {
            $where->set(Where::equal('app_id', $app_id));
        }

        $tokens = AppTokenModel::select($where);

        if (!$tokens->isEmpty()) {
            $tokens->delete();
        }
    }
}
