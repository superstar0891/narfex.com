<?php


namespace Serializers\BalanceHistory;


use Models\RefillModel;
use Models\UserBalanceHistoryModel;

class RefillSerializer {
    public static function serialize(RefillModel $refill): array {
        return [
            'bank_code' => $refill->bank_code,
            'created_at' => (int) $refill->created_at_timestamp,
            'fee' => (int) $refill->fee,
            'id' => (int) $refill->id,
            'amount' => (float) $refill->amount,
            'currency' => strtolower($refill->currency),
            'type' => UserBalanceHistoryModel::OPERATIONS_MAP[UserBalanceHistoryModel::OPERATION_REFILL],
            'provider' => $refill->provider,
            'status' => 'confirmed',
        ];
    }
}
