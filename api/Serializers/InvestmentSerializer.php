<?php

namespace Serializers;

use Models\DepositModel;
use Models\PlanModel;
use Modules\InvestmentModule;
use Modules\WalletModule;

class InvestmentSerializer {
    public static function listItem(DepositModel $deposit, PlanModel $plan) {
        $deposit_type = InvestmentModule::getDepositType($deposit);

        if ($deposit_type === 'pool') {
            $day_percent = 0;
            $percent = $deposit->dynamic_profit / $deposit->amount * 100;
            $cur_percent = $percent;
            $profit = $deposit->dynamic_profit;
            $plan_percent = 0;
        } else {
            $day_percent = $plan->percent * $deposit->dynamic_coeff;
            $profit = $deposit->days * ($deposit->amount * $plan->percent / 100);
            $percent = $plan->days * $plan->percent;
            $cur_percent = $deposit->days * $plan->percent;
            $plan_percent = $percent;
            if ($deposit->dynamic_percent) {
                if ($deposit->dynamic_percent == 1) {
                    $percent = $deposit->dynamic_coeff * $plan->percent * $plan->days * $deposit->plan_percent_coeff;
                    $cur_percent = $deposit->dynamic_coeff * $plan->percent * $deposit->days * $deposit->plan_percent_coeff;
                    $plan_percent = $plan->percent * $plan->days * $deposit->plan_percent_coeff;
                } elseif ($deposit->dynamic_percent == 2) {
                    $percent *= $deposit->dynamic_coeff;
                    $cur_percent = $deposit->dynamic_profit * 100 / $deposit->amount;
                }

                if ($deposit->withdraw_disabled) {
                    $percent *= $deposit->withdraw_disabled_coeff;
                    $cur_percent *= $deposit->withdraw_disabled_coeff;
                    $plan_percent *= $deposit->withdraw_disabled_coeff;
                }
            }

            $profit = $deposit->dynamic_percent ? $deposit->dynamic_profit : $profit;
        }

        return [
            'description' => $plan->description,
            'day_percent' => (float) $day_percent,
            'percent' => (double) round($percent, 2, PHP_ROUND_HALF_DOWN),
            'current_percent' => $cur_percent,
            'days' => (int) $plan->days,
            'id' => (int) $deposit->id,
            'passed_days' => (int) $deposit->days,
            'status' => $deposit->status,
            'operation' => $deposit->operation,
            'currency' => $deposit->currency,
            'amount' => (double) $deposit->amount,
            'profit' => (double) round($profit, 9),
            'usd_profit' => (double) WalletModule::getUsdPrice($deposit->currency) * $profit,
            'created_at' => (int) $deposit->created_at_timestamp ?: (int) $deposit->created_at,
            'type' => $deposit_type,
            'plan_percent' => (double) round($plan_percent, 2, PHP_ROUND_HALF_DOWN),
        ];
    }
}
