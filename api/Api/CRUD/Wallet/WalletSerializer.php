<?php

namespace Api\CRUD\Wallet;

class WalletSerializer {
    public static function detail($wallet) {
        return [
            'id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'address' => $wallet->address,
            'created_at' => $wallet->created_at,
        ];
    }
}
