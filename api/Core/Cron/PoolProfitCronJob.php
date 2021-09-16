<?php

namespace Cron;

use Db\Where;
use Models\DepositModel;
use Models\ProfitModel;
use Modules\InvestmentModule;

class PoolProfitCronJob implements CronJobInterface {
    public function exec() {
        $percent = settings()->pool_percent;
        if ($percent <= 0) {
            return;
        }

        $current_day = (int) date('d');
        $current_month = (int) date('m');
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $current_month, (int) date('Y'));
        $half_month_day = 15;

        if ($current_day != $half_month_day || $current_day == $days_in_month) {
            return;
        }

        if ($current_day == $half_month_day) {
            $range_start = 1;
            $range_end = $half_month_day;
        } else {
            $range_start = $half_month_day + 1;
            $range_end = $days_in_month;
        }

        $deposits = DepositModel::select(Where::and()
            ->set('status', Where::OperatorEq, 'accepted')
            ->set('dynamic_type', Where::OperatorEq, DepositModel::TYPE_POOL)
            ->set(Where::and()
                ->set('DAY(date_start)', Where::OperatorGreaterEq, $range_start)
                ->set('DAY(date_start)', Where::OperatorLowerEq, $range_end)
            ));


        /* @var DepositModel $deposit */
        foreach ($deposits as $deposit) {
            if (!in_array($deposit->user_id, DepositModel::USERS_WITH_ENABLED_DEPOSITS)) {
                continue;
            }
            $last_profit = ProfitModel::queryBuilder()
                ->columns([])
                ->where(Where::equal('deposit_id', $deposit->id))
                ->orderBy(['id' => 'DESC'])
                ->select();
            $last_profit = ProfitModel::rowsToSet($last_profit);

            if (!$last_profit->isEmpty()) {
                $month = (int) date('m', $last_profit->first()->created_at_timestamp);
                if ($month == $current_month) {
                    continue;
                }
            }

            $amount = $deposit->amount * $percent / 100;
            InvestmentModule::addPoolProfit($deposit->id, $amount);
        }
    }
}
