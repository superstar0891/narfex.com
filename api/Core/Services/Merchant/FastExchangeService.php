<?php

namespace Core\Services\Merchant;

use Core\App;
use Core\Blockchain\Factory;
use Core\Services\BalanceHistory\BalanceHistorySaver;
use Core\Services\Mail\MailAdapter;
use Core\Services\Mail\Templates;
use Core\Services\Qiwi\QiwiService;
use Core\Services\Storage\FileManager;
use Core\Services\Telegram\SendService;
use Db\Model\Exception\ModelNotFoundException;
use Db\Transaction;
use Db\Where;
use Exception;
use Exceptions\WithdrawalRequests\EnoughMoneyTransferException;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Models\AgentModel;
use Models\AgentPromoCodeModel;
use Models\BalanceModel;
use Models\BitcoinovnetUserCardModel;
use Models\CardModel;
use Models\ManualSessionModel;
use Models\MerchantPayments;
use Models\ProfitModel;
use Models\ReservedCardModel;
use Models\TransactionModel;
use Models\UserBalanceHistoryModel;
use Models\UserModel;
use Modules\BalanceModule;
use Modules\BitcoinovnetModule;
use Modules\FiatWalletModule;
use Modules\WalletModule;
use Serializers\BitcoinovnetUserSerializer;

class FastExchangeService {
    const PROD_WEBHOOK_DOMAIN = 'https://api.bitcoinov.net',
        DEV_WEBHOOK_DOMAIN = 'https://api-stage.narfex.dev';

    const MAX_UPLOAD_FILE_SIZE = 15000000;

    public static function getInitInfo(UserModel $user = null): array {
        return [
            'max_file_upload_size' => self::MAX_UPLOAD_FILE_SIZE,
            'available_balance' => (float) static::getAvailableBalanceInBtc(),
            'transaction_max_amount' => (float) settings()->bitcoinovnet_max_transaction_amount,
            'transaction_min_amount' => (float) settings()->bitcoinovnet_min_transaction_amount,
            'active' => (bool) bitcoinovnetIsActive(),
            'user' => $user ? BitcoinovnetUserSerializer::serialize($user) : null,
            'book_time' =>  settings()->getBitcoinovnetBookTime()
        ];
    }

    public static function getAvailableBalanceInBtc(): float {
        $reservations = ReservedCardModel::select(Where::and()
            ->set(Where::in('status', [
                ReservedCardModel::STATUS_WAIT_FOR_PAY,
                ReservedCardModel::STATUS_WAIT_FOR_SEND,
            ]))
        );

        $available_balance = settings()->bitcoinovnet_btc_balance;

        if ($reservations->isEmpty()) {
            return $available_balance;
        }

        $current_rate = FiatWalletModule::swapRate(CURRENCY_RUB, CURRENCY_BTC);
        foreach ($reservations->column('amount') as $amount) {
            $available_balance -= $amount / $current_rate;
        }

        return $available_balance;
    }

    public static function getReservationByHash(string $hash, string $request_id): ?array {
        $reservations = ReservedCardModel::select(Where::and()
            ->set('request_id', Where::OperatorEq, $request_id)
            ->set('hash', Where::OperatorEq, $hash)
        );

        try {
            $reservation = $reservations->first();
            /** @var ReservedCardModel $reservation */
        } catch (ModelNotFoundException $e) {
            return null;
        }

        $card = CardModel::first(Where::and()
            ->set('booked', Where::OperatorEq, $reservation->id)
            ->set('active', Where::OperatorEq, 1)
            ->set('card_id', $reservation->card_id));
        /* @var CardModel $card */

        if (!$card) {
            return [
                'reservation' => $reservation,
            ];
        }

        return [
            'card' => $card,
            'reservation' => $reservation,
        ];
    }

