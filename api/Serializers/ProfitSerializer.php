<?php

namespace Serializers;

use Models\ProfitModel;
use Modules\WalletModule;

class ProfitSerializer {
    public static function listItem(ProfitModel $profit) {
        return [
            'id' => (int) $profit->id,
            'amount' => (double) $profit->amount,
            'type' => $profit->type,
            'date' => (int) strtotime($profit->created_at),
            'currency' => $profit->currency,
        ];
    }

    public static function profitChartItem(ProfitModel $profit): array {
        return [
            'currency' => $profit->currency === null ? 'btc' : $profit->currency,
            'amount' => (double) $profit->amount,
            'usd_amount' => WalletModule::getUsdPrice($profit->currency) * $profit->amount,
            'created_at' => (int) $profit->created_at_timestamp ?: (int) strtotime($profit->created_at),
        ];
    }
}
