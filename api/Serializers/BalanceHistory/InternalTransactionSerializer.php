<?php

namespace Serializers\BalanceHistory;

use Models\InternalTransactionModel;

class InternalTransactionSerializer {
    public static function listItem(InternalTransactionModel $operation): array {
        return [
            'id' => (int) $operation->id,
            'created_at' => (int) $operation->created_at_timestamp,
            'currency' => $operation->currency,
            'amount' => (double) $operation->amount,
            'from' => InternalTransactionModel::$categories[$operation->from_category],
            'to' => InternalTransactionModel::$categories[$operation->to_category],
            'type' => 'internal_transaction',
        ];
    }
}
