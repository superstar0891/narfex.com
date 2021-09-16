<?php

namespace Modules;

use DateInterval;
use Datetime;
use Db\Transaction;
use Db\Where;
use Models\BalanceModel;
use Models\DepositModel;
use Models\PaymentModel;
use Models\PlanModel;
use Models\ProfitModel;
use Models\RoleModel;
use Models\UserModel;
use Models\UserRoleModel;
use Models\WalletModel;
use Serializers\InvestmentSerializer;
use Serializers\ProfitSerializer;

class InvestmentModule {
    public static function investmentData(int $user_id) {
        $wallets = WalletModule::getWallets($user_id);

        $payments_result = [];
        foreach (currencies() as $currency => $_) {
            $payments_result[$currency] = [
                'invested_amount' => 0,
                'total_invested_amount' => 0,
                'profit' => 0,
                'available' => 0,
                'currency' => $currency
            ];
        }

        $deposits = DepositModel::select(Where::and()
            ->set('user_id', Where::OperatorEq, $user_id)
            ->set(Where::or()
                ->set('status', Where::OperatorEq, 'accepted')
                ->set('status', Where::OperatorEq, 'done')
            ), false);

        $plan_ids = [];
        $deposits_result = [];
        $cant_withdraw = [];
        /* @var DepositModel $deposit */
        foreach ($deposits as $deposit) {
            if ($deposit->deleted_at !== null) {
                continue;
            }

            if (empty($cant_withdraw[$deposit->currency])) {
                $cant_withdraw[$deposit->currency] = 0;
            }

            if ($deposit->withdraw_disabled && $deposit->status === 'accepted') {
                $cant_withdraw[$deposit->currency] += $deposit->dynamic_profit;
            }

            if (isset($payments_result[$deposit->currency]) && $deposit->status === 'accepted') {
                $payments_result[$deposit->currency]['invested_amount'] += $deposit->amount;
            }

            $payments_result[$deposit->currency]['total_invested_amount'] += $deposit->amount;

            $plan_ids[] = $deposit->plan;
        }

        $plans = PlanModel::select(Where::in('id', $plan_ids), false);

        // Group deposits with plans
        foreach ($deposits as $deposit) {
            foreach ($plans as $plan) {
                if ($plan->id == $deposit->plan && $deposit->deleted_at === null) {
                    $deposits_result[] = InvestmentSerializer::listItem($deposit, $plan);
                }
            }
        }

        /* @var WalletModel $wallet */
        foreach ($wallets as $wallet) {
            if (array_key_exists($wallet->currency, $cant_withdraw)) {
                $disabled = $cant_withdraw[$wallet->currency];
            } else {
                $disabled = 0;
            }

            $available = $wallet->profit - $disabled;
            if ($available > 0) {
                $payments_result[$wallet->currency]['available'] = (float) $available;
            }

            $payments_result[$wallet->currency]['profit'] = (float) $wallet->profit;
        }

        // profits
        $profits = InvestmentModule::getProfit($user_id, false, false);
        foreach ($profits as $currency => $profit) {
            $payments_result[$currency]['total_profit'] = (float) $profit['amount'];
        }

        // payments
        $payments = PaymentModel::queryBuilder()
            ->columns([
                'SUM(amount)' => 'total',
                'wallet_id'
            ], true)
            ->where(Where::and()
                ->set('user_id', Where::OperatorEq, $user_id)
                ->set('status', Where::OperatorEq, 'accepted')
            )
            ->groupBy(['wallet_id'])
            ->select();

        foreach ($payments as $payment) {
            $wallet_id = (int) $payment['wallet_id'];
            $wallet = $wallets->getItem($wallet_id);
            if (is_null($wallet)) {
                continue;
            }

            $payments_result[$wallet->currency]['total_paid'] = (float) $payment['total'];
        }

        return [$wallets, $payments_result, $deposits_result];
    }

