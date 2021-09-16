<?php


namespace Serializers\BalanceHistory;


use Models\UserBalanceHistoryModel;
use Models\WithdrawalModel;

class WithdrawalSerializer {
    public static function serialize(WithdrawalModel $withdrawal): array {
        return [
            'account_number' => $withdrawal->account_number,
            'account_holder_name' => $withdrawal->account_holder_name,
            'bank_code' => $withdrawal->bank_code,
            'created_at' => (int) $withdrawal->created_at_timestamp,
            'fee' => (float) $withdrawal->fee,
            'id' => (int) $withdrawal->id,
            'status' => UserBalanceHistoryModel::STATUSES_MAP[$withdrawal->status],
            'amount' => (float) $withdrawal->amount,
            'currency' => strtolower($withdrawal->currency),
            'type' => UserBalanceHistoryModel::OPERATIONS_MAP[UserBalanceHistoryModel::OPERATION_WITHDRAWAL]
        ];
    }
}
