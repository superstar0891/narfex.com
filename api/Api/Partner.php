<?php

namespace Api\Partner;

use Api\Errors;
use Core\Response\JsonResponse;
use Db\Pagination\Paginator;
use Db\Transaction;
use Db\Where;
use Models\BalanceModel;
use Models\DepositModel;
use Models\InviteLinkModel;
use Models\NotificationModel;
use Models\PlanModel;
use Models\ProfitModel;
use Models\RoleModel;
use Models\UserModel;
use Modules\BalanceModule;
use Modules\NotificationsModule;
use Modules\PartnerModule;
use Serializers\ErrorSerializer;
use Serializers\InvestmentSerializer;
use Serializers\PartnerSerializer;
use Serializers\UserSerializer;

class Partner {
    public static function retrieve($request) {
        /**
         * @var $start_from
         */
        extract($request['params']);

        $page = intval($start_from);
        $limit = Paginator::DEFAULT_COUNT;
        $user = getUser($request);

        $level = PartnerModule::getLevel($user);
        $result = [
            'level' => $level,
            'balances' => BalanceModule::getBalances($user->id, BalanceModel::CATEGORY_PARTNERS)
                ->map('Serializers\BalanceSerializers::listItem'),
        ];

        if ($level === 'representative') {
            $result = array_merge($result, PartnerModule::representativeData($user));
        } else if ($level === 'agent') {
            $result = array_merge($result, PartnerModule::agentData($user, $page, $limit));
        } else {
            $result = array_merge($result, PartnerModule::partnerData($user, $page, $limit));
        }

        $result['profit_chart'] = PartnerModule::profitChart($user->id, 30, $level);
        $result['client_chart'] = PartnerModule::clientChart($user->id, 30, $level);

        JsonResponse::ok($result);
    }

    public static function retrievePartnersOnly($request) {
        /**
         * @var string $start_from
         */
        extract($request['params']);
        $page = intval($start_from);

        $user = getUser($request);
        $level = PartnerModule::getLevel($user);
        $limit = Paginator::DEFAULT_COUNT;
        $result = [];
        if ($level === 'representative') {
            $result = array_merge($result, PartnerModule::representativeData($user));
        } else if ($level === 'agent') {
            $result = array_merge($result, PartnerModule::agentData($user, $page, $limit));
        } else {
            $result = array_merge($result, PartnerModule::partnerData($user, $page, $limit));
        }

        JsonResponse::ok($result);
    }

    public static function profitChart($request) {
        /* @var int $period
         * @var int $agent_id
         */
        extract($request['params']);

        $user = getUser($request);
        $level = PartnerModule::getLevel($user);

        $target_id = 0;
        if ($agent_id > 0 && $level === 'representative') {
            /* @var UserModel $agent */
            $agent = UserModel::get($agent_id);
            if ($agent->representative_id == $user->id) {
                $target_id = $agent->id;
            }
        }

        JsonResponse::ok(PartnerModule::profitChart($user->id, $period, $level, $target_id));
    }

    public static function clientChart($request) {
        /* @var int $period
         * @var int $agent_id
         */
        extract($request['params']);

        $user = getUser($request);
        $level = PartnerModule::getLevel($user);

        $user_id = $user->id;
        if ($agent_id > 0 && $level === 'representative') {
            /* @var UserModel $agent */
            $agent = UserModel::get($agent_id);
            if ($agent->representative_id == $user->id) {
                $user_id = $agent->id;
                $level = PartnerModule::getLevel($agent);
            }
        }

        JsonResponse::ok(PartnerModule::clientChart($user_id, $period, $level));
    }

    public static function createInviteLink($request) {
        /* @var string $name */
        extract($request['params']);

        $user = getUser($request);

        $link = new InviteLinkModel();
        $link->name = $name;
        $link->user_id = $user->id;
        $link->add_date = time();
        $link->join_count = 0;
        $link->deposits_count = 0;
        $link->view_count = 0;
        $link->save();

        JsonResponse::ok(PartnerSerializer::inviteLink($link));
    }

