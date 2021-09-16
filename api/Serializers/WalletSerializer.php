<?php

namespace Serializers;

use Models\WalletModel;
use Modules\FiatWalletModule;

class WalletSerializer {
    public static function listItem(WalletModel $wallet): array {
        return [
            'id' => (int) $wallet->id,
            'amount' => (double) positive($wallet->amount),
            'currency' => strtolower($wallet->currency),
            'align' => (double) positive($wallet->alignAmount('btc')),
            'status' => $wallet->status,
            'address' => $wallet->address,
            'to_usd' => (double) $wallet->toUSD(),
            'to_btc' => (double) $wallet->toBTC(),
            'is_saving_enabled' => (bool) $wallet->saving_enabled,
            'has_history' => (bool) $wallet->has_history,
            'is_saving_available' => (bool) $wallet->isSavingAvailable(),
        ];
    }
}