    public static function reservation(
        float $amount,
        string $wallet_address,
        ?string $card_number,
        ?string $card_owner_name,
        ?string $email,
        ?string $promo_code,
        ?UserModel $user_model = null,
        ?int $card_id = null): ?array {
        $initial_rate = FiatWalletModule::swapRate(CURRENCY_RUB, CURRENCY_BTC);
        if (!is_null($promo_code)) {
            $initial_rate = static::calcNewRateByPromoCode($initial_rate, $promo_code);
            if (is_null($initial_rate)) {
                return [
                    'code' => 'invalid_promo_code',
                ];
            }
        }
        $current_rate = $initial_rate;
        /**
         * @var float $available_balance
         * @var float $transaction_max_amount
         * @var float $transaction_min_amount
         * @var int $max_file_upload_size
         */
        extract(FastExchangeService::getInitInfo($user_model));
        if ($available_balance < (settings()->bitcoinovnet_min_transaction_amount / $current_rate)) {
            return [
                'code' => 'not_enough_money',
            ];
        }

        if ($amount < $transaction_min_amount) {
            return [
                'code' => 'transaction_wrong_min_amount',
                'params' => [
                    'amount' => formatNum($transaction_min_amount, 2)
                ],
            ];
        }

        if ($amount > $transaction_max_amount) {
            return [
                'code' => 'transaction_wrong_max_amount',
                'params' => [
                    'amount' => formatNum($transaction_max_amount, 2)
                ],
            ];
        }

        $user_card = null;

        if (!is_null($user_model) && !is_null($card_id)) {
            $user_card = BitcoinovnetUserCardModel::first(Where::and()
                ->set(Where::equal('user_id', $user_model->id))
                ->set(Where::equal('validated', 1))
                ->set(Where::equal('id', $card_id))
            );

            if (is_null($user_card)) {
                return [
                    'code' => 'invalid_card',
                ];
            }

            $card_number = $user_card->card_number;
            $card_owner_name = $user_card->card_owner;
        }

        if (!is_null($promo_code)) {
            $promo_code_model = FastExchangeService::getPromoCodeModel($promo_code);

            if (is_null($promo_code_model)) {
                return [
                    'code' => 'invalid_promo_code',
                ];
            }

            $user_by_promo_code = UserModel::get($promo_code_model->user_id);

            if (is_null($user_model)) {
                if ($user_by_promo_code->email === $email) {
                    return [
                        'code' => 'invalid_promo_code',
                    ];
                }
            } elseif ($user_model->email === $user_by_promo_code->email) {
                return [
                    'code' => 'invalid_promo_code',
                ];
            }
        }

        if (is_null($card_number) || is_null($card_owner_name)) {
            return [
                'code' => 'invalid_card',
            ];
        }

        $card_number = preg_replace('/[^0-9]/', '', $card_number);

        if (strlen($card_number) !== 20 && strlen($card_number) !== 16) {
            return [
                'code' => 'invalid_card',
            ];
        }

        $manual_session = ManualSessionModel::getCurrentSession();
        $card = static::getAvailableCard($amount, $manual_session);

        if (!$card) {
            return null;
        }

        if (!self::checkCardNumber($card, $card_number)) {
            return [
                'code' => 'incorrect_card'
            ];
        }

        return Transaction::wrap(function () use (
            $manual_session,
            $initial_rate,
            $card_number,
            $card_owner_name,
            $email,
            $amount,
            $card,
            $wallet_address,
            $promo_code,
            $user_card,
            $user_model
        ) {
            $hash = bin2hex(openssl_random_pseudo_bytes(32));
            $request_id = random_int(100000000000, 999999999999);
            $validate = 0;

            $reservation = new ReservedCardModel();
            $reservation->request_id = $request_id;
            $reservation->card_id = $card->id;
            $reservation->wallet_address = $wallet_address;
            $reservation->amount = ceil($amount);
            $reservation->fee = 0;
            $reservation->operation = ReservedCardModel::OPERATION_BUY;
            $reservation->status = ReservedCardModel::STATUS_WAIT_FOR_PAY;
            $reservation->currency = CURRENCY_BTC;
            $reservation->card_number = preg_replace('/\D/', '', $card_number);
            $reservation->card_owner_name = $card_owner_name;
            $reservation->current_rate = $initial_rate;
            $reservation->initial_rate = $initial_rate;
            $reservation->rate_update_at_timestamp = time() + settings()->bitcoinovnet_rate_update;
            $reservation->hash = $hash;
            $reservation->validate = 0;
            $reservation->promo_code = $promo_code;
            $reservation->session_id = $manual_session instanceof ManualSessionModel ? $manual_session->id : null;
            $reservation->user_id = is_null($user_model) ? null : $user_model->id;
            if ($email) {
                $reservation->email = $email;
            }
            if (!is_null($user_card)) {
                $validate = $user_card->validated;
            }
            $reservation->validate = $validate;
            $reservation->save();

            if ($manual_session instanceof ManualSessionModel) {
                $manual_session->incrReservations();
            }

            if ($email) {
                MailAdapter::sendBitcoinovnet(
                    $email,
                    'Заявка создана',
                    Templates::CREATE_BITCOINOVNET,
                    $reservation->toJsonReservationEmailInfo()
                );
            }

            $card->booked($reservation);
            static::sendTelegramNotify(
                $card,
                '#new_reservation'
                . PHP_EOL .
                "Reserved card: {$card->id}, Amount: {$amount}, Rate: {$initial_rate}"
            );

            return [
                'card' => $card,
                'reservation' => $reservation,
            ];
        });
    }

