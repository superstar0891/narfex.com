<?php

namespace Serializers;

use Db\Where;
use Models\BalanceHistoryModel;
use Models\BalanceModel;
use Models\UserBalanceHistoryModel;
use Modules\FiatWalletModule;
use Modules\WalletModule;

class BalanceSerializers {
    public static function listItem(BalanceModel $balance) {
        return [
            'id' => (int) $balance->id,
            'amount' => (double) $balance->amount,
            'currency' => strtolower($balance->currency),
            'align' => (double) $balance->alignAmount('btc'),
            'to_usd' => $balance->toUSD(),
            'has_history' => (bool) $balance->has_history,
        ];
    }

    public static function item(BalanceModel $balance) {
        $result = self::listItem($balance);
        $result['to_btc'] = FiatWalletModule::getRate($balance->currency, CURRENCY_BTC, false);
        return $result;
    }

    public static function historyListItem(BalanceHistoryModel $history) {
        return [
            'id' => (int) $history->id,
            'amount' => (double) $history->amount,
        ];
    }
}
