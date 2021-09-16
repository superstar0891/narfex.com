<?php

namespace Serializers;

class BalanceSerializer {
    public static function listItem($balance) {
        return [
            'id' => (int) $balance->id,
            'amount' => (double) $balance->amount,
            'currency' => strtolower($balance->currency),
        ];
    }
}
