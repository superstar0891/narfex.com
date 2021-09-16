<?php

namespace Api\Investment;

use Api\Errors;
use Core\Response\JsonResponse;
use Db\Transaction;
use Db\Where;
use Models\DepositCoeffHistoryModel;
use Models\DepositModel;
use Models\InviteLinkModel;
use Models\PaymentModel;
use Models\PlanModel;
use Models\PoolModel;
use Models\ProfitModel;
use Models\UserModel;
use Models\WalletModel;
use Modules\InvestmentModule;
use Modules\LogModule;
use Modules\UserModule;
use Modules\WalletModule;
use Serializers\ErrorSerializer;
use Serializers\InvestmentSerializer;
use Serializers\PagingSerializer;
use Serializers\PlanSerializer;
use Serializers\ProfitSerializer;
use Serializers\WalletSerializer;
use Serializers\WithdrawalSerializer;

class Investment {
    public static function retrieveList($request) {
        $user = getUser($request);

        [$wallets, $payments_result, $deposits_result] = InvestmentModule::investmentData($user->id);
        [$chart_usd_profit, $chart] = InvestmentModule::investProfitChart($user->id);

        JsonResponse::ok([
            'balances' => $wallets->map('Serializers\WalletSerializer::listItem'),
            'payments' => array_values($payments_result),
            'deposits' => array_reverse($deposits_result),
            'chart' => [
                'usd_profit' => (double) $chart_usd_profit,
                'data' => $chart,
            ],
        ]);
    }

    public static function depositRetrieve($request) {
        /* @var int $deposit_id */
        extract($request['params']);

        $user = getUser($request);

        $deposit = DepositModel::select(Where::and()
            ->set('id', Where::OperatorEq, $deposit_id)
            ->set('user_id', Where::OperatorEq, $user->id)
        );

        if ($deposit->isEmpty()) {
            JsonResponse::errorMessage('deposit_is_empty');
        }

        $deposit = $deposit->first();
        /* @var DepositModel $deposit */

        /* @var PlanModel $plan */
        $plan = PlanModel::get($deposit->plan, false);

        $result = InvestmentSerializer::listItem($deposit, $plan);

        if (KERNEL_CONFIG['pool']['plan_id'] == $plan->id) {
            $pool = PoolModel::select(Where::and()
                ->set('deposit_id', Where::OperatorEq, $deposit->id)
            );
            if ($pool->isEmpty()) {
                $result['proposed_amount'] = $deposit->amount;
            } else {
                $pool = $pool->first();
                /* @var PoolModel $pool */

                $result['proposed_amount'] = $pool->amount;
            }
        }

        JsonResponse::ok($result);
    }

    public static function withdrawalRetrieveList($request) {
        /* @var string $start_from */
        extract($request['params']);

        $start_from = intval($start_from);

        $user = getUser($request);

        $count = 25;
        $where = Where::and()->set('user_id', Where::OperatorEq, $user->id);
        if ($start_from) {
            $where->set('id', Where::OperatorLower, $start_from);
        }
        $payments_builder = PaymentModel::queryBuilder()
            ->columns([])
            ->where($where)
            ->orderBy(['id' => 'DESC'])
            ->limit($count)
            ->select();
        $payments = PaymentModel::rowsToSet($payments_builder);

        $payments_total = PaymentModel::queryBuilder()
            ->columns(['COUNT(id)' => 'cnt'], true)
            ->where(Where::equal('user_id', $user->id))
            ->get();

        $payment_wallet_ids = array_unique($payments->column('wallet_id'));

        $wallets = WalletModel::select(Where::in('id', $payment_wallet_ids));

        $result = [];
        foreach ($payments as $payment) {
            foreach ($wallets as $wallet) {
                if ($payment->wallet_id == $wallet->id) {
                    $result[] = WithdrawalSerializer::listItem($payment, $wallet);
                }
            }
        }

        if ($payments->count() == $count) {
            $next_from = $payments->last()->id;
        } else {
            $next_from = null;
        }

        JsonResponse::ok([
            'withdrawals' => PagingSerializer::detail($next_from, $result),
            'total_count' => $payments_total ? (int) $payments_total['cnt'] : 0,
        ]);
    }

