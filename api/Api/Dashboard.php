<?php

namespace Api\Dashboard;

use Core\Response\JsonResponse;
use Db\Where;
use Models\UserModel;
use Modules\InvestmentModule;
use Modules\WalletModule;

class Dashboard {
    public static function retrieve($request) {
        $user = getUser($request);

        $balances = WalletModule::getWallets($user->id)
            ->map('Serializers\WalletSerializer::listItem');

        $partners_count = UserModel::queryBuilder()
            ->columns(['COUNT(id)' => 'cnt'], true)
            ->where(Where::and()
                ->set(Where::or()
                    ->set('refer', Where::OperatorEq, $user->id)
                    ->set('refer', Where::OperatorLike, "{$user->id},%")
                )
                ->set('active', Where::OperatorEq, 1)
            )
            ->get();
        $partners_count = (int) $partners_count['cnt'];

        // Stats
        $stats = [
            [
                'type' => 'partners',
                'profit' =>  InvestmentModule::getProfit($user->id, true),
                'count' => $partners_count,
            ],
        ];

        JsonResponse::ok([
            'balances' => $balances,
            'stats' => $stats,
        ]);
    }
}
