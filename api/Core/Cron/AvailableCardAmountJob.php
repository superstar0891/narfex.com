<?php

namespace Cron;

use Core\Services\Merchant\FastExchangeService;
use Db\Where;
use Models\CardModel;

class AvailableCardAmountJob implements CronJobInterface {
    public function exec() {
        $cards =  CardModel::select(Where::equal('active', 1));
        foreach ($cards as $card) {
            FastExchangeService::calcAvailableAmount($card);
        }
    }
}