    public static function updateInviteLink($request) {
        /* @var string $name
         * @var int $id
         */
        extract($request['params']);

        $user = getUser($request);

        try {
            /* @var \Models\InviteLinkModel $link */
            $link = InviteLinkModel::get($id);
        } catch (\Exception $e) {
            JsonResponse::apiError();
        }

        if ($link->user_id != $user->id) {
            JsonResponse::apiError();
        }

        $link->name = $name;
        $link->save();

        JsonResponse::ok();
    }

    public static function deleteInviteLink($request) {
        /* @var int $id */
        extract($request['params']);

        $user = getUser($request);

        try {
            /* @var \Models\InviteLinkModel $link */
            $link = InviteLinkModel::get($id);
        } catch (\Exception $e) {
            JsonResponse::apiError();
        }

        if ($link->user_id != $user->id) {
            JsonResponse::apiError();
        }

        $link->delete();

        JsonResponse::ok();
    }

    public static function restoreInviteLink($request) {
        /* @var int $id */
        extract($request['params']);

        $user = getUser($request);

        try {
            /* @var \Models\InviteLinkModel $link */
            $link = InviteLinkModel::get($id, false);
        } catch (\Exception $e) {
            JsonResponse::apiError();
        }

        if ($link->user_id != $user->id) {
            JsonResponse::apiError();
        }

        $link->restore();

        JsonResponse::ok();
    }

    public static function partnerInfo($request) {
        /* @var int $login */
        extract($request['params']);

        $user = getUser($request);
        $level = PartnerModule::getLevel($user);

        $partner = UserModel::first(Where::equal('login', $login));
        if (!$partner) {
            JsonResponse::accessDeniedError();
        }
        /* @var UserModel $partner */

        $refers = explode(',', $partner->refer);
        if ($partner->representative_id != $user->id && intval($refers[0]) != $user->id) {
            JsonResponse::accessDeniedError();
        }

        $partner_level = PartnerModule::getLevel($partner);

        $result = [
            'user' => UserSerializer::detail($partner),
        ];
        $profits_result = [];
        if ($level === 'representative') {
            $result['profit_chart'] = PartnerModule::profitChart($user->id, 30, $level, $partner->id);
            $result['client_chart'] = PartnerModule::clientChart($partner->id, 30, $partner_level);

            $profits = ProfitModel::queryBuilder()
                ->columns([
                    'SUM(amount)' => 'total',
                    'currency',
                ], true)
                ->where(Where::and()
                    ->set('user_id', Where::OperatorEq, $user->id)
                    ->set('type', Where::OperatorIN, ProfitModel::TYPE_AGENT_PROFIT)
                    ->set('target_id', Where::OperatorEq, $partner->id)
                )->groupBy(['currency']);

            foreach ($profits as $profit) {
                $profits_result[$profit['currency']] = $profit;
            }
        } else if ($level === 'agent') {
            $deposits = DepositModel::select(Where::and()
                ->set('user_id', Where::OperatorEq, $partner->id)
                //->set('status', Where::OperatorEq, 'accepted')
                //->set('created_at', Where::OperatorGreater, $user->agent_date)
            );

            $plans = PlanModel::select(Where::in('id', $deposits->column('plan')), false);
            $plans_map = [];
            /* @var PlanModel $plan */
            foreach ($plans as $plan) {
                $plans_map[$plan->id] = $plan;
            }

            $profits = ProfitModel::queryBuilder()
                ->columns([
                    'SUM(amount)' => 'total',
                    'deposit_id',
                ], true)
                ->where(Where::and()
                    ->set('user_id', Where::OperatorEq, $user->id)
                    ->set('type', Where::OperatorEq, ProfitModel::TYPE_REFERRAL_PROFIT)
                    ->set('deposit_id', Where::OperatorIN, $deposits->column('id'))
                )
                ->groupBy(['deposit_id'])
                ->select();

            $profits_map = [];
            foreach ($profits as $profit) {
                $profits_map[$profit['deposit_id']] = $profit['total'];
            }

            $deposits_result = [];
            /* @var \Models\DepositModel $deposit */
            foreach ($deposits as $deposit) {
                $agent_profit = isset($profits_map[$deposit->id]) ? $profits_map[$deposit->id] : 0;
                $plan = $plans_map[$deposit->plan];

                $item = InvestmentSerializer::listItem($deposit, $plan);
                $item['agent_profit'] = (double) $agent_profit;
                $deposits_result[] = $item;
            }

            $result['deposits'] = $deposits_result;
        }

        // legacy, needs to be fixed
        $profit_types = ['token_profit'];
        $profit_types[] = $level === 'representative' ? 'agent_profit' : 'referral_profit';

        $profits = ProfitModel::queryBuilder()
            ->columns([
                'SUM(amount)' => 'total',
                'currency',
            ], true)
            ->where(Where::and()
                ->set('user_id', Where::OperatorEq, $user->id)
                ->set('type', Where::OperatorIN, $profit_types)
                ->set('target_id', Where::OperatorEq, $partner->id)
            )->groupBy(['currency'])
            ->select();

        foreach ($profits as $profit) {
            $profits_result[$profit['currency']] = $profit;
        }

        $result['profits'] = [];
        foreach (['btc', 'eth', 'ltc', CURRENCY_FNDR] as $currency) {
            if (isset($profits_result[$currency])) {
                $amount = $profits_result[$currency]['total'];
            } else {
                $amount = 0;
            }
            $result['profits'][] = PartnerSerializer::currencyProfitItem($currency, $amount);
        }

        JsonResponse::ok($result);
    }

