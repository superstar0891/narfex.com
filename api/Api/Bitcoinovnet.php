<?php

namespace Api\Bitcoinovnet;

use Core\Response\JsonResponse;
use Core\Services\Merchant\FastExchangeService;
use Models\AgentPromoCodeModel;
use Models\BalanceModel;
use Models\BitcoinovnetUserCardModel;
use Models\CardModel;
use Models\ReservedCardModel;
use Modules\BalanceModule;
use Modules\BitcoinovnetModule;
use Modules\ReviewModule;
use Serializers\BalanceHistory\HistorySerializer;
use Serializers\PagingSerializer;

function checkActiveReservation(?string $session_hash, ?string $request_id) {
    if (is_null($session_hash) || is_null($request_id)) {
        return;
    }
    $reservation = FastExchangeService::getReservationByHash($session_hash, $request_id);
    if (isset($reservation['reservation']) && isset($reservation['card'])) {
        JsonResponse::ok([
            'status' => 'already_booked',
            'reservation' => $reservation['reservation']->toJson(),
            'card' => $reservation['card']->toJson(),
        ]);
    }
}

function retrieve($request) {
    /**
     * @var string|null $session_hash
     * @var string|null $request_id
     */
    extract($request['params']);

    if (is_null($session_hash) || is_null($request_id)) {
        JsonResponse::error(['code' => 'not_found'], 404);
    }

    $reservation = FastExchangeService::getReservationByHash($session_hash, $request_id);
    if (isset($reservation['reservation'])) {
        JsonResponse::ok([
            'reservation' => $reservation['reservation']->toJson(),
            'card' => isset($reservation['card']) ? $reservation['card']->toJson() : null,
        ]);
    }

    JsonResponse::error(['code' => 'not_found'], 404);
}

function reservation($request) {
    /* @var string|null $card_number
     * @var string|null $card_owner_name
     * @var string $wallet_address
     * @var string $email
     * @var float $amount
     * @var string|null $promo_code
     * @var string|null $session_hash
     * @var string|null $request_id
     * @var int|null $card_id
     */
    extract($request['params']);

    if (!bitcoinovnetIsActive()) {
        JsonResponse::error([
            'code' => 'platform_not_active'
        ]);
    }

    $user = getUser($request);

    if ($user) {
        if ($user->email !== $email) {
            JsonResponse::error(['code' => 'wrong_email']);
        }
    }

    BitcoinovnetModule::floodControl(
        'bitcoinovnet_new_reservation_' . ipAddress(),
        KERNEL_CONFIG['flood_control']['bitcoinovnet_reservation']
    );

    checkActiveReservation($session_hash, $request_id);

    $reservation_data = FastExchangeService::reservation(
        $amount,
        $wallet_address,
        $card_number,
        $card_owner_name,
        $email,
        $promo_code,
        $user,
        $card_id
    );

    if ($reservation_data) {
        if (isset($reservation_data['code'])) {
            JsonResponse::error($reservation_data);
        }

        /* @var CardModel $card
         * @var ReservedCardModel $reservation
         */
        extract($reservation_data);

        if ($reservation) {
            JsonResponse::ok([
                'status' => 'ok',
                'card' => $card->toJson(),
                'reservation' => $reservation->toJson(),
            ]);
        }
    }

    JsonResponse::error([
        'code' => 'not_available_cards'
    ]);
}

function mainInfo($request) {
    JsonResponse::ok(FastExchangeService::getInitInfo(getUser($request)));
}

function validateCard($request) {
    /* @var string $session_hash
     * @var string $request_id
     */
    extract($request['params']);

    if (!isset($_FILES['file']) || !$_FILES['file']['tmp_name']) {
        JsonResponse::error([
            'code' => 'file_not_found',
        ]);
    }

    if ($_FILES['file']['size'] > FastExchangeService::MAX_UPLOAD_FILE_SIZE) {
        JsonResponse::error([
            'code' => 'bad_file_size',
            'params' => [
                'max_size' => FastExchangeService::MAX_UPLOAD_FILE_SIZE
            ]
        ]);
    }

    if (substr(mime_content_type($_FILES['file']['tmp_name']), 0, 5) !== 'image') {
        JsonResponse::error([
            'code' => 'bad_file_type',
        ]);
    }

    $data = FastExchangeService::validateCard($_FILES['file']['tmp_name'], $_FILES['file']['type'], $session_hash, $request_id);
    if (isset($data['reservation'])) {
        JsonResponse::response($data);
    } else {
        JsonResponse::error($data);
    }
}

