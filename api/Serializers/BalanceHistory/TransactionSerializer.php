<?php


namespace Serializers\BalanceHistory;


use Models\TransactionModel;
use Models\UserBalanceHistoryModel;
use Models\WithdrawalRequest;

class TransactionSerializer {
    public static function serialize(TransactionModel $transaction): array {
        $required_confirmations = 2;
        if ($transaction->currency === 'eth') {
            $required_confirmations = 8;
        }
        $transaction_type = $transaction->category;
        return [
            'address' => $transaction->wallet_to,
            'txid' => $transaction->txid,
            'amount' => (double) $transaction->amount,
            'fee' => $transaction->isReceive() ?
                0 :
                (double) KERNEL_CONFIG['wallet']['withdraw_limits'][$transaction->currency]['fee'],
            'confirmations' => (int) $transaction->confirmations,
            'created_at' => (int) $transaction->created_at_timestamp
                ?:
                (is_null($transaction->created_at) ? '' : strtotime($transaction->created_at)),
            'currency' => $transaction->currency,
            'id' => (int) $transaction->id,
            'required_confirmations' => $required_confirmations,
            'status' => $transaction->isCancelled() ?
                TransactionModel::STATUS_CANCELED :
                ($transaction->confirmations > 1 ? 'done' : 'pending'),
            'type' => "transaction_{$transaction_type}",
            'transaction_state' => 'transaction'
        ];
    }

    public static function withdrawalRequestListItem(WithdrawalRequest $request): array {
        $required_confirmations = 2;
        if ($request->currency === CURRENCY_ETH) {
            $required_confirmations = 8;
        }

        return [
            'address' => $request->to_address,
            'amount' => (double) $request->amount,
            'fee' => (double) KERNEL_CONFIG['wallet']['withdraw_limits'][$request->currency]['fee'],
            'confirmations' => (int) 0,
            'created_at' => (int) $request->created_at_timestamp,
            'currency' => $request->currency,
            'id' => (int) -$request->id,
            'required_confirmations' => $required_confirmations,
            'status' => 'pending',
            'type' => 'transaction_send',
            'transaction_state' => 'request'
        ];
    }
}
