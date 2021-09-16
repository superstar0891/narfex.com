<?php

namespace Cron;

use Core\Services\Mail\MailAdapter;
use Core\Services\Mail\Templates;
use Core\Services\Merchant\FastExchangeService;
use Core\Services\Telegram\SendService;
use Db\Transaction;
use Db\Where;
use Models\CardModel;
use Models\ManualSessionModel;
use Models\ReservedCardModel;
use Modules\FiatWalletModule;

class MerchantCardCronJob implements CronJobInterface {
    public function exec() {
        $this->releaseExpiredCards();
        $this->updateRates();
    }

    public function releaseExpiredCards() {
        $cards = CardModel::select(Where::and()
            ->set('booked', Where::OperatorGreater, 0)
            ->set('book_expiration', Where::OperatorLowerEq, time())
        );

        $reservations = ReservedCardModel::select(Where::and()
            ->set(Where::in('card_id', $cards->column('id')))
            ->set(Where::in('status', [
                ReservedCardModel::STATUS_WAIT_FOR_PAY,
                ReservedCardModel::STATUS_WAIT_FOR_SEND
            ]))
            ->set(Where::equal('operation', ReservedCardModel::OPERATION_BUY))
        );

        $reservations_by_cards = [];
        foreach ($reservations as $reservation) {
            /* @var ReservedCardModel $reservation */
            $reservations_by_cards[$reservation->card_id][] = $reservation;
        }

        Transaction::wrap(function () use ($cards, $reservations_by_cards) {
            foreach ($cards as $card) {
                /* @var CardModel $card */

                $reservations = $reservations_by_cards[$card->id] ?? [];

                foreach ($reservations as $reservation) {
                    /* @var ReservedCardModel $reservation */
                    $reservation->status = ReservedCardModel::STATUS_EXPIRED;
                    $reservation->save();

                    if (null !== $reservation->session_id) {
                        ManualSessionModel::get($reservation->session_id)->incrExpiredReservations();
                    }
                    
                    if ($reservation->email) {
                        MailAdapter::sendBitcoinovnet(
                            $reservation->email,
                            'Заявка просрочена',
                            Templates::EXPIRED_BITCOINOVNET,
                            $reservation->toJsonReservationEmailInfo()
                        );
                    }
                }

                $card->unbook();
            }
        });
    }
    
    public function updateRates() {
        $cards = CardModel::select(Where::and()
            ->set('booked', Where::OperatorGreater, 0)
            ->set('book_expiration', Where::OperatorGreater, time())
        );

        $reservations = ReservedCardModel::select(Where::and()
            ->set(Where::in('card_id', $cards->column('id')))
            ->set('rate_update_at_timestamp', Where::OperatorLowerEq, time())
            ->set(Where::in('status', [
                ReservedCardModel::STATUS_WAIT_FOR_PAY,
                ReservedCardModel::STATUS_WAIT_FOR_SEND
            ]))
            ->set(Where::equal('operation', ReservedCardModel::OPERATION_BUY))
        );

        Transaction::wrap(function () use ($cards, $reservations) {
            $new_rate = FiatWalletModule::swapRate(CURRENCY_RUB, CURRENCY_BTC);
            foreach ($reservations as $reservation) {
                /** @var ReservedCardModel $reservation */
                $new_rate = $new_rate ?: $reservation->current_rate;

                if (!is_null($reservation->promo_code)) {
                    $new_rate = FastExchangeService::calcNewRateByPromoCode($new_rate, $reservation->promo_code) ?: $new_rate;
                }

                $change_rate_percent = (($new_rate - $reservation->initial_rate) / $reservation->initial_rate) * 100;

                if ($change_rate_percent > 0 && $change_rate_percent >= settings()->bitcoinovnet_max_change_course) {
                    $reservation->moderation();

                    try {
                        $telegram = new SendService(SendService::CHAT_BITCOINOVNET);
                        $telegram->sendMessage(
                            '#WARNING #need_moderation @NikitaRadio' .
                            PHP_EOL .
                            "too much change in rate, reservation id: {$reservation->id}, change in percent: {$change_rate_percent}%"
                        );
                    } catch (\Exception $e) {
                        //
                    }
                    continue;
                }

                $reservation->rate_update_at_timestamp = time() + settings()->bitcoinovnet_rate_update;
                $reservation->current_rate = $new_rate;
                $reservation->save();
            }
        });
    }
}
