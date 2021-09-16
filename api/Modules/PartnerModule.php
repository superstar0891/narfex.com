<?php

namespace Modules;

use Db\Pagination\Paginator;
use Db\Where;
use Models\DepositModel;
use Models\InviteLinkModel;
use Models\ProfitModel;
use Models\RoleModel;
use Models\UserModel;
use Models\UserRoleModel;
use Serializers\PartnerSerializer;
use Serializers\ProfitSerializer;
use Serializers\UserSerializer;

class PartnerModule {
    public static function getLevel(UserModel $user): string {
        /* @var RoleModel $role */
        if (!is_null($user->role)) {
            $role = RoleModel::get($user->role);
        } else {
            return UserRoleModel::USER_ROLE;
        }
        return strtolower($role->role_name);
    }

    public static function representativeData($user): array {
        $agents = UserModel::select(Where::equal('representative_id', $user->id));

        $agents_result = [];
        /* @var UserModel $agent */
        foreach ($agents as $agent) {
            $agent_id = (int) $agent->id;
            $profits = ProfitModel::queryBuilder()
                ->columns([
                    'SUM(amount)' => 'total',
                    'currency',
                ], true)
                ->where(Where::and()
                    ->set('user_id', Where::OperatorEq, $user->id)
                    ->set('type', Where::OperatorEq, 'agent_profit')
                    ->set('target_id', Where::OperatorEq, $agent->id)
                )
                ->groupBy(['currency'])
                ->select();

            $total_profit = 0;
            foreach ($profits as $profit) {
                $total_profit += WalletModule::getUsdPrice($profit['currency']) * $profit['total'];
            }

            $partners_count = UserModel::queryBuilder()
                ->columns(['COUNT(id)' => 'cnt'], true)
                ->where(Where::and()
                    ->set(Where::or()
                        ->set('refer', Where::OperatorEq, $agent_id)
                        ->set('refer', Where::OperatorLike, "{$agent_id},%")
                    )
                    ->set('active', Where::OperatorEq, 1)
                )
                ->get();
            $partners_count = (int) $partners_count['cnt'];

            $agents_result[] = PartnerSerializer::representativePartnerItem($agent, $total_profit, $partners_count);
        }

        return [
            'clients' => $agents_result,
        ];
    }

    public static function agentData(UserModel $user, int $page, int $limit): array {
        $clientsPaginator = self::getClients($user->id, $page, $limit);
        $clients = $clientsPaginator->getItems();
        $deposits = DepositModel::queryBuilder()
            ->columns([
                'COUNT(id)' => 'cnt',
                'user_id'
            ], true)
            ->where(Where::in('user_id', $clients->column('id')))
            ->groupBy(['user_id'])
            ->select();

        $deposits_map = [];
        foreach ($deposits as $deposit) {
            $deposits_map[$deposit['user_id']] = $deposit['cnt'];
        }

        $profits = ProfitModel::queryBuilder()
            ->columns([
                'SUM(amount)' => 'total',
                'currency',
                'target_id'
            ], true)
            ->where(Where::and()
                ->set('user_id', Where::OperatorEq, $user->id)
                ->set('type', Where::OperatorEq, 'referral_profit')
                ->set('target_id', Where::OperatorIN, $clients->column('id'))
            )
            ->groupBy(['currency', 'target_id'])
            ->select();

        $profits_map = [];
        foreach ($profits as $profit) {
            if (!isset($profits_map[$profit['target_id']])) {
                $profits_map[$profit['target_id']] = [];
            }

            $profits_map[$profit['target_id']][] = $profit;
        }

        $clients_result = [];
        /* @var \Models\UserModel $client */
        foreach ($clients as $client) {
            $deposits = isset($deposits_map[$client->id]) ? $deposits_map[$client->id] : 0;
            $profits = isset($profits_map[$client->id]) ? $profits_map[$client->id] : [];

            $total_profit = 0;
            foreach ($profits as $profit) {
                $total_profit += WalletModule::getUsdPrice($profit['currency']) * $profit['total'];
            }

            $clients_result[] = PartnerSerializer::agentPartnerItem($client, $total_profit, $deposits);
        }

        // Invite Links
        $links = InviteLinkModel::select(Where::equal('user_id', $user->id));
        $links = $links->map('Serializers\PartnerSerializer::inviteLink');

        $default_link = new InviteLinkModel();
        $default_link->view_count = 0;
        $default_link->join_count = 0;
        $default_link->deposits_count = 0;
        $default_link->name = settings()->host . '/?ref=' . $user->login;
        $default_link->user_id = $user->id;

        $default_link_serialized = PartnerSerializer::inviteLink($default_link);
        $default_link_serialized['link'] = $default_link->name;

        $links = array_merge([$default_link_serialized], $links);

        return [
            'clients' => [
                'items' => $clients_result,
                'next' => $clientsPaginator->getNext(),
                'total' => $clientsPaginator->getTotal()
            ],
           'links' => $links,
        ];
    }