    public static function profitRetrieveList($request) {
        /* @var string $start_from */
        extract($request['params']);

        $start_from = intval($start_from);

        $user = getUser($request);

        $count = 25;
        $where = Where::and()->set('user_id', Where::OperatorEq, $user->id);
        if ($start_from) {
            $where->set('id', Where::OperatorLower, $start_from);
        }

        $profits_builder = ProfitModel::queryBuilder()
            ->columns([])
            ->where($where)
            ->orderBy(['id' => 'DESC'])
            ->limit($count)
            ->select();
        $profits = ProfitModel::rowsToSet($profits_builder);

        $profits_total = ProfitModel::queryBuilder()
            ->columns(['COUNT(id)' => 'cnt'], true)
            ->where(Where::equal('user_id', $user->id))
            ->get();

        $deposit_ids = array_unique($profits->column('deposit_id'));
        $deposits = DepositModel::select(Where::in('id', $deposit_ids));

        $deposits_map = [];
        /* @var DepositModel $deposit */
        foreach ($deposits as $deposit) {
            $deposits_map[$deposit->id] = $deposit;
        }

        $plan_ids = $deposits->column('plan');
        $plans = PlanModel::select(Where::in('id', $plan_ids), false);

        $plans_map = [];
        /* @var PlanModel $plan */
        foreach ($plans as $plan) {
            $plans_map[$plan->id] = $plan;
        }

        $result = [];
        foreach ($profits as $profit) {
            $deposit = $deposits_map[$profit->deposit_id] ?? null;
            if (!$deposit) {
                continue;
            }
            $plan = $plans_map[$deposit->plan] ?? null;

            $result[] = [
                'profit' => ProfitSerializer::listItem($profit),
                'deposit' => $deposit ? InvestmentSerializer::listItem($deposit, $plan) : null,
                'plan' => $plan ? PlanSerializer::listItem($plan, $deposit->plan_percent_coeff) : null,
            ];
        }

        if ($profits->count() == $count) {
            $next_from = $profits->last()->id;
        } else {
            $next_from = null;
        }

        JsonResponse::ok([
            'profits' => PagingSerializer::detail($next_from, $result),
            'total_count' => (int) ($profits_total ? $profits_total['cnt'] : 0),
        ]);
    }

    public static function withdrawRetrieve($request) {
        /* @var int $currency */
        extract($request['params']);

        $user = getUser($request);

        if (UserModule::isWithdrawDisabled($user)) {
            JsonResponse::apiError(Errors::WITHDRAW_DISABLED);
        }

        $wallet = WalletModel::select(Where::and()
            ->set('user_id', Where::OperatorEq, $user->id)
            ->set('currency', Where::OperatorEq, $currency)
        );

        if ($wallet->isEmpty()) {
            JsonResponse::apiError();
        }

        $wallet = $wallet->first();
        /* @var WalletModel $wallet */

        $deposits = DepositModel::select(Where::and()
            ->set('user_id', Where::OperatorEq, $user->id)
            ->set('dynamic_percent', Where::OperatorNotEq, 0)
            ->set('status', Where::OperatorEq, 'accepted')
            ->set('currency', Where::OperatorEq, $currency)
        );

        $disabled = 0;
        $dynamic = 0;
        /* @var DepositModel $deposit */
        foreach ($deposits as $deposit) {
            if ($deposit->withdraw_disabled) {
                $disabled += $deposit->dynamic_profit;
            } else if ($deposit->dynamic_profit_share > 0 && $deposit->dynamic_percent == DepositModel::TYPE_DYNAMIC && $deposit->status === 'accepted') {
                $dynamic += $deposit->dynamic_profit_share;
            }
        }

        $available = positive($wallet->profit - $disabled);

        JsonResponse::ok([
            'available' => round($available, 6, PHP_ROUND_HALF_DOWN),
            'available_without_drop' => round($available - $dynamic, 6, PHP_ROUND_HALF_DOWN),
            'wallet' => WalletSerializer::listItem($wallet),
        ]);
    }