    public static function validateCard(
        string $file_path,
        string $file_type,
        ?string $session_hash,
        ?string $request_id): array {

        /** @var ReservedCardModel|null $reservation
         * @var CardModel|null $card
         */
        extract(FastExchangeService::getReservationByHash($session_hash, $request_id));

        if (is_null($reservation) || is_null($card)) {
            return [
                'code' => 'operation_not_found',
            ];
        }

        try {
            $text = '';
            $validate_card = false;
            $validate_request_id = false;

            $image_annotator = new ImageAnnotatorClient();
            $image = file_get_contents($file_path);
            $response = $image_annotator->textDetection($image);

            $full_text_annotation = $response->getFullTextAnnotation();

            if (!is_null($full_text_annotation)) {
                $text = preg_replace(
                    ['/\s+/', '/[oO]/', '/[iI]/', '/[b]/'],
                    ['', '0', '1', '6'],
                    $full_text_annotation->getText()
                );
                $image_annotator->close();

                $validate_card = matchSubstrInText($text, $reservation->card_number);
                $validate_request_id = matchSubstrInText($text, $reservation->request_id, 2);
            }
        } catch (\Exception $e) {
            //
        }

        $type = str_replace('image/', '', $file_type);

        $file_manager = new FileManager(FileManager::STORAGE_LOCAL);
        $file_name = $reservation->getRequestId() . '.' . $type;
        $res = $file_manager->getStorage()->upload($file_path, 'cards/' . $file_name);

        if (!$res) {
            return ['code' => 'failed_to_upload_photo',];
        }

        if ($validate_card === false || $validate_request_id === false) {
            if (App::isProduction()) {
                return [
                    'code' => 'validation_failed',
                ];
            } /*else {
                return [
                    'code' => 'validation_failed',
                    'text' => $text,
                    $reservation->card_number,
                    $reservation->request_id,
                    $validate_card,
                    $validate_request_id
                ];
            }*/
        }

        $reservation = Transaction::wrap(function () use ($reservation, $file_name) {
            $reservation->photo_name = $file_name;
            $reservation->save();

            $reservation->validate();
            BitcoinovnetModule::getOrCreateUserAfterReservation($reservation);
            return $reservation;
        });

        return [
            'reservation' => $reservation->toJson(),
        ];
    }

    public static function cancelReservation(string $hash, string $request_id) {
        $reservation = ReservedCardModel::first(Where::and()
            ->set(Where::equal('request_id', $request_id))
            ->set(Where::equal('hash', $hash))
            ->set(Where::equal('operation', ReservedCardModel::OPERATION_BUY))
            ->set(Where::in('status', [
                ReservedCardModel::STATUS_WAIT_FOR_PAY,
                ReservedCardModel::STATUS_WAIT_FOR_SEND,
                ReservedCardModel::STATUS_MODERATION,
                ReservedCardModel::STATUS_BLOCKCHAIN_START_SEND,
            ]))
        );

        if (!$reservation) {
            return [
                'code' => 'operation_not_found',
            ];
        }
        /* @var ReservedCardModel $reservation */

        $card = CardModel::first(Where::and()
            ->set('id', Where::OperatorEq, $reservation->card_id)
            ->set('active', Where::OperatorEq, 1));
        /* @var CardModel $card */

        Transaction::wrap(function () use ($card, $reservation) {
            if ($card && $card->isBookedBy($reservation)) {
                $card->unbook();
            }
            $reservation->cancelled();
        });

        return [
            'reservation' => $reservation->toJson()
        ];
    }