    public static function investProfitChart($user_id, $period = 30): array {
        $period = 'P'.$period.'D';
        $date_now = new DateTime();
        $from_date = $date_now->sub(new DateInterval($period))->format('Y-m-d');

        $profits = ProfitModel::select(Where::and()
            ->set('user_id', Where::OperatorEq, $user_id)
            ->set('type', Where::OperatorIN, ['invest_profit', 'reinvest_profit', 'referral_profit'])
            ->set('created_at', Where::OperatorGreater, $from_date)
        );

        $result = [];
        $usd_profit = 0;
        /* @var ProfitModel $profit */
        foreach ($profits as $profit) {
            $currency = $profit->currency === null ? 'btc' : $profit->currency;
            if (!isset($result[$currency])) {
                $result[$currency] = [];
            }

            $date = date('d-m-Y', strtotime($profit->created_at));
            if (!isset($result[$currency][$date])) {
                $result[$currency][$date] = ProfitSerializer::profitChartItem($profit);
            } else {
                $result[$currency][$date]['amount'] += $profit->amount;
                $result[$currency][$date]['usd_amount'] += WalletModule::getUsdPrice($profit->currency) * $profit->amount;
            }

            $usd_profit += WalletModule::getUsdPrice($currency) * $profit->amount;
        }

        foreach ($result as $currency => $data) {
            $result[$currency] = array_values($data);
        }

        return [$usd_profit, $result];
    }

    public static function getProfit($user_id, $is_partners = false, $merge = true) {
        if ($is_partners) {
            $types = ['referral_profit'];
        } else {
            $types = ['invest_profit', 'reinvest_profit'];
        }

        $profits = ProfitModel::queryBuilder()
            ->columns(['SUM(amount)' => 'total', 'currency'], true)
            ->where(Where::and()
                ->set('type', Where::OperatorIN, $types)
                ->set('user_id', Where::OperatorEq, $user_id)
            )
            ->groupBy('currency')
            ->select();

        if (!$merge) {
            $result = [];
            foreach ($profits as $row) {
                $result[$row['currency']] = [
                    'amount' => $row['total'],
                ];
            }
            return $result;
        }

        $btc_result = 0;
        foreach ($profits as $result) {
            $wallet = new WalletModel();
            $wallet->currency = $result['currency'];
            $wallet->amount = $result['total'];
            $btc_result += $wallet->alignAmount('btc');
        }

        return [
            'btc' => $btc_result,
            'usd' => WalletModule::getUsdPrice('btc') * $btc_result
        ];
    }

    public static function getInvested($user_id) {
        $deposits = DepositModel::queryBuilder()
            ->columns(['SUM(amount)' => 'total'])
            ->where(Where::and()
                ->set('user_id', Where::OperatorEq, $user_id)
                ->set('deleted_at', Where::OperatorIs, null))
            ->groupBy(['id', 'currency'])
            ->select();

        $result = 0;
        foreach ($deposits as $deposit) {
            $wallet = new WalletModel();
            $wallet->currency = $deposit['currency'];
            $wallet->amount = $deposit['total'];
            $result += $wallet->alignAmount('btc');
        }

        $wallet = new WalletModel();
        $wallet->currency = 'btc';
        $wallet->amount = $result;

        return [
            'btc' => $result,
            'usd' => $wallet->toUSD()
        ];
    }

    /* @param \Models\DepositModel $deposit
     * @param double $deposits_sum
     * @param double $deposits_whole_sum
     * @param double $withdraw_amount
     * @return array
     */
    public static function depositCountDynamicPercent(DepositModel $deposit, $deposits_sum, $deposits_whole_sum, $withdraw_amount) {
        if ($deposit->dynamic_percent == DepositModel::TYPE_POOL) {
            return [];
        }

        $minimal_coeff = settings()->dynamic_minimal_coeff;
        $point_x = settings()->dynamic_point_x;
        $point_y = settings()->dynamic_point_y;
        $deposit_profit = $deposit->getWholeProfit();

        $x = $withdraw_amount / $deposits_whole_sum * $deposit_profit;
        if ($x <= $point_x * $deposit_profit) {
            $coeff = self::dynamicF1X($deposit, $deposit_profit, $point_x, $point_y, $x);
        } else {
            $coeff = self::dynamicF2X($deposit, $deposit_profit, $minimal_coeff, $point_x, $point_y, $x);
        }

        return [$coeff, $withdraw_amount / $deposits_sum * $deposit->dynamic_profit_share];
    }

    private static function dynamicF1X(DepositModel $deposit, $deposit_profit, $point_x, $point_y, $x) {
        $x_1 = 0;
        $y_1 = $deposit->dynamic_coeff;
        $x_2 = $point_x * $deposit_profit;
        $y_2 = $point_y * $deposit->dynamic_coeff;

        return ($x - $x_1) * ($y_2 - $y_1) / ($x_2 - $x_1) + $y_1;
    }

