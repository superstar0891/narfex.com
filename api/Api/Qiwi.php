<?php

namespace Api\Qiwi;

use Core\App;
use Core\Response\ContinueProcessingResponse;
use Core\Services\Merchant\FastExchangeService;
use Core\Services\Telegram\SendService;
use Db\Where;
use Engine\Debugger\Traceback;
use Models\CardModel;
use Models\MerchantPayments;

class Qiwi {
    public static function webhook($request) {
        /**
         * @var string $hookId
         * @var string $messageId
         * @var mixed $payment
         * @var bool $test
         * @var string $version
         * @var string $hash
         */
        extract($request['params']);
        ContinueProcessingResponse::ok('OK');

        if (App::isProduction() && $test) {
            return;
        }

        if (empty($payment)) {
            return;
        }

        $card = CardModel::first(Where::equal('hook_id', $hookId));
        /** @var CardModel|null $card */

        $payment_currency = $payment['sum']['currency'] == 643 ? 'RUB' : 'UNDEFINED';
        $payment_type = $payment['type'] == 'IN' ? ' #refill' : ' #withdrawal';
        $payment_status = $payment['status'];
        $payment_info = $payment_type
            . PHP_EOL .
            "Card id: {$card->id} ({$card->card_number})"
            . PHP_EOL .
            "Amount - {$payment['sum']['amount']} {$payment_currency}"
            . PHP_EOL .
            "Status - {$payment_status}"
            . PHP_EOL .
            "txnId {$payment['txnId']}"
            . PHP_EOL;

        if (is_null($card)) {
            self::error('#ERROR' . $payment_info . "Card with hook id $hookId not found");
        }

        try {
            if (App::isProduction()) {
                FastExchangeService::checkHookHash($card, $hash, $payment);
            }
        } catch (\Exception $e) {
            self::error('#ERROR' . $payment_info . $e->getMessage(), $e->getMessage());
        }

        $merchant_payment = MerchantPayments::first(Where::equal('merchant_txid', $payment['txnId']));
        $new_merchant_payment = FastExchangeService::updateOrCreateMerchantPayment($card, $payment);

        if (!is_null($merchant_payment)) {
            //Проверяем уже записанный платеж
            //Если нам пришел платеж с таким же статусом, отдаем ошибку
            //Если нам пришел платеж с другим статусом и его статус не SUCCESS, отдаем ошибку
            if (
                $merchant_payment->status === $new_merchant_payment->status
                ||
                $new_merchant_payment->status != 'SUCCESS'
            ) {
                self::error('#WARNING' . $payment_info . "Payment already exist");
            }
        }

        //Если статус платежа не SUCCESS останавливаем обработку, отправляем уведомление в телеграм
        if ($new_merchant_payment->status != 'SUCCESS') {
            self::error(
                '#WARNING' . $payment_info . "Send payment with status {$new_merchant_payment->status}"
            );
        }

        //Обработав успешный платеж, пересчитываем доступные балансы для карты
        FastExchangeService::calcAvailableAmount($card);

        if ($new_merchant_payment->type == 'OUT') {
            $amount = $payment['sum']['amount'];
            $message = "Withdrawal from balance card({$card->id}), {$amount} RUB";

            $card->decrBalance($new_merchant_payment->amount);
            self::error($payment_info, $message);
        } else {
            $card->incrBalance($new_merchant_payment->amount);
        }

        if (!$card->isBooked()) {
            self::error('#WARNING' . $payment_info . "Webhook came for an unreserved card");
        }

        try {
            $error = FastExchangeService::confirmPaymentWebhook($card, $payment);

            if (!is_null($error)) {
                self::error( '#ERROR' . $payment_info . $error, $error);
            }
        } catch (\Exception $e) {
            self::error('#ERROR' . $payment_info . $e->getMessage(), $e->getMessage());
        }
    }

    private static function error($telegram_message, $trace_back_message = null, bool $need_exit = true) {
        if ($trace_back_message !== null) {
            Traceback::debugLog($trace_back_message);
        }

        $telegram = new SendService(SendService::CHAT_BITCOINOVNET);
        $telegram->sendMessageSafety($telegram_message);
        if ($need_exit) {
            exit();
        }
    }
}