    public static function withdraw($request) {
        /* @var int $wallet_id
         * @var double $amount
         */
        extract($request['params']);

        $user = getUser($request);

        if (UserModule::isWithdrawDisabled($user)) {
            JsonResponse::apiError(Errors::WITHDRAW_DISABLED);
        }

        try {
            /* @var \Models\WalletModel $wallet */
            $wallet = WalletModel::get($wallet_id);
        } catch (\Exception $e) {
            JsonResponse::errorMessage('api_wallet_not_found');
        }

        if ($wallet->user_id != $user->id) {
            JsonResponse::accessDeniedError();
        }

        if ($wallet->profit < $amount) {
            JsonResponse::errorMessage('insufficient_funds', Errors::INSUFFICIENT_FUNDS);
        }

        $deposits = DepositModel::select(Where::and()
            ->set('user_id', Where::OperatorEq, $user->id)
            ->set('status', Where::OperatorEq, 'accepted')
            ->set('currency', Where::OperatorEq, $wallet->currency)
            ->set('withdraw_disabled', Where::OperatorEq, 0));


        $all_sum = array_sum($deposits->column('amount'));
        $all_sum_percent = floor($all_sum * settings()->deposit_withdraw_min / 100 * 1000 ) / 1000;

        if ($amount < $all_sum_percent) {
            $err_msg = str_replace([
                '{min}',
                '{all_percent}',
                '{currency}'
            ], [
                settings()->deposit_withdraw_min,
                $all_sum_percent,
                $wallet->currency
            ], lang('api_withdraw_limit_error'));
            JsonResponse::error(ErrorSerializer::detail(Errors::AMOUNT_INCORRECT, $err_msg));
        }

        $non_withdrawal_deposits = DepositModel::select(Where::and()
            ->set('user_id', Where::OperatorEq, $user->id)
            ->set('status', Where::OperatorEq, 'accepted')
            ->set('currency', Where::OperatorEq, $wallet->currency)
            ->set('withdraw_disabled', Where::OperatorEq, 1)
        );

        $cant_withdraw = array_sum($non_withdrawal_deposits->column('dynamic_profit'));

        if ($wallet->profit - $cant_withdraw < $amount) {
            JsonResponse::errorMessage('api_withdrawal_static_deposit_error', Errors::AMOUNT_INCORRECT);
        }

        if (!floodControl('investment_profit_withdrawal_' . $user->id, KERNEL_CONFIG['flood_control']['investment_profit_withdrawal'])) {
            JsonResponse::floodControlError();
        }

        // sub deposits coeffs
        $opened_deposits = DepositModel::select(Where::and()
            ->set('user_id', Where::OperatorEq, $user->id)
            ->set('status', Where::OperatorEq, 'accepted')
            ->set('currency', Where::OperatorEq, $wallet->currency)
            ->set('dynamic_percent', Where::OperatorNotEq, 0)
            ->set('dynamic_profit', Where::OperatorGreater, 0)
            ->set('withdraw_disabled', Where::OperatorEq, 0)
        );

        Transaction::wrap(function () use ($opened_deposits, $wallet, $cant_withdraw, $amount) {
            if (!$wallet->checkProfit($amount)) {
                throw new \Exception();
            }

            if (!$opened_deposits->isEmpty()) {
                $profit_sum = 0;
                $whole_profit_sum = 0;
                /* @var \Models\DepositModel $dynamic_deposit */
                foreach ($opened_deposits as $dynamic_deposit) {
                    $profit_sum += $dynamic_deposit->dynamic_profit_share;
                    $whole_profit_sum += $dynamic_deposit->getWholeProfit();
                }

                if ($wallet->profit - $cant_withdraw - $amount < $profit_sum) {
                    $dynamic_withdraw = $profit_sum - ($wallet->profit - $cant_withdraw - $amount);

                    /* @var \Models\DepositModel $dynamic_deposit */
                    foreach ($opened_deposits as $dynamic_deposit) {
                        $res = InvestmentModule::depositCountDynamicPercent($dynamic_deposit, $profit_sum, $whole_profit_sum, $dynamic_withdraw);
                        if ($res && !empty($res)) {
                            $new_coeff = $res[0];
                            $new_share = $res[1];
                            $old_coeff = $dynamic_deposit->dynamic_coeff;

                            $dynamic_deposit->dynamic_coeff = $new_coeff;
                            $dynamic_deposit->dynamic_profit_share = $dynamic_deposit->dynamic_profit_share - $new_share;
                            $dynamic_deposit->save();

                            $history = new DepositCoeffHistoryModel();
                            $history->deposit_id = $dynamic_deposit->id;
                            $history->old_coeff = $old_coeff;
                            $history->new_coeff = $new_coeff;
                            $history->created_at = date('Y-m-d H:i:s');
                            $history->save();
                        }
                    }
                }
            }

            if (!$wallet->subProfit($amount)) {
                throw new \Exception();
            }

            $payment = new PaymentModel();
            $payment->wallet_id = $wallet->id;
            $payment->amount = $amount;
            $payment->created_at = date('Y-m-d H:i:s');
            $payment->user_id = $wallet->user_id;
            $payment->status = 'waiting';
            $payment->wallet_address = $wallet->address;
            $payment->save();

            LogModule::add($wallet->user_id, 'withdrawal_request');
        });

        JsonResponse::ok();
    }