function cancelReservation($request) {
    /* @var string $session_hash
     * @var int $request_id
     */
    extract($request['params']);

    $data = FastExchangeService::cancelReservation($session_hash, $request_id);

    if (isset($data['reservation'])) {
        JsonResponse::ok($data);
    } else {
        JsonResponse::error($data);
    }
}

function confirmPayment($request) {
    /* @var string $session_hash
     * @var int $request_id
     */
    extract($request['params']);

    $data = FastExchangeService::confirmPayment($session_hash, $request_id);

    if (isset($data['reservation'])) {
        JsonResponse::ok($data);
    } else {
        JsonResponse::error($data);
    }
}

function updateRate($request) {
    /* @var string $session_hash
     * @var string $request_id
     */
    extract($request['params']);

    BitcoinovnetModule::floodControl(
        'bitcoinovnet_update_rate_' . ipAddress(),
        KERNEL_CONFIG['flood_control']['bitcoinovnet_update_rate']
    );

    $data = FastExchangeService::updateRate($session_hash, $request_id);

    if (isset($data['reservation'])) {
        JsonResponse::ok($data);
    } else {
        JsonResponse::error($data);
    }
}

function getRateWithPromoCode($request) {
    /* @var string $promo_code */
    extract($request['params']);

    $user = getUser($request);
    $promo_code = FastExchangeService::getPromoCodeModel($promo_code);
    /* @var AgentPromoCodeModel|null $promo_code */
    if (is_null($promo_code) || ($user !== null && $user->id === $promo_code->user_id)) {
        JsonResponse::error([
            'code' => 'invalid_promo_code',
        ]);
    }
    $sale_in_percent = FastExchangeService::getSaleInPercent($promo_code);

    JsonResponse::ok([
        'sale' => round($sale_in_percent, 2)
    ]);
}

function partnersCabinet($request) {
    /** @var int $start_from */
    /** @var int $count */
    extract($request['params']);
    $user = getUser($request);
    $agent = BitcoinovnetModule::getOrCreateAgent($user);
    $promo_codes = BitcoinovnetModule::getOrCreatePromoCodes($agent);
    $paginator = BitcoinovnetModule::history($user, $start_from, $count);
    $profits_count = BitcoinovnetModule::getProfitsCount($user);
    $balance = BalanceModule::getBalanceOrCreate(
        $user->id,
        CURRENCY_RUB,
        BalanceModel::CATEGORY_BITCOINOVNET_AGENT
    );

    JsonResponse::ok([
        'history' => PagingSerializer::detail(
            $paginator->getNext(),
            HistorySerializer::serializeItems($paginator->getItems(), $user)
        ),
        'all_profits_count' => (int) $profits_count,
        'promo_codes' => $promo_codes->toJson(),
        'profit_in_percent' => (float) settings()->bitcoinovnet_agent_max_percent,
        'balance' => (float) $balance->amount,
        'withdrawal_min_balance_amount' => (float) settings()->bitcoinovnet_withdrawal_min_balance_amount,
        'min_withdrawal' => 500
    ]);
}

function partnersCabinetHistory($request) {
    /** @var int $start_from */
    /** @var int $count */
    extract($request['params']);
    $user = getUser($request);
    $paginator = BitcoinovnetModule::history($user, $start_from, $count);
    JsonResponse::ok([
        'history' => PagingSerializer::detail($paginator->getNext(), HistorySerializer::serializeItems($paginator->getItems(), $user)),
    ]);
}

function profitInPercent() {
    JsonResponse::ok([
        'profit_in_percent' => (float) settings()->bitcoinovnet_agent_max_percent,
    ]);
}