    public static function partnerData($user, int $page, int $limit): array {
        $clientsPaginator = self::getClients($user->id, $page, $limit);
        $clients = $clientsPaginator->getItems();
        $deposits = DepositModel::select(Where::and()->set('user_id', Where::OperatorIN, $clients->column('id')));

        $deposits_map = [];
        /* @var \Models\DepositModel $deposit */
        foreach ($deposits as $deposit) {
            if (isset($deposits_map[$deposit->user_id])) {
                continue;
            }
            $deposits_map[$deposit->user_id] = WalletModule::getUsdPrice($deposit->currency) * ($deposit->amount * DepositModel::REFERRAL_PROFIT);
        }

        $clients_result = [];
        /* @var \Models\UserModel $client */
        foreach ($clients as $client) {
            $clients_result[] = [
                'user' => UserSerializer::detail($client),
                'profit' => isset($deposits_map[$client->id]) ? $deposits_map[$client->id] : 0,
            ];
        }

        return [
            'clients' => [
                'items' => $clients_result,
                'next' => $clientsPaginator->getNext(),
                'total' => $clientsPaginator->getTotal()
            ],
        ];
    }

    public static function getClients(int $user_id, int $page, int $count): Paginator {
        $builder = UserModel::queryBuilder();
        $where = Where::and()->set(Where::or()
            ->set('refer', Where::OperatorEq, $user_id)
            ->set('refer', Where::OperatorLike, "{$user_id},%")
        )
            ->set('active', Where::OperatorEq, 1);

        $res = $builder->columns([])
            ->where($where)
            ->orderBy(['join_date' => 'DESC'])
            ->paginate($page, $count);

        return $res;
    }

    public static function profitChart(int $user_id, int $period, string $level, int $target_id = 0) {
        $from_date = strtotime('-' . $period . ' days');

        $where = Where::and()
            ->set('user_id', Where::OperatorEq, (int) $user_id)
            ->set('type', Where::OperatorEq, $level === 'representative' ? 'agent_profit' : 'referral_profit')
            ->set('UNIX_TIMESTAMP(created_at)', Where::OperatorGreaterEq, $from_date);

        if ($target_id > 0) {
            $where->set('target_id', Where::OperatorEq, $target_id);
        }

        $profits = ProfitModel::select($where);

        $result = [];
        $usd_profit = 0;
        /* @var \Models\ProfitModel $profit */
        foreach ($profits as $profit) {
            if (!isset($total_profit[$profit->currency])) {
                $total_profit[$profit->currency] = 0;
                $result[$profit->currency] = [];
            }

            $date = date('d-m-Y', strtotime($profit->created_at));
            if (!isset($result[$profit->currency][$date])) {
                $result[$profit->currency][$date] = ProfitSerializer::profitChartItem($profit);
            } else {
                $result[$profit->currency][$date]['amount'] += $profit->amount;
                $result[$profit->currency][$date]['usd_amount'] += WalletModule::getUsdPrice($profit->currency) * $profit->amount;
            }

            $usd_profit += WalletModule::getUsdPrice($profit->currency) * $profit->amount;
        }

        foreach ($result as $currency => $items) {
            $result[$currency] = array_values($items);
        }

        return [
            'usd_profit' => (double) $usd_profit,
            'data' => $result
        ];
    }

    public static function clientChart(int $user_id, int $period, string $level) {
        $from_date = strtotime('-' . $period . ' days');

        $where = Where::and()
            ->set('UNIX_TIMESTAMP(join_date)', Where::OperatorGreaterEq, $from_date)
            ->set('active', Where::OperatorEq, 1);

        if ($level === 'representative') {
            $where->set('representative_id', Where::OperatorEq, $user_id);
        } else {
            $where->set(Where::or()
                ->set('refer', Where::OperatorEq, $user_id)
                ->set('refer', Where::OperatorLike, "{$user_id},%")
            );
        }

        $rows = UserModel::queryBuilder()
            ->columns([
                'COUNT(id)' => 'cnt',
                'join_date'
            ], true)
            ->where($where)
            ->groupBy(['join_date'])
            ->select();

        if (empty($rows)) {
            return [
                'total' => (int) 0,
                'data' => [],
            ];
        }

        $avail_map = [];
        $total = 0;
        foreach ($rows as $row) {
            $total += $row['cnt'];
            $avail_map[$row['join_date']] = $row;
        }

        $data = [];
        for ($i = 0; $i < $period; $i++) {
            $date = date('Y-m-d', strtotime('-' . $i . ' days'));

            if (isset($avail_map[$date])) {
                $count = $avail_map[$date]['cnt'];
            } else {
                $count = 0;
            }
            $data[] = PartnerSerializer::clientChartItem($count, $date);
        }

        return [
            'total' => (int) $total,
            'data' => $data,
        ];
    }
}
