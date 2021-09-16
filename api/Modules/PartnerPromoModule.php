<?php

namespace Modules;

use Core\Services\BalanceHistory\BalanceHistoryGetter;
use Db\Where;
use Models\BalanceModel;
use Models\RatingModel;
use Models\UserBalanceHistoryModel;
use Models\UserModel;

class PartnerPromoModule {
    public static function getRating(int $user_id) {
        $ratings = RatingModel::queryBuilder()
            ->orderBy(['rank' => 'ASC'])
            ->limit(10)
            ->select();

        $ratings = RatingModel::rowsToSet($ratings);
        $in_top_ten = $ratings->filter(function (RatingModel $rating) use ($user_id) {
            return $rating->user_id === $user_id;
        });

        if (!$in_top_ten) {
            $ratings->push(RatingModel::first(Where::equal('user_id', $user_id)));
        }

        return $ratings;
    }

    public static function history(UserModel $user, int $start_from, int $count) {
        $balances = BalanceModule::getBalances($user->id, BalanceModel::CATEGORY_PARTNERS);
        $balance_ids = $balances->column('id');

        $history_getter = new BalanceHistoryGetter;
        $history_getter->setUsersIds([$user->id]);
        $history_getter->setOperations([
            UserBalanceHistoryModel::OPERATION_PROMO_REWARD,
            UserBalanceHistoryModel::OPERATION_INTERNAL_TRANSACTION,
        ]);

        $history_getter->setWhere(
            Where::or()
                ->set(Where::in('from_id', $balance_ids))
                ->set(Where::in('to_id', $balance_ids))
        );

        $transactions = $history_getter->paginateById((int) $start_from, (int) $count);

        return [$balances, $transactions];
    }
}