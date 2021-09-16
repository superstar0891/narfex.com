<?php

namespace Serializers;

use Models\PaymentModel;
use Models\WalletModel;

class WithdrawalSerializer {
    public static function listItem(PaymentModel $payment, WalletModel $wallet) {
        return [
            'amount' => (double) $payment->amount,
            'created_at' => (int) strtotime($payment->created_at),
            'status' => $payment->status,
            'currency' => strtolower($wallet->currency),
        ];
    }
}
