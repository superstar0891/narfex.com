<?php

namespace Cron;

use Db\Transaction;
use Db\Where;
use Models\DepositModel;
use Models\NotificationModel;
use Models\PlanModel;
use Models\UserModel;
use Modules\InvestmentModule;
use Modules\NotificationsModule;

class ProfitCronJob implements CronJobInterface {
    public function exec() {
        $deposits = DepositModel::select(Where::and()
            ->set('status', Where::OperatorEq, 'accepted')
            ->set('DATE(date_start)', Where::OperatorLowerEq, date('Y-m-d')));

        $plans = PlanModel::select(Where::in('id', $deposits->column('plan')), false);
        $plans_map = [];
        foreach ($plans as $plan) {
            $plans_map[$plan->id] = $plan;
        }

        $users = UserModel::select(Where::in('id', $deposits->column('user_id')));
        $users_map = [];
        foreach ($users as $user) {
            $users_map[$user->id] = $user;
        }

        /* @var \Models\DepositModel $deposit */
        foreach ($deposits as $deposit) {
            if (!in_array($deposit->user_id, DepositModel::USERS_WITH_ENABLED_DEPOSITS)
                && $deposit->dynamic_percent != DepositModel::TYPE_POOL) {
                continue;
            }

            if (!isset($users_map[$deposit->user_id])) {
                continue;
            }

            /* @var UserModel $user */
            $user = $users_map[$deposit->user_id];
            if (!$user->active) {
                continue;
            }

            if (!isset($plans_map[$deposit->plan])) {
                continue;
            }
            /* @var \Models\PlanModel $plan */
            $plan = $plans_map[$deposit->plan];

            $days = $deposit->days;
            $passed_days = floor((time() - strtotime($deposit->date_start)) / (60 * 60 * 24));

            if ($days >= $passed_days) {
                continue;
            }

            if ($deposit->dynamic_percent == DepositModel::TYPE_STATIC) {
                $amount = $deposit->amount * $deposit->dynamic_coeff * $plan->percent / 100;
                if ($deposit->withdraw_disabled) {
                    $amount *= $deposit->withdraw_disabled_coeff;
                }
            } else if ($deposit->dynamic_percent == DepositModel::TYPE_DYNAMIC) {
                $amount = $deposit->amount * $deposit->dynamic_coeff * $deposit->dynamic_curr_percent / 100;
                if ($deposit->withdraw_disabled) {
                    $amount *= $deposit->withdraw_disabled_coeff;
                }
            } else if ($deposit->dynamic_percent == DepositModel::TYPE_POOL) {
                $deposit->days += 1;
                if ($deposit->days >= (int) $plan->days) {
                    $deposit->status = 'done';
                }
                Transaction::wrap(function () use ($deposit) {
                    $deposit->save();

                    if ($deposit->status === 'done') {
                        InvestmentModule::addProfit($deposit->user_id, $deposit->amount, $deposit, 'return_deposit');
                    }
                });
                continue;
            } else {
                $amount = $deposit->amount * $plan->percent / 100;
            }

            $amount *= $deposit->plan_percent_coeff;

            Transaction::wrap(function () use ($user, $amount, $deposit, $days, $plan) {
                InvestmentModule::addProfit($user->id, $amount, $deposit, $deposit->operation . '_profit');
                InvestmentModule::agentsProfits($deposit, $user, $amount);

                $deposit->days = $days + 1;
                if ($deposit->dynamic_percent != 0) {
                    $deposit->dynamic_profit = $deposit->dynamic_profit + $amount;

                    if ($deposit->withdraw_disabled == 0) {
                        $deposit->dynamic_profit_share = $deposit->dynamic_profit_share + $amount;
                    }

                    if ($deposit->dynamic_percent == 2) {
                        $deposit->dynamic_curr_percent = $deposit->dynamic_curr_percent + $deposit->dynamic_daily_percent;
                    }
                }

                if ($deposit->days >= (int)$plan->days) {
                    $deposit->status = 'done';
                    InvestmentModule::addProfit($deposit->user_id, $deposit->amount, $deposit, 'return_deposit');
                }

                if ($days > (int)$plan->days) {
                    $deposit->status = 'done';
                }

                $deposit->save();

                if ($deposit->status === 'done') {
                    NotificationsModule::send($user->id, NotificationModel::TYPE_DEPOSIT_COMPLETED, [
                        'message' => 'notifications_your_deposit_end_day',
                        'deposit_id' => $deposit->id,
                        'date_start' => $deposit->date_start,
                        'amount' => $deposit->amount,
                        'plan_id' => $deposit->plan
                    ]);
                }
            });
        }
    }
}