function partnersCabinetWithdrawal($request) {
    /** @var float $amount */
    /** @var string $card_number */
    extract($request['params']);
    $user = getUser($request);

    BitcoinovnetModule::floodControl(
        'bitcoinovnet_withdrawal_' . $user->id,
        KERNEL_CONFIG['flood_control']['bitcoinovnet_withdrawal']
    );

    if ($amount < 500) {
        JsonResponse::error([
            'code' => 'min_amount_error',
            'params' => [
                'amount' => 500
            ]
        ]);
    }

    $balance = BalanceModule::getBalanceOrCreate(
        $user->id,
        CURRENCY_RUB,
        BalanceModel::CATEGORY_BITCOINOVNET_AGENT
    );

    $withdrawal_min_balance_amount = settings()->bitcoinovnet_withdrawal_min_balance_amount;
    if ($balance->amount < $withdrawal_min_balance_amount) {
        JsonResponse::error([
            'code' => 'withdrawal_min_balance_error',
            'params' => [
                'amount' => (float) $withdrawal_min_balance_amount
            ]
        ]);
    }

    $res = BitcoinovnetModule::withdrawal($balance, $amount);
    if (!$res) {
        JsonResponse::error([
            'code' => 'not_enough_money',
            'params' => [
                'amount' => (float) $balance->amount
            ]
        ]);
    }

    JsonResponse::ok([
        'withdrawal' => BitcoinovnetModule::createWithdrawalRequest($balance, $amount, $card_number)->toJson()
    ]);
}

function cabinet($request) {
    /** @var int $start_from */
    /** @var int $count */
    extract($request['params']);
    $user = getUser($request);

    $paginator = BitcoinovnetModule::userReservations($user, $start_from, $count);
    $cards = BitcoinovnetModule::userCards($user);

    JsonResponse::ok([
        'history' => PagingSerializer::detail(
            $paginator->getNext(),
            $paginator->getItems()->toJson()
        ),
        'cards' => $cards->toJson(),
    ]);
}

function cabinetHistory($request) {
    /** @var int $start_from */
    /** @var int $count */
    extract($request['params']);
    $user = getUser($request);

    $paginator = BitcoinovnetModule::userReservations($user, $start_from, $count);

    JsonResponse::ok([
        'history' => PagingSerializer::detail(
            $paginator->getNext(),
            $paginator->getItems()->toJson()
        ),
    ]);
}

function cabinetCards($request) {
    $user = getUser($request);
    $cards = BitcoinovnetModule::userCards($user);

    JsonResponse::ok([
        'cards' => $cards->toJson(),
    ]);
}

function cabinetCardDelete($request) {
    /** @var int $id */
    extract($request['params']);

    $user = getUser($request);
    $card = BitcoinovnetUserCardModel::get($id);

    if ($card->user_id !== $user->id) {
        JsonResponse::accessDeniedError();
    }

    $card->delete(true);

    JsonResponse::ok([
        'response' => 'OK'
    ]);
}

function rateInXml() {
    $xml = BitcoinovnetModule::ratesXml();
    Header('Content-type: text/xml');
    print($xml->asXML());
}

function rateInXmlDownload() {
    $xml = BitcoinovnetModule::ratesXml();
    Header('Content-type: text/xml');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="rates.xml"');
    print($xml->asXML());
}

function reviews($request) {
    /**
     * @var int $page
     * @var int $count
     */
    extract($request['params']);
    $paginator = ReviewModule::bitcoinovnetReview($page, $count);

    JsonResponse::ok([
        'reviews' => PagingSerializer::classicPaginator(
            $paginator->getItems()->toJson(),
            $paginator->getTotal() ?? 0
        ),
    ]);
}

function newReview($request) {
    /**
     * @var string $name
     * @var string $content
     */
    extract($request['params']);

    BitcoinovnetModule::floodControl(
        'bitcoinovnet_new_review_' . ipAddress(),
        KERNEL_CONFIG['flood_control']['bitcoinovnet_new_review']
    );

    $review = ReviewModule::newBitcoinovnetReview($name, $content);

    JsonResponse::ok([
        'review' => $review->toJson()
    ]);
}
