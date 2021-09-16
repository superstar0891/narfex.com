<?php

namespace Api\PartnerPromo;

use Core\Response\JsonResponse;
use Core\Services\Promo\CodeGeneratorService;
use Models\RatingModel;
use Modules\PartnerPromoModule;
use Serializers\BalanceHistory\HistorySerializer;
use Serializers\PagingSerializer;

class PartnerPromo {
    public static function retrieve($request) {
        /**
         * @var int $start_from
         * @var int $count
         */
        extract($request['params']);
        $user = getUser($request);
        [$balances, $transactions] = PartnerPromoModule::history($user, $start_from, $count);
        $serialized_transactions = HistorySerializer::serializeItems($transactions->getItems(), $user);

        JsonResponse::ok([
            'promo_code' => CodeGeneratorService::encodeUserId($user->id),
            'balances' => $balances->map('Serializers\BalanceSerializer::listItem'),
            'history' => PagingSerializer::detail($transactions->getNext(), $serialized_transactions),
            'rating' => PartnerPromoModule::getRating($user->id)->toJson(),
        ]);
    }

    public static function promo_code($request) {
        $user = getUser($request);
        JsonResponse::ok([
            'promo_code' => CodeGeneratorService::encodeUserId($user->id)
        ]);
    }

    public static function rating($request) {
        $user = getUser($request);
        JsonResponse::ok([
            'rating' => PartnerPromoModule::getRating($user->id)
                ->map(function (RatingModel $rating) {
                    return $rating->toJson();
                }),
        ]);
    }

    public static function history($request) {
        /**
         * @var int $start_from
         * @var int $count
         */
        extract($request['params']);
        $user = getUser($request);
        [$balances, $transactions] = PartnerPromoModule::history($user, $start_from, $count);
        $serialized_transactions = HistorySerializer::serializeItems($transactions->getItems(), $user);

        JsonResponse::ok([
            'balances' => $balances->map('Serializers\BalanceSerializer::listItem'),
            'history' => PagingSerializer::detail($transactions->getNext(), $serialized_transactions),
        ]);
    }
}