    public static function openDeposit($request) {
        /* @var int $amount
         * @var int $wallet_id
         * @var int $plan_id
         * @var string $deposit_type
         */

        // users can not open new deposits now
        JsonResponse::errorMessage('deposits_disabled');

        extract($request['params']);

        $user = getUser($request);

        if ($deposit_type === 'static') {
            $dynamic_type = 1;
            $withdraw_disabled = 1;
        } elseif ($deposit_type == 'dynamic') {
            $dynamic_type = 2;
            $withdraw_disabled = 0;
        } else {
            $dynamic_type = null;
            $withdraw_disabled = null;
        }

        try {
            /* @var \Models\WalletModel $wallet */
            $wallet = WalletModel::get($wallet_id);
        } catch (\Exception $e) {
            JsonResponse::errorMessage('api_wallet_not_found');
        }

        if ($wallet->user_id != $user->id) {
            JsonResponse::errorMessage('access_denied');
        }

        try {
            /* @var PlanModel $plan */
            $plan = PlanModel::get($plan_id);
        } catch (\Exception $e) {
            JsonResponse::errorMessage('api_investment_plan_not_found');
        }

        if ($wallet->currency !== $plan->currency) {
            JsonResponse::errorMessage('module_wallet_not_match_plan_curr');
        }

        if ($wallet->amount < $amount) {
            JsonResponse::errorMessage('module_invest_not_match_wallet_amount');
        }

        if ($amount < $plan->min) {
            JsonResponse::error(ErrorSerializer::detail(Errors::FATAL,lang('module_min_sum_invest') . ' ' . $plan->min));
        }

        if (!in_array($wallet->currency, array_keys(currencies()), true)) {
            JsonResponse::errorMessage('api_currency_not_exist');
        }

        if (!floodControl('open_deposit_' . $user->id, KERNEL_CONFIG['flood_control']['open_deposit'])) {
            JsonResponse::floodControlError();
        }

        $opened_deposits = DepositModel::select(Where::and()
            ->set('user_id', Where::OperatorEq, $user->id)
            ->set('status', Where::OperatorEq, 'accepted')
            ->set('currency', Where::OperatorEq, $wallet->currency)
        );

        $opened_deposits_amount = array_sum($opened_deposits->column('amount'));

        $dynamic_start = 0;
        $dynamic_daily = 0;
        if ($dynamic_type == 2) {
            $dynamic_start = $plan->percent * settings()->dynamic_start_percent;
            $whole_percent = $plan->percent * $plan->days;
            $num_days = $plan->days;

            $dynamic_daily = InvestmentModule::dynamicFX($dynamic_start, $whole_percent, $num_days, 1)
                - InvestmentModule::dynamicFX($dynamic_start, $whole_percent, $num_days, 0);
        }

        $plan_percent = $plan->percent * $plan->days;
        $plan_percent_coeff = InvestmentModule::percentDrop($opened_deposits_amount, $wallet->currency, $amount, $plan_percent, $deposit_type);

        $deposit = new DepositModel();
        $deposit->amount = $amount;
        $deposit->user_id = $user->id;
        $deposit->wallet_id = $wallet->id;
        $deposit->operation = 'invest';
        $deposit->currency = $wallet->currency;
        $deposit->status = 'accepted';
        $deposit->plan = $plan->id;
        $deposit->dynamic_percent = $dynamic_type;
        $deposit->dynamic_start_percent = $dynamic_start;
        $deposit->dynamic_curr_percent = $dynamic_start;
        $deposit->dynamic_daily_percent = $dynamic_daily;
        $deposit->withdraw_disabled = $withdraw_disabled;
        $deposit->withdraw_disabled_coeff = settings()->withdraw_disabled_coeff;
        $deposit->plan_percent_coeff = $plan_percent_coeff;
        $deposit->created_at = date('Y-m-d H:i:s');
        $deposit->date_start = date('Y-m-d', strtotime('+1 day'));
        $deposit->charged_amount = $amount;

        Transaction::wrap(function () use ($wallet, $amount, $deposit, $user) {
            if (!$wallet->checkAmount($amount)) {
                throw new \Exception();
            }

            if (!$wallet->subAmount($amount)) {
                throw new \Exception();
            }
            $deposit->save();

            if ($user->refer) {
                list($ref_id) = array_map('intval', explode(',', $user->refer));

                try {
                    /* @var \Models\UserModel $ref */
                    $ref = UserModel::get($ref_id);
                } catch (\Exception $e) {
                    $ref = false;
                }

                if ($ref && $ref->role != 35) { // 35 - is agent
                    $all_deposits = DepositModel::select(Where::and()
                        ->set('user_id', Where::OperatorEq, $user->id)
                        ->set('currency', Where::OperatorEq, $wallet->currency)
                    );

//                    if ($all_deposits->count() == 1) {
//                        InvestmentModule::addProfit($ref_id, $deposit->amount * DepositModel::REFERRAL_PROFIT, $deposit, 'referral_profit', $user->id);
//                    }
                }
            }

            LogModule::add($wallet->user_id, 'new_investition');
        });

        if ($user->invite_link_id !== null) {
            try {
                /* @var InviteLinkModel $link */
                $link = InviteLinkModel::get($user->invite_link_id, true);
                $link->deposits_count += 1;
                $link->save();
            } catch (\Exception $e) {}
        }

        JsonResponse::ok([
            'deposit' => InvestmentSerializer::listItem($deposit, $plan),
            'balances' => WalletModule::getWallets($user->id)->map('Serializers\WalletSerializer::listItem'),
        ]);
    }