    private static function dynamicF2X(DepositModel $deposit, $deposit_profit, $minimal_coeff, $point_x, $point_y, $x) {
        $x_1 = $point_x * $deposit_profit;
        $y_1 = $point_y * $deposit->dynamic_coeff;
        $x_2 = $deposit_profit;
        $y_2 = $minimal_coeff;

        return ($x - $x_1) * ($y_2 - $y_1) / ($x_2 - $x_1) + $y_1;
    }

    public static function dynamicFX($start_percent, $whole_percent, $num_days, $day) {
        return ( 2 / ($num_days - 1)) * ($whole_percent / $num_days - $start_percent) * $day + $start_percent;
    }

    public static function percentDrop($existing_sum, $curr, $open_amount, $start_percent, string $deposit_type) {
        $drop_coeff = 13;

        $whole_amount = $existing_sum + $open_amount; // x point
        $drop_start = currencies()[$curr]['profit_drop']; // x_1 point
        $x_2 = 10 * $drop_start;
        $y_2 = $start_percent - 10 * ($start_percent/$drop_coeff);

        if ($whole_amount <= $drop_start) {
            $coeff = 1;
        } else if ($whole_amount > $drop_start && $whole_amount <= 10*$drop_start) {
            $p = ($whole_amount - $drop_start) * ($y_2 - $start_percent) / ($x_2 - $drop_start) + $start_percent;
            $coeff = $p / $start_percent;
        } else if ($whole_amount > 10 * $drop_start) {
            $coeff = 0.01;
        } else {
            $coeff = 0;
        }

        if ($deposit_type === 'static') {
            $coeff = max(0.5, $coeff);
        } else if ($deposit_type === 'dynamic') {
            $coeff = max(0.4, $coeff);
        }

        return $coeff;
    }

    public static function addProfit(int $user_id, float $amount, DepositModel $deposit, string $type, int $target_id = 0, string $date = null) {
        $wallet = WalletModel::select(Where::and()
            ->set('user_id', Where::OperatorEq, $user_id)
            ->set('currency', Where::OperatorEq, $deposit->currency));

        if ($wallet->isEmpty()) {
            return;
        }

        $wallet = $wallet->first();
        /* @var \Models\WalletModel $wallet */

        $profit = new ProfitModel();
        $profit->deposit_id = $deposit->id;
        $profit->type = $type;
        $profit->user_id = $user_id;
        $profit->amount = $amount;
        $profit->wallet_id = $wallet->id;
        $profit->target_id = $target_id;
        $profit->currency = $deposit->currency;
        if ($date != null) {
            $time = strtotime($date);
            $profit->created_at_timestamp = $time;
            $profit->updated_at_timestamp = $time;
            $profit->created_at = date('Y-m-d H:i:s', $time);
        } else {
            $profit->created_at = date('Y-m-d H:i:s');
        }
        $profit->save();

        if (in_array($type, ['reinvest_profit', 'invest_profit', 'return_deposit', 'pool_profit'], true)) {
            $wallet->addProfit($amount);
        } else {
            $balance = BalanceModule::getBalanceOrCreate($user_id, $deposit->currency, BalanceModel::CATEGORY_PARTNERS);
            $balance->incrAmount($amount);
        }
    }

    public static function createPoolDeposit(int $user_id, float $amount, string $currency, $date = null): DepositModel {
        $wallet = WalletModel::select(Where::and()
            ->set('user_id', Where::OperatorEq, $user_id)
            ->set('currency', Where::OperatorEq, $currency)
        );
        if ($wallet->isEmpty()) {
            throw new \Exception('Wallet not found');
        }

        $wallet = $wallet->first();

        if ($date === null) {
            $date = date('d.m.Y');
        }

        $time = strtotime($date);
        $diff = time() - $time;

        $deposit = new DepositModel();
        $deposit->wallet_id = $wallet->id;
        $deposit->amount = $amount;
        $deposit->user_id = $user_id;
        $deposit->dynamic_percent = DepositModel::TYPE_POOL;
        $deposit->created_at = date('Y-m-d H:i:s', $time);
        $deposit->days = round($diff / 86400);
        $deposit->currency = $currency;
        $deposit->plan = KERNEL_CONFIG['pool']['plan_id'];
        $deposit->withdraw_disabled = 0;
        $deposit->dynamic_coeff = 1;
        $deposit->dynamic_profit_share = 0;
        $deposit->dynamic_profit = 0;
        $deposit->dynamic_daily_percent = 0;
        $deposit->dynamic_curr_percent = 0;
        $deposit->dynamic_start_percent = 0;
        $deposit->date_start = date('Y-m-d H:i:s', $time);
        $deposit->withdraw_disabled_coeff = 0;
        $deposit->charged_amount = $amount;
        $deposit->status = 'accepted';
        $deposit->operation = 'invest';
        $deposit->plan_percent_coeff = 1;
        $deposit->created_at_timestamp = $time;
        $deposit->updated_at_timestamp = $time;
        $deposit->save();

        return $deposit;
    }