    public static function confirmPayment(string $hash, string $request_id) {
        $reservation = ReservedCardModel::first(Where::and()
            ->set(Where::equal('request_id', $request_id))
            ->set(Where::equal('hash', $hash))
            ->set(Where::equal('operation', ReservedCardModel::OPERATION_BUY))
            ->set(Where::equal('validate', 1))
            ->set(Where::in('status', [
                ReservedCardModel::STATUS_WAIT_FOR_PAY,
                ReservedCardModel::STATUS_CONFIRMED,
            ]))
        );

        if (!$reservation) {
            return [
                'code' => 'operation_not_found',
            ];
        }

        if ($reservation->status == ReservedCardModel::STATUS_WAIT_FOR_PAY) {
            /* @var ReservedCardModel $operation */
            $reservation->status = ReservedCardModel::STATUS_WAIT_FOR_SEND;
            $reservation->save();
        }

        return [
            'reservation' => $reservation->toJson()
        ];
    }

    public static function updateRate(string $hash, string $request_id) {
        $reservation = ReservedCardModel::first(Where::and()
            ->set(Where::equal('request_id', $request_id))
            ->set(Where::equal('hash', $hash))
            ->set(Where::equal('operation', ReservedCardModel::OPERATION_BUY))
        );

        if (!$reservation) {
            return [
                'code' => 'operation_not_found',
            ];
        }

        /* @var ReservedCardModel $reservation */
        if ($reservation->status != ReservedCardModel::STATUS_CONFIRMED) {
            $new_rate = FiatWalletModule::swapRate(CURRENCY_RUB, CURRENCY_BTC);
            if ($new_rate === false) {
                $new_rate = $reservation->current_rate;
            }

            if (!is_null($reservation->promo_code)) {
                $new_rate = static::calcNewRateByPromoCode($new_rate, $reservation->promo_code) ?: $new_rate;
            }

            $reservation->rate_update_at_timestamp = time() + settings()->bitcoinovnet_rate_update;
            $reservation->current_rate = $new_rate;
            $reservation->save();
        }

        return [
            'reservation' => $reservation->toJson()
        ];
    }

    public static function registerHook(CardModel $card): string {
        $webhook_service = new QiwiService($card->oauth_token);
        if (App::isProduction()) {
            $url = self::PROD_WEBHOOK_DOMAIN;
        } else {
            $url = self::DEV_WEBHOOK_DOMAIN;
        }
        $url .= '/api/v1/qiwi/webhook';

        $hook = $webhook_service->registerHook(1, $url, 2);
        return $hook['hookId'];
    }

    public static function changeHook(CardModel $card): string {
        $webhook_service = new QiwiService($card->oauth_token);
        $hook = $webhook_service->getActiveHooks();
        $webhook_service->deleteHook($hook['hookId']);
        return self::registerHook($card);
    }

    public static function getSecretKey(CardModel $card): string {
        $webhook_service = new QiwiService($card->oauth_token);
        $hook = $webhook_service->getSecretKey($card->hook_id);
        return $hook['key'];
    }

    public static function getCardBalance(CardModel $card): float {
        $wallet_service = new QiwiService($card->oauth_token);
        $response = $wallet_service->getBalances($card->wallet_number);
        $account = current(array_filter($response['accounts'], function (array $account) {
            return $account['alias'] == 'qw_wallet_rub';
        }));
        return $account['balance']['amount'];
    }

