<?php

namespace Cron;

use Db\Transaction;
use Db\Where;
use Models\ProfitModel;
use Models\RatingModel;
use Models\UserModel;

class UpdatePartnerRating implements CronJobInterface {

    public function exec() {
        $profits = ProfitModel::select(
            Where::equal('type', ProfitModel::TYPE_PROMO_CODE_REWARD)
        );

        if ($profits->isEmpty()) {
            return;
        }

        $users = [];
        foreach ($profits as $profit) {
            /** @var ProfitModel $profit */
            $users[] = $profit->user_id;
        }

        $users = UserModel::select(Where::in('id', $users));

        $ratings = [];
        foreach ($profits as $profit) {
            /** @var ProfitModel $profit */

            $user = $users->getItem($profit->user_id);
            /** @var UserModel $user */

            if (!isset($ratings[$user->id])) {
                $ratings[$user->id] = [
                    'amount' => $profit->amount * $profit->rate,
                    'currency' => 'btc',
                    'user_id' => $user->id,
                    'user_login' => $user->login,
                    'rank' => 0,
                ];
            } else {
                $ratings[$user->id]['amount'] += $profit->amount * $profit->rate;
            }
        }

        usort($ratings, function ($item1, $item2) {
            return $item2['amount'] <=> $item1['amount'];
        });

        Transaction::wrap(function () use ($ratings) {
            RatingModel::select()->delete();

            $i = 1;
            foreach ($ratings as $rating) {
                $rating_model = new RatingModel();
                $rating_model->amount = $rating['amount'];
                $rating_model->currency = $rating['currency'];
                $rating_model->user_login = $rating['user_login'];
                $rating_model->user_id = $rating['user_id'];
                $rating_model->rank = $i;
                $rating_model->save();;
                $i++;
            }
        });

    }
}
