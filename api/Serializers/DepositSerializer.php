<?php

namespace Serializers;

use Models\DepositModel;

class DepositSerializer {
    public static function listItem(DepositModel $deposit): array {
        return [
            'id' => (int) $deposit->id,
            'days' => (int) $deposit->days,
            'currency' => $deposit->currency,
            'amount' => (double) $deposit->amount,
            'status' => $deposit->status,
            'type' => $deposit->dynamic_percent == 2 ?  'dynamic' : 'static',
        ];
    }
}