    public static function openPoolDeposit($request) {
        /* @var int $amount
         * @var int $wallet_id
         */

        // users can not open new deposits now
        JsonResponse::errorMessage('deposits_disabled');

        extract($request['params']);

        $user = getUser($request);

        /* @var WalletModel $wallet */
        $wallet = WalletModel::get($wallet_id);

        if ($wallet->user_id != $user->id) {
            JsonResponse::apiError();
        }

        /* @var PlanModel $plan */
        $plan = PlanModel::get(KERNEL_CONFIG['pool']['plan_id']);
        if ($amount < $plan->min || $amount > $plan->max) {
            JsonResponse::apiError();
        }

        if ($wallet->amount < $amount) {
            JsonResponse::errorMessage('module_invest_not_match_wallet_amount');
        }

        $exist = PoolModel::select(Where::and()
            ->set('user_id', Where::OperatorEq, $user->id)
            ->set('status', Where::OperatorEq, 1)
        );

        if (!$exist->isEmpty()) {
            JsonResponse::error(ErrorSerializer::detail(Errors::FATAL, 'Дождитесь рассмотрения предыдущей заявки'));
        }

        $pool = new PoolModel();
        $pool->user_id = $user->id;
        $pool->wallet_id = $wallet_id;
        $pool->status = 1;
        $pool->amount = $amount;
        $pool->currency = $wallet->currency;
        $pool->save();

        JsonResponse::ok([
            'balances' => WalletModule::getWallets($user->id)->map('Serializers\WalletSerializer::listItem'),
        ]);
    }

    public static function plansRetrieve($request) {
        /* @var string $currency
         * @var double $amount
         * @var string $deposit_type
        */
        extract($request['params']);

        $result = [];
        if ($deposit_type === 'pool') {
            $result[] = [
                'pool' => PlanSerializer::listItem(PlanModel::get(KERNEL_CONFIG['pool']['plan_id']), 1),
            ];
        } else {
            $user = getUser($request);

            $wallet = WalletModel::select(Where::and()
                ->set('user_id', Where::OperatorEq, $user->id)
                ->set('currency', Where::OperatorEq, $currency)
            );

            if ($wallet->isEmpty()) {
                JsonResponse::errorMessage('api_wallet_not_found');
            }

            $wallet = $wallet->first();
            /* @var \Models\WalletModel $wallet */

            $opened_deposits = DepositModel::select(Where::and()
                ->set('user_id', Where::OperatorEq, $user->id)
                ->set('status', Where::OperatorEq, 'accepted')
                ->set('currency', Where::OperatorEq, $wallet->currency)
            );

            $opened_deposits_amount = array_sum($opened_deposits->column('amount'));

            $plans = PlanModel::select(Where::and()
                ->set('currency', Where::OperatorEq, $currency)
                ->set('_delete', Where::OperatorEq, 0)
            );

            /* @var \Models\PlanModel $plan */
            foreach ($plans as $plan) {
                $plan_percent = $plan->percent * $plan->days;

                $plan_percent_coeff_static = InvestmentModule::percentDrop($opened_deposits_amount, $wallet->currency, $amount, $plan_percent, 'static');
                $static_coeff = $plan_percent_coeff_static + settings()->withdraw_disabled_coeff - 1;

                $dynamic_coeff = InvestmentModule::percentDrop($opened_deposits_amount, $wallet->currency, $amount, $plan_percent, 'dynamic');

                $result[] = [
                    'static' => PlanSerializer::listItem($plan, $static_coeff),
                    'dynamic' => PlanSerializer::listItem($plan, $dynamic_coeff),
                ];
            }
        }


        JsonResponse::ok([
            'plans' => $result,
        ]);
    }

