<?php

namespace Api\Cards;

use Core\Response\JsonResponse;
use Core\Services\Merchant\CardsService;
use Db\Where;
use Models\BankCardModel;
use Models\BankCardOperationModel;
use Models\UserModel;

function checkActiveReservation(UserModel $user) {
    $reservation = CardsService::getUserReservation($user);
    if ($reservation) {

        JsonResponse::ok([
            'status' => 'already_booked',
            'reservation' => $reservation['operation']->toJson(),
            'card' => $reservation['card']->toJson(),
        ]);
    }
}

function refillBanksRetrieve($request) {
    $user = getUser($request);

    checkActiveReservation($user);
    JsonResponse::ok(CardsService::getBanks());
}

function reservation($request) {
    /* @var string $bank_code
     * @var float $amount
    */
    extract($request['params']);

    $user = getUser($request);

    checkActiveReservation($user);

    $reservation = CardsService::reservation($user, $bank_code, $amount);

    if ($reservation) {
        /* @var BankCardModel $card
         * @var BankCardOperationModel $operation
         */
        extract($reservation);

        if ($reservation) {
            JsonResponse::ok([
                'status' => 'ok',
                'card' => $card->toJson(),
                'reservation' => $operation->toJson(),
            ]);
        }
    }

    JsonResponse::error([
        'status' => 'not_available_cards'
    ]);
}

function cancelReservation($request) {
    /* @var string $reservation_id */
    extract($request['params']);

    $user = getUser($request);
    try {
        CardsService::cancelReservation($user, $reservation_id);
    } catch (\Exception $e) {
        JsonResponse::error($e->getMessage());
    }

    JsonResponse::ok();
}

function confirmPayment($request) {
    /* @var string $reservation_id */
    extract($request['params']);

    $user = getUser($request);
    try {
        CardsService::confirmPayment($user, $reservation_id);
    } catch (\Exception $e) {
        JsonResponse::error($e->getMessage());
    }

    JsonResponse::ok([
        'status' => BankCardOperationModel::STATUS_WAIT_FOR_REVIEW,
    ]);
}