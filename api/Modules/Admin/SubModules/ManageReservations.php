<?php

namespace Admin\SubModules;

use Admin\helpers\ActionRequest;
use Admin\layout\Action;
use Core\Services\Merchant\FastExchangeService;
use Core\Services\Telegram\SendService;
use Db\Transaction;
use Models\CardModel;
use Models\ManualSessionModel;
use Models\ReservedCardModel;
use Models\UserModel;

trait ManageReservations {
    /** @var Action */
    public $reject_action;

    public $confirmed_action;

    public function manageReservationsRegisterActions() {
        $this->reject_action = $this->createAction(function(ActionRequest $request) {
            try {
                Transaction::wrap(function () use ($request) {
                    $reservation = ReservedCardModel::get($request->getParam('reservation_id'));
                    $reservation->reject();

                    $card = CardModel::get($reservation->card_id);
                    if ($card->booked == $reservation->id) {
                        $card->unbook();
                    }

                    if (null !== $reservation->session_id) {
                        ManualSessionModel::get($reservation->session_id)->incrDeclinedReservations();
                    }
                });

                return [
                    $this->showToast('Request rejected'),
                    $this->reservations_table->getReloadAction($request->getParams(), $request->getValues())
                ];
            } catch (\Exception $e) {
                return [
                    $this->showErrorToast($e->getMessage())
                ];
            }
        })->setConfirm(true, 'Do you want to reject reservation?');

        $this->confirmed_action = $this->createAction(function(ActionRequest $request) {
            $session = ManualSessionModel::getCurrentSession();

            /** @var UserModel $admin */
            $admin = $this->getAdmin();

            $admin_id = (int) $admin->id;
            $user_is_admin = $admin->isAdmin();

            if ($session instanceof ManualSessionModel && ($session->user_id !== $admin_id && !$user_is_admin)) {
                return [
                    $this->showErrorToast('Access denied'),
                ];
            }

            try {
                Transaction::wrap(function () use ($request) {
                    $reservation = ReservedCardModel::get($request->getParam('reservation_id'));

                    if (!is_null($reservation->promo_code) && $reservation->profit_id === null) {
                        $promo_code = FastExchangeService::getPromoCodeModel($reservation->promo_code);
                        if ($promo_code !== null) {
                            $final_agent_reward_percent = FastExchangeService::getAgentProfitInPercent($promo_code);
                            $agent_reward = round($reservation->amount * ($final_agent_reward_percent / 100), 2);
                            $profit = FastExchangeService::addProfitByReservation($promo_code, $reservation, $agent_reward, CURRENCY_RUB);
                            $reservation->profit_id = $profit->id;
                        }
                    }

                    $reservation->confirm();

                    $card = CardModel::get($reservation->card_id);
                    if ($card->booked == $reservation->id) {
                        $card->unbook();
                    }

                    $chat = SendService::CHAT_BITCOINOVNET;
                    if (null !== $reservation->session_id) {
                        $chat = SendService::CHAT_BITCOINOVNET_MANUAL_OPERATOR;
                        ManualSessionModel::get($reservation->session_id)->incrSuccessReservations();
                    }

                    $telegram = new SendService($chat);
                    $telegram->sendMessageSafety(
                        '#reservation_confirmed'
                        . PHP_EOL .
                        "Reservation: {$reservation->id}, Amount: {$reservation->amount}, Rate: {$reservation->current_rate}"
                    );
                });

                return [
                    $this->showToast('Request was approved'),
                    $this->reservations_table->getReloadAction($request->getParams(), $request->getValues())
                ];
            } catch (\Exception $e) {
                return [
                    $this->showErrorToast($e->getMessage())
                ];
            }
        })->setConfirm(true, 'Do you want to confirm reservation?');
    }
}