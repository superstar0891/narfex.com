<?php


namespace Serializers\BalanceHistory;


use Models\SwapModel;
use Models\UserBalanceHistoryModel;

class SwapSerializer {
    public static function serialize(SwapModel $swap): array {
        $type = $swap->to_currency === CURRENCY_FNDR ?
            'buy_token' :
            UserBalanceHistoryModel::OPERATIONS_MAP[UserBalanceHistoryModel::OPERATION_SWAP];

        return [
            'id' => (int) $swap->id,
            'created_at' => (int) $swap->created_at_timestamp,
            'price' => (float) $swap->rate,
            'primary_amount' => (float) $swap->from_amount,
            'primary_currency' => strtolower($swap->from_currency),
            'secondary_amount' => (float) $swap->to_amount,
            'secondary_currency' => strtolower($swap->to_currency),
            'status' => UserBalanceHistoryModel::STATUSES_MAP[(int) $swap->status],
            'type' => $type,
        ];
    }
}
