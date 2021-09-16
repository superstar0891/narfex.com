<?php

namespace Serializers\BalanceHistory;

use Db\Model\ModelSet;
use Models\ProfitModel;
use Models\UserModel;
use Serializers\ProfitSerializer as SavingProfitSerializer;

class ProfitSerializer {
    public static function listItem(ProfitModel $profit) {
        return SavingProfitSerializer::listItem($profit);
    }

    public static function promoListItem(ProfitModel $profit, Modelset $users): array {
        $login = '';

        if ($user = $users->getItem($profit->target_id)) {
            /** @var UserModel $user */
            $login = $user->login;
        }
        return [
            'id' => (int) $profit->id,
            'amount' => (double) $profit->amount,
            'type' => $profit->type,
            'currency' => $profit->currency,
            'created_at' => (int) strtotime($profit->created_at),
            'login' => $login,
        ];
    }

    public static function bitcoinovnetProfit(ProfitModel $profit) {
        return [
            'amount' => (float) $profit->amount,
            'currency' => $profit->currency,
            'rate' => (float) $profit->rate,
            'percent_profit' => (float) $profit->agent_percent_profit,
            'created_at' => $profit->created_at_timestamp,
            'type' => 'profit',
        ];
    }
}