    public static function calculate($request) {
        /* @var string $steps
         * @var double $amount
         * @var string $currency
         * @var int $plan_id
         */
        extract($request['params']);

        /* @var \Models\PlanModel $plan */
        $plan = PlanModel::get($plan_id);

        if ($amount < $plan->min) {
            JsonResponse::error(ErrorSerializer::detail(Errors::FATAL,lang('module_min_sum_invest') . ' ' . $plan->min));
        }

        $steps_arr = explode(',', $steps);
        $withdrawals = [];
        while (count($steps_arr) > 1) {
            list($day, $payment) = array_splice($steps_arr, 0, 2);

            $day = (int) $day;
            $payment = (double) $payment;

            $error_message = false;
            if ($day > $plan->days) {
                $error_message = 'Maximum ' . $plan->days . ' days';
            }

            if ($day < 0 || count($withdrawals) && $day < end($withdrawals)['day']) {
                $error_message = 'Day is incorrect';
            }

            if ($error_message) {
                $error = ErrorSerializer::detail(Errors::DAY_INCORRECT, $error_message);
                $error['day'] = (int) $day;
                JsonResponse::error($error);
            }

            $withdrawals[] = [
                'day' => $day,
                'amount' => $payment,
            ];
        }

        $dynamic_start = $plan->percent * settings()->dynamic_start_percent;
        $whole_percent = $plan->percent * $plan->days;
        $whole_percent_dynamic = $whole_percent;
        $num_days = $plan->days;

        $dynamic_daily = InvestmentModule::dynamicFX($dynamic_start, $whole_percent, $num_days, 1) -
            InvestmentModule::dynamicFX($dynamic_start, $whole_percent, $num_days, 0);

        $deposit = new DepositModel();
        $deposit->plan = $plan->id;
        $deposit->amount = $amount;
        $deposit->currency = $currency;
        $deposit->days = 0;
        $deposit->dynamic_percent = 2;
        $deposit->dynamic_coeff = 1;
        $deposit->dynamic_profit = 0;
        $deposit->dynamic_profit_share = 0;
        $deposit->dynamic_start_percent = $dynamic_start;
        $deposit->dynamic_curr_percent = $dynamic_start;
        $deposit->dynamic_daily_percent = $dynamic_daily;

        $normal_profit = $deposit->amount * $whole_percent / 100;

        $final_profit = 0;
        $profit = 0;
        $d = 0;
        $profits_result = [];
        $drop_percent_total = 0;
        for ($i = 1; $i <= $plan->days; $i++) {
            $step_profit = $deposit->amount * $deposit->dynamic_coeff * $deposit->dynamic_curr_percent / 100;
            $final_profit += $step_profit;
            $profit += $step_profit;
            $deposit->dynamic_profit += $step_profit;
            $deposit->dynamic_profit_share += $step_profit;
            $deposit->dynamic_curr_percent += $deposit->dynamic_daily_percent;
            $deposit->days++;

            $coeff = $deposit->dynamic_coeff;
            if ($d < count($withdrawals) && $i == $withdrawals[$d]['day']) {
                $payment = $withdrawals[$d]['amount'];

                $cur_profit = $profit;
                if (count($withdrawals) >= $d + 1 && $payment != 0) {
                    if ($profit >= $payment) {
                        $profit -= $payment;
                        $curr_whole_profit = $deposit->getWholeProfit();
                        $coeff_res = InvestmentModule::depositCountDynamicPercent($deposit, $deposit->dynamic_profit_share, $curr_whole_profit, $payment);
                        if (!empty($coeff_res)) {
                            $deposit->dynamic_coeff = $coeff_res[0];
                            $deposit->dynamic_profit_share -= $coeff_res[1];
                        }
                        $d++;
                    } else {
                        $error = ErrorSerializer::detail(Errors::AMOUNT_INCORRECT, 'You can\'t withdraw more');
                        $error['day'] = (int) $withdrawals[$d]['day'];
                        JsonResponse::error($error);
                    }
                }

                $drop_percent = $whole_percent_dynamic * ($coeff - $deposit->dynamic_coeff);
                $whole_percent_dynamic -= $drop_percent;
                $drop_percent_total += $drop_percent;

                $profits_result[] = [
                    'profit' => $cur_profit,
                    'percent' => $cur_profit / $amount * 100,
                    'drop_percent' => $drop_percent,
                    'drop_amount' => $drop_percent * $amount / 100,
                ];
            }
        }

        JsonResponse::ok([
            'profit' => (double) $final_profit,
            'percent' => (double) $whole_percent - $drop_percent_total,
            'profits_result' => $profits_result
        ]);
    }
}
