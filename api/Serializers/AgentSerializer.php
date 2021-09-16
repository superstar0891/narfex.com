<?php

namespace Serializers;

class AgentSerializer {
    public static function detail($user) {
        return [
            'id' => $user->id,
            'name' => $user->first_name . ' ' . $user->last_name,
            'created_at' => (int) $user->created_at,
        ];
    }
}
