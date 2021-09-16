<?php

namespace Modules;

use Core\Services\Merchant\CardsService;
use Core\Services\Merchant\XenditService;

class FeeModule {
    const REFILL_TYPE = 'refill';
    const WITHDRAWAL_TYPE = 'withdrawal';
    public static function getFee(float $amount, string $currency): float {
        $fee = 0;

        switch ($currency) {
            case CURRENCY_IDR:
                $refill_percents_fee = settings()->xendit_percent_fee;
                $refill_min_fee = settings()->xendit_min_fee;
                $fee = max($amount * floatval($refill_percents_fee / 100), $refill_min_fee);
                break;
            case CURRENCY_RUB:
                $refill_percents_fee = settings()->rub_refill_percent_fee;
                $fee = max($amount * floatval($refill_percents_fee / 100), CardsService::REFILL_FEE);
                break;
        }

        return $fee;
    }

    public static function amountWithoutFee($full_amount, $currency): float {
        $amount = 0;

        switch ($currency) {
            case CURRENCY_IDR:
                $refill_percents_fee = settings()->xendit_percent_fee;
                $refill_min_fee = settings()->xendit_min_fee;
                $fee = floatval($full_amount/$refill_percents_fee) <= 100 ?
                    $refill_min_fee :
                    $full_amount * floatval($refill_percents_fee / 100);
                $amount = $full_amount - $fee;
                break;
        }

        return $amount;
    }
}