    public static function addPoolProfit($deposit_id, $percent, $date = null) {
        if ($date === null) {
            $date = date('d.m.Y');
        }

        Transaction::wrap(function () use ($deposit_id, $percent, $date) {
            /* @var \Models\DepositModel $deposit */
            $deposit = DepositModel::get($deposit_id);
            $amount = $deposit->amount * ($percent / 100);

            /* @var UserModel $user */
            $user = UserModel::get($deposit->user_id);

            $deposit->dynamic_profit += $amount;
            $deposit->dynamic_profit_share += $amount;
            $deposit->save();

            self::addProfit($deposit->user_id, $amount, $deposit, 'pool_profit', 0, $date);
            InvestmentModule::agentsProfits($deposit, $user, $amount);
        });
    }

    public static function agentsProfits(DepositModel $deposit, UserModel $user, float $amount): void {
        $refers = $user->refer;
        if (!$refers) {
            return;
        }

        [$agent_id] = array_map('intval', explode(',', $user->refer));
        if (!$agent_id) {
            return;
        }

        try {
            /* @var \Models\UserModel $agent */
            $agent = UserModel::get($agent_id);
        } catch (\Exception $e) {
            return;
        }

        if (!$agent->role) {
            return;
        }

        try {
            /* @var \Models\RoleModel $role */
            $role = RoleModel::get($agent->role);
        } catch (\Exception $e) {
            return;
        }

        if (strtolower($role->role_name) !== 'agent') {
            return;
        }

        $created_at_ts = strtotime($deposit->created_at);
        if ($created_at_ts > NEW_AGENTS_TS && strtotime($agent->agent_date) >= $created_at_ts) {
            return;
        }

        $agent_percent = \settings()->agent_percent * 0.01;
        $agent_profit = $amount * $agent_percent;

//        InvestmentModule::addProfit($agent->id, $agent_profit, $deposit, ProfitModel::TYPE_REFERRAL_PROFIT, $deposit->user_id);

        if ($agent->representative_id > 0) {
            $representative_percent = \settings()->representative_percent * 0.01;
            $representative_profit = $amount * $representative_percent;
            // InvestmentModule::addProfit($agent->representative_id, $representative_profit, $deposit, ProfitModel::TYPE_AGENT_PROFIT, $agent->id);
        }
    }

    public static function addTokenProfit(UserModel $agent, float $amount, int $target_id) {
        if (!$agent->hasRole(UserRoleModel::AGENT_ROLE)) {
            return;
        }

        $amount = $amount / 100 * \settings()->agent_token_percent;
        $balance = BalanceModule::getBalanceOrCreate($agent->id, CURRENCY_FNDR, BalanceModel::CATEGORY_PARTNERS);

        Transaction::wrap(function() use ($agent, $amount, $balance, $target_id){
            $profit = new ProfitModel();
            $profit->type = ProfitModel::TYPE_TOKEN_PROFIT;
            $profit->user_id = $agent->id;
            $profit->target_id = $target_id;
            $profit->amount = $amount;
            $profit->currency = $balance->currency;
            $profit->created_at = date('Y-m-d H:i:s');
            $profit->save();

            $balance->incrAmount($amount);
        });
    }

    public static function getDepositType(DepositModel $deposit): string {
        $deposit_type = 'old';

        if ($deposit->dynamic_percent == DepositModel::TYPE_STATIC) {
            $deposit_type = 'static';
        } else if ($deposit->dynamic_percent == DepositModel::TYPE_DYNAMIC) {
            $deposit_type = 'dynamic';
        } else if ($deposit->dynamic_percent == DepositModel::TYPE_POOL) {
            $deposit_type = 'pool';
        }

        return $deposit_type;
    }
}