    /**
     * @param CardModel $card
     * @param array $payment
     * @throws Exception
     * @return string|null
     */
    public static function confirmPaymentWebhook(CardModel $card, array $payment): ?string {
        $telegram = new SendService(SendService::CHAT_BITCOINOVNET);

        $reservations = ReservedCardModel::select(Where::and()
            ->set(Where::equal('card_id', $card->id))
            ->set(Where::equal('operation', ReservedCardModel::OPERATION_BUY))
            ->set(Where::equal('validate', 1))
            ->set(Where::in('status', [
                ReservedCardModel::STATUS_WAIT_FOR_PAY,
                ReservedCardModel::STATUS_WAIT_FOR_SEND
            ]))
        );

        if ($reservations->count() == 0) {
            return "Get payment {$payment['txnId']}, but reservations not found, card id: {$card->id}";
        } elseif ($reservations->count() > 1) {
            Transaction::wrap(function () use ($reservations, $telegram) {
                foreach ($reservations as $reservation) {
                    /** @var ReservedCardModel $reservation */
                    $reservation->moderation();
                }

                $telegram->sendMessageSafety(
                    '#WARNING #need_moderation @NikitaRadio'
                    . PHP_EOL .
                    'Reservations: ' . implode(',', $reservations->column('id'))
                );
            });

            return 'Reservations more then one, card id:' . $card->id;
        }

        $reservation = $reservations->first();
        /** @var ReservedCardModel $reservation */

        if (!is_null($reservation->txid)) {
            if ($reservation->status != ReservedCardModel::STATUS_CONFIRMED) {
                $reservation->moderation();
            }
            return null;
        }

        if (App::isProduction()) {
            $instance = Factory::getBtcBitcoinovnetInstance();
            $balance = $instance->getWalletInfo()['balance'];
        } else {
            $instance = null;
            $balance = 100000000;
        }
        $got_amount = $payment['sum']['amount'];
        if ($balance < $got_amount / $reservation->current_rate) {
            $reservation->moderation();
            throw new EnoughMoneyTransferException('not enough money to transfer founds');
        }

        try {
            $reservation->got_amount = $got_amount;
            self::checkReservationAmount($card, $reservation, $got_amount);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        $reservation->status = ReservedCardModel::STATUS_BLOCKCHAIN_START_SEND;
        $reservation->save();

        $btc_amount = round($reservation->amount / $reservation->current_rate, 8);
        [$reservation, $txid] = Transaction::wrap(function () use ($reservation, $instance, $btc_amount) {
            if (App::isProduction()) {
                $txid = $instance->sendToAddress(
                    null,
                    $reservation->wallet_address,
                    $btc_amount,
                    null
                );

                BitcoinovnetModule::updateBitcoinovnetBalance();
            } else {
                $settings = settings();
                $settings->decrBitcoinovnetBtcBalance($btc_amount);

                $txid = bin2hex(openssl_random_pseudo_bytes(32));
            }
            WalletModule::createTransaction('send', CURRENCY_BTC, $btc_amount, [
                'status' => TransactionModel::STATUS_UNCONFIRMED,
                'txid' => $txid,
                'to' => $reservation->wallet_address,
                'platform' => PLATFORM_BITCOINOVNET,
            ]);
            $reservation->txid = $txid;
            $reservation->save();

            return [$reservation, $txid];
        });

        Transaction::wrap(function () use ($card, $reservation, $payment, $instance, $telegram, $txid, $btc_amount) {
            $currency = CURRENCY_RUB;
            BitcoinovnetModule::addLong($btc_amount);
            static::updateOrCreateMerchantPayment($card, $payment, $reservation, $txid);
            $card->unbook();

            if (!is_null($reservation->promo_code) && $reservation->profit_id === null) {
                $promo_code = static::getPromoCodeModel($reservation->promo_code);
                if ($promo_code !== null) {
                    $final_agent_reward_percent = static::getAgentProfitInPercent($promo_code);
                    $agent_reward = round($reservation->amount * ($final_agent_reward_percent / 100), 2);
                    $profit = static::addProfitByReservation($promo_code, $reservation, $agent_reward, $currency);
                    $reservation->profit_id = $profit->id;
                }
            }

            $reservation->confirm();

            $telegram = new SendService(SendService::CHAT_BITCOINOVNET);
            $telegram->sendMessageSafety(
                '#reservation_confirmed'
                . PHP_EOL .
                "Reservation: {$reservation->id}, Amount: {$reservation->amount}, Rate: {$reservation->current_rate}"
            );
        });

        return null;
    }

    /**
     * @param CardModel $card
     * @param $hash
     * @param array $payment
     * @throws Exception
     */
    public static function checkHookHash(CardModel $card, $hash, array $payment): void {
        if (!isset($payment['signFields'])) {
            throw new Exception('Hash validation failed');
        }

        $keys = explode(',', $payment['signFields']);
        $sign_params = [];
        foreach ($keys as $key) {
            $arr = $payment;
            $arr_keys = explode('.', $key);

            while ($arr_key = array_shift($arr_keys)) {
                if (is_array($arr[$arr_key])) {
                    $arr = &$arr[$arr_key];
                } else {
                    $sign_params[] = $arr[$arr_key];
                }
            }
        }

        $sign_params = implode('|', $sign_params);
        $reqres = hash_hmac('sha256', $sign_params, base64_decode($card->getSecretDecoded()));

        if (hash_equals($reqres, $hash)) {
            return;
        }

        throw new Exception('Invalid hash');
    }

    /**
     * @param CardModel $card
     * @param ReservedCardModel $reservation
     * @param float $got_amount
     * @throws Exception
     */
    private static function checkReservationAmount(CardModel $card, ReservedCardModel $reservation, float $got_amount) {
        if ($reservation->amount <= $got_amount) {
            return;
        }

        Transaction::wrap(function () use ($reservation, $card, $got_amount) {
            $reservation->status = ReservedCardModel::STATUS_WRONG_AMOUNT;
            $reservation->save();

            MailAdapter::sendBitcoinovnet(
                $reservation->email,
                'Неверная сумма пополнения',
                Templates::WRONG_AMOUNT_BITCOINOVNET,
                $reservation->toJsonReservationEmailInfo()
            );

            $card->unbook();
        });

        throw new Exception(
            'The amount of the reservation does not match the amount from the hook'
            . PHP_EOL .
            "Card id: {$card->id}, reservation id: {$reservation->id}"
        );

    }

    public static function checkCardNumber(CardModel $card, string $card_number) {
        $qiwi = new QiwiService($card->oauth_token);
        $provider_info = $qiwi->getProviderId($card_number);
        return $provider_info['code']['value'] == 0 || !in_array($provider_info['message'], ['1960', '21012']);
    }

    public static function calcNewRateByPromoCode(float $rate, string $promo_code): ?float {
        $promo_code = static::getPromoCodeModel($promo_code);

        if (is_null($promo_code)) {
            return null;
        }

        $sale_in_percent = static::getSaleInPercent($promo_code);
        if ($sale_in_percent > 0) {
            $rate = round((1 - $sale_in_percent / 100) * $rate, 2);
        }

        return $rate;
    }

    public static function getPromoCodeModel(string $promo_code): ?AgentPromoCodeModel {
        return AgentPromoCodeModel::first(Where::equal('promo_code', $promo_code));
    }

    public static function getAgentByPromoCode(string $promo_code): ?AgentModel {
        $agent = null;

        $promo_code = static::getPromoCodeModel($promo_code);

        if ($promo_code === null) {
            return null;
        }

        try {
            $agent = AgentModel::get($promo_code->agent_id);
        } catch (ModelNotFoundException $e) {
            //
        }

        return $agent;
    }

    public static function getSaleInPercent(AgentPromoCodeModel $promo_code): float {
        return settings()->getBitcoinovnetNetProfit()
            *
            (settings()->bitcoinovnet_agent_max_percent / 100)
            *
            (1 - ($promo_code->percent / settings()->bitcoinovnet_agent_max_percent));
    }

    public static function getAgentProfitInPercent(AgentPromoCodeModel $promo_code): float {
        return settings()->getBitcoinovnetNetProfit()
            *
            (settings()->bitcoinovnet_agent_max_percent / 100)
            *
            ($promo_code->percent / settings()->bitcoinovnet_agent_max_percent);
    }

    public static function addProfitByReservation(
        AgentPromoCodeModel $promo_code,
        ReservedCardModel $reservation,
        float $amount,
        string $currency): ProfitModel {
        return Transaction::wrap(function () use ($promo_code, $reservation, $amount, $currency){
            $balance = BalanceModule::getBalanceOrCreate(
                $promo_code->user_id,
                $currency,
                BalanceModel::CATEGORY_BITCOINOVNET_AGENT
            );

            $profit = new ProfitModel();
            $profit->currency = $currency;
            $profit->amount = $amount;
            $profit->user_id = $promo_code->user_id;
            $profit->type = ProfitModel::TYPE_BITCOINOVNET_PROFIT;
            $profit->target_id = $reservation->id;
            $profit->wallet_id = $balance->id;
            $profit->created_at = date('Y-m-d H:i:s');
            $profit->rate = $reservation->current_rate;
            $profit->agent_percent_profit = $promo_code->percent;
            $profit->save();

            $promo_code->swap_count++;
            $promo_code->save();

            BalanceHistorySaver::make()
                ->setToRaw(UserBalanceHistoryModel::TYPE_BALANCE, $balance->id, $balance->user_id, $balance->currency)
                ->setCreatedAt($profit->created_at_timestamp)
                ->setToAmount($profit->amount)
                ->setOperation(UserBalanceHistoryModel::OPERATION_BITCOINOVNET_PROFIT)
                ->setObjectId($profit->id)
                ->save();

            $balance = BalanceModule::getBalanceOrCreate(
                $promo_code->user_id,
                $currency,
                BalanceModel::CATEGORY_BITCOINOVNET_AGENT
            );
            $balance->incrAmount($profit->amount);

            return $profit;
        });
    }

    public static function updateOrCreateMerchantPayment(
        CardModel $card,
        array $payment,
        ReservedCardModel $reservation = null,
        $blockchain_txid = null): MerchantPayments {
        return Transaction::wrap(function () use ($card, $payment, $reservation, $blockchain_txid) {
            $merchant_txid = $payment['txnId'];
            $merchant_payment = MerchantPayments::queryBuilder()
                ->forUpdate()
                ->where(Where::equal('merchant_txid', $merchant_txid))
                ->get();

            if (empty($merchant_payment)) {
                $merchant_payment = new MerchantPayments();
            } else {
                try {
                    $merchant_payment = MerchantPayments::rowsToSet([$merchant_payment])->first();
                } catch (ModelNotFoundException $e) {
                    $merchant_payment = new MerchantPayments();
                }
            }

            /** @var MerchantPayments $merchant_payment */

            $merchant_payment->merchant = MerchantPayments::MERCHANT_QIWI;
            $merchant_payment->blockchain_txid = $blockchain_txid;
            $merchant_payment->merchant_txid = $payment['txnId'];
            $merchant_payment->card_id = $card->id;
            $merchant_payment->reservation_id = $reservation ? $reservation->id : null;
            $merchant_payment->account = $payment['personId'];
            $merchant_payment->type = $payment['type'];
            $merchant_payment->status = $payment['status'];
            $merchant_payment->amount = $payment['sum']['amount'];
            $merchant_payment->total = $payment['total']['amount'];
            $merchant_payment->currency = CURRENCY_RUB;
            $merchant_payment->commission = $payment['commission']['amount'];
            $merchant_payment->comment = $payment['comment'];
            $merchant_payment->extra = json_encode([
                'total' => $payment['total'],
                'provider' => $payment['provider'],
                'comment' => $payment['comment'],
            ]);

            $merchant_payment->save();

            return $merchant_payment;
        });
    }

    public static function getCardLimits(CardModel $card, array $types) {
        $limits = (new QiwiService($card->oauth_token))->getCardLimits($card->wallet_number, $types);
        return $limits['limits']['RU'];
    }

    public static function getAvailableCard(float $need_amount, $manual_session): ?CardModel {
        $where = Where::and()
            ->set('active', Where::OperatorEq, 1)
            ->set('booked', Where::OperatorEq, 0)
            ->set('available_amount', Where::OperatorGreaterEq, $need_amount);

        if ($manual_session instanceof ManualSessionModel) {
            $where->set('merchant', Where::OperatorNotEq, CardModel::MERCHANT_QIWI);
        } else {
            $where->set(Where::equal('merchant', CardModel::MERCHANT_QIWI));
        }

        return CardModel::first($where);
    }

    public static function calcAvailableAmount(CardModel $card) {
        try {
            $types = [
                'REFILL',
                'TURNOVER'
            ];

            $limits = FastExchangeService::getCardLimits($card, $types);

            $date_till = (new \DateTime($limits[0]['interval']['dateTill']))->getTimestamp();
            $rest_turnover = 0;
            $rest_refill = 0;

            foreach ($limits as $limit) {
                $var = 'rest_' . strtolower($limit['type']);
                $$var = $limit['rest'];
            }

            if (($date_till + 3 * 86400) >= time()) {
                //если до конца расчетного периода осталось меньше чем 3 дня, лимит на вывод с карт можно не учитывать
                $rest_turnover = 9999999999;
            }

            $available_amount = $rest_turnover > $rest_refill ? $rest_refill : $rest_turnover;

            $card->available_amount = $available_amount;
            $card->save();
        } catch (\Exception $e) {
            $telegram = new SendService(SendService::CHAT_BITCOINOVNET);
            $telegram->sendMessageSafety(
                '#WARNING'
                . PHP_EOL .
                "failed to update valid card limit (card: {$card->id})"
            );
        }
        
        return $card;
    }

    public static function sendTelegramNotify(CardModel $card, string $message) {
        $chat = SendService::CHAT_BITCOINOVNET;
        if ($card->merchant !== CardModel::MERCHANT_QIWI) {
            $chat = SendService::CHAT_BITCOINOVNET_MANUAL_OPERATOR;
        }

        $telegram = new SendService($chat);
        $telegram->sendMessageSafety($message);
    }
}
