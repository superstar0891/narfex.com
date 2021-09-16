<?php

namespace Cron;

use Db\Transaction;
use Db\Where;
use Models\ProfitModel;
use Models\WalletModel;

class DailySavingCronJob implements CronJobInterface {

    const MIN_TOKEN_AMOUNT = 100;

    public function exec() {
        $profit = ProfitModel::first(Where::and()
            ->set(Where::equal('type', ProfitModel::TYPE_SAVING_PROFIT))
            ->set('created_at_timestamp', Where::OperatorGreaterEq, strtotime('today'))
        );

        if ($profit) {
            return;
        }

        $wallets = WalletModel::select(Where::and()
            ->set(Where::equal('currency', CURRENCY_FNDR))
            ->set('amount', Where::OperatorGreaterEq, self::MIN_TOKEN_AMOUNT)
            ->set(Where::equal('saving_enabled', 1))
        );

        Transaction::wrap(function () use ($wallets) {
            foreach ($wallets as $wallet) {
                /* @var WalletModel $wallet */

                $percent = $this->calcYearlyPercent($wallet->amount) / date('t') / 100;
                $profit_amount = $wallet->amount * $percent;

                $profit = new ProfitModel();
                $profit->amount = $profit_amount;
                $profit->type = ProfitModel::TYPE_SAVING_PROFIT;
                $profit->user_id = $wallet->user_id;
                $profit->currency = $wallet->currency;
                $profit->wallet_id = $wallet->id;
                $profit->created_at = date('Y-m-d H:i:s');
                $profit->save();
            }
        });
    }

    private function calcYearlyPercent(float $amount) {
        $ranges = [
            [
                'amount' => 100,
                'percent' => 2,
            ],
            [
                'amount' => 1001,
                'percent' => 2.2,
            ],
            [
                'amount' => 3001,
                'percent' => 2.4,
            ],
            [
                'amount' => 5001,
                'percent' => 2.6,
            ],
            [
                'amount' => 7001,
                'percent' => 2.8,
            ],
            [
                'amount' => 10001,
                'percent' => 3,
            ]
        ];

        foreach ($ranges as $range) {
            if ($amount >= $range['amount']) {
                return $range['percent'];
            }
        }

        return 0;
    }
}