    public static function sendInvite($request) {
        /* @var int $login */
        extract($request['params']);

        $user = getUser($request);

        /* @var RoleModel $role */
        $role = RoleModel::get($user->role);
        if (strtolower($role->role_name) !== 'representative') {
            JsonResponse::errorMessage('wrong_role');
        }

        $agent = UserModel::select(Where::equal('login', $login));
        if ($agent->isEmpty()) {
            JsonResponse::errorMessage('agent_invite_user_not_found', Errors::LOGIN_INCORRECT);
        }

        $agent = $agent->first();
        /* @var \Models\UserModel $agent */

        if ($agent->representative_id > 0) {
            JsonResponse::errorMessage('agent_invite_already_agent');
        }

        $role = RoleModel::get($agent->role);
        if (strtolower($role->role_name) !== 'user') {
            JsonResponse::errorMessage('agent_invite_already_agent');
        }

        if (!floodControl('agent_invite_' . $user->id, KERNEL_CONFIG['flood_control']['agent_invite_total'])) {
            JsonResponse::errorMessage('agent_invite_flood');
        }

        if (!floodControl('agent_invite_' . $user->id . '_' . $agent->id, KERNEL_CONFIG['flood_control']['agent_invite'])) {
            JsonResponse::errorMessage('agent_invite_already_sent');

        }

        NotificationsModule::send($agent->id, NotificationModel::TYPE_AGENT_INVITE, [
            'representative_id' => $user->id,
            'representative_login' => $user->login,
        ]);

        JsonResponse::ok();
    }

    public static function acceptInvite($request) {
        /* @var int $notify_id */
        extract($request['params']);

        $user = getUser($request);

        /* @var RoleModel $role */
        $role = RoleModel::get($user->role);
        if (in_array(strtolower($role->role_name), ['agent', 'representative'], true)) {
            JsonResponse::apiError(Errors::LOGIN_INCORRECT);
        }

        $notify = NotificationModel::select(Where::equal('id', $notify_id));
        if ($notify->isEmpty()) {
            JsonResponse::apiError(Errors::LOGIN_INCORRECT);
        }

        $notify = $notify->first();
        /* @var \Models\NotificationModel $notify */

        Transaction::wrap(function () use ($user, $notify) {
            $extra = json_decode($notify->extra, true);

            $user->representative_id = (int) $extra['representative_id'];
            $user->save();

            $notify->delete();
        });

        JsonResponse::ok();
    }

    public static function inviteLinkView($request) {
        /* @var int $link */
        extract($request['params']);

        if (!floodControl('invite_link_view' . ipAddress() . '_' . substr($link, 0, 10), KERNEL_CONFIG['flood_control']['invite_link_view'])) {
            JsonResponse::ok();
        }

        try {
            /* @var InviteLinkModel $link */
            $link = InviteLinkModel::get(InviteLinkModel::decode($link), true);
            $link->view_count += 1;
            $link->save();
        } catch (\Exception $e) {}

        JsonResponse::ok();
    }
}
