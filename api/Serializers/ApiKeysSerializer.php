<?php

namespace Serializers;

use Models\AppTokenModel;

class ApiKeysSerializer {
    public static function listItem(AppTokenModel $token) {
        $permissions = explode(',', $token->permissions);
        return [
            'id' => (int) $token->id,
            'name' => $token->name,
            'allow_ips' => $token->allow_ips,
            'permission_trading' => in_array('trading', $permissions),
            'permission_withdraw' => in_array('withdraw', $permissions),
            'public_key' => $token->public_key,
        ];
    }
}
