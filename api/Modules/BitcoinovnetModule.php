<?php

namespace Modules;

use Core\Blockchain\Factory;
use Core\Exceptions\FloodControl\FloodControlExpiredAtException;
use Core\Queue\BitcoinovnetHedgingTransactionsJob;
use Core\Queue\QueueManager;
use Core\Services\BalanceHistory\BalanceHistoryGetter;
use Core\Services\BalanceHistory\BalanceHistorySaver;
use Core\Services\Merchant\FastExchangeService;
use Core\Services\Promo\CodeGeneratorService;
use Core\Services\Telegram\SendService;
use Db\Model\Exception\ModelNotFoundException;
use Db\Model\ModelSet;
use Db\Pagination\PaginatorById;
use Db\Transaction;
use Db\Where;
use Models\AgentModel;
use Models\AgentPromoCodeModel;
use Models\BalanceModel;
use Models\BitcoinovnetUserCardModel;
use Models\BitcoinovnetWithdrawal;
use Models\HedgingTransactionModel;
use Models\ProfitModel;
use Models\ReservedCardModel;
use Models\UserBalanceHistoryModel;
use Models\UserModel;
use Models\UserPermissionModel;

class BitcoinovnetModule {
    public static function addShort($amount_btc, UserModel $user = null) {
        self::addHedgingQueues($amount_btc,
            HedgingTransactionModel::TYPE_SELL,
            'short',
            'long',
            $user
        );
    }

    public static function addLong($amount_btc, UserModel $user = null) {
        self::addHedgingQueues($amount_btc,
            HedgingTransactionModel::TYPE_BUY,
            'long',
            'short',
            $user
        );
    }

    public static function addHedgingQueues(float $amount_btc, string $side, ?string $open_account = null, ?string $close_account = null, UserModel $user = null) {
        $success_message = [];
        $error_message = [];

        if (!is_null($open_account)) {
            try {
                $rate = FiatWalletModule::getRate(CURRENCY_BTC, CURRENCY_USD, false, true);
                $usd_amount = ceil($amount_btc * $rate);
                self::createTransaction($usd_amount, $side, $open_account, $user);
                $success_message[] = "Bitcoinovnet add hedging queue, side {$side}, {$open_account} account, amount: " . formatNum($amount_btc, 6);
            } catch (\Exception $e) {
                $error_message[] = "Can't open {$open_account} order: {$e->getMessage()}";
            }
        }

        if (!is_null($close_account)) {
            try {
                $conf = KERNEL_CONFIG['bitcoinovnet_hedging']['bitmex'][$close_account];
                $exchange = HedgingExchangeModule::getExchange('bitmex', $conf['key'], $conf['secret']);
                $position_price = array_get_val($exchange->getPosition($exchange->mapSymbol(\Symbols::BTCUSD)), 'position_price', null);
                if (is_null($position_price)) {
                    throw new \Exception('Failed to get position_price');
                }
                $usd_amount = ceil($position_price * $amount_btc);
                self::createTransaction($usd_amount, $side, $close_account, $user);
                $success_message[] = "Bitcoinovnet add hedging queue, side {$side}, {$close_account} account, amount: " . formatNum($amount_btc, 6);
            } catch (\Exception $e) {
                $error_message[] = "Can't open {$close_account} order: {$e->getMessage()}";
            }
        }


        $telegram = new SendService(SendService::CHAT_BITCOINOVNET);
        if (!empty($success_message)) {
            $telegram->sendMessageSafety(implode(PHP_EOL, $success_message));
        }
        if (!empty($error_message)) {
            $telegram->sendMessageSafety('#ERROR' . PHP_EOL . implode(PHP_EOL, $error_message));
        }
    }

    public static function createTransaction(float $amount, string $type, string $account, UserModel $user = null) {
        Transaction::wrap(function () use ($amount, $type, $user, $account) {
            $transaction = new HedgingTransactionModel();
            $transaction->exchange = HedgingTransactionModel::EXCHANGE_BITMEX;
            $transaction->amount = $amount;
            $transaction->currency = CURRENCY_USD;
            $transaction->rate = 0;
            $transaction->type = $type;
            $transaction->user_id = is_null($user) ? null : $user->id;
            $transaction->platform = HedgingTransactionModel::PLATFORM_BITCOINOVNET;
            $transaction->account = $account;
            $transaction->save();

            QueueManager::addQueue(new BitcoinovnetHedgingTransactionsJob($transaction->id, $account));
        });
    }

    public static function getOrCreateAgent(UserModel $user) {
        if (!$user->fromBitcoinovnet()) {
            throw new \LogicException('Incorrect user');
        }

        $agent = AgentModel::first(Where::equal('user_id', $user->id));

        if (is_null($agent)) {
            $agent = new AgentModel();
            $agent->user_id = $user->id;
            $user->addPermission(UserPermissionModel::AGENT_BITCOINOVNET)->save();
            $agent->platform = PLATFORM_BITCOINOVNET;
            $agent->save();
        }

        return $agent;
    }

    public static function getOrCreatePromoCodes(AgentModel $agent): ModelSet {
        $promo_codes = AgentPromoCodeModel::select(Where::equal('agent_id', $agent->id));

        return Transaction::wrap(function () use ($promo_codes, $agent) {
            $current_percent = 5;
            $last_percent = settings()->bitcoinovnet_agent_max_percent;

            $codes = [];

            do {
                try {
                    $promo_code = $promo_codes->filter(function (AgentPromoCodeModel $promo_code) use ($current_percent) {
                        return $current_percent == $promo_code->percent;
                    })->first();
                } catch (ModelNotFoundException $e) {
                    $promo_code = new AgentPromoCodeModel();
                    $promo_code->percent = $current_percent;
                    $promo_code->user_id = $agent->user_id;
                    $promo_code->agent_id = $agent->id;
                    $promo_code->swap_count = 0;
                    $promo_code->promo_code = '';
                    $promo_code->save();

                    $promo_code->promo_code = strtoupper(bin2hex(openssl_random_pseudo_bytes(4)))
                        .
                        CodeGeneratorService::encode($promo_code->id);
                    $promo_code->save();
                }

                $codes[] = $promo_code;
                $current_percent += 5;
            } while ($current_percent < $last_percent);

            return new ModelSet($codes);
        });
    }

    public static function history(UserModel $user, int $start_from, int $count): PaginatorById {
        if (!$user->fromBitcoinovnet()) {
            throw new \LogicException('Incorrect user');
        }
        $history_getter = new BalanceHistoryGetter;
        $history_getter->setUsersIds([$user->id]);
        $history_getter->setOperations([
            UserBalanceHistoryModel::OPERATION_BITCOINOVNET_PROFIT,
            UserBalanceHistoryModel::OPERATION_BITCOINOVNET_WITHDRAWAL,
        ]);
        $paginator = $history_getter->paginateById((int) $start_from, (int) $count);

        return $paginator;
    }

    public static function getProfitsCount(UserModel $user) {
        if (!$user->fromBitcoinovnet()) {
            throw new \LogicException('Incorrect user');
        }

        $profits_count = ProfitModel::queryBuilder()
            ->columns(['COUNT(id)' => 'cnt'], true)
            ->where(Where::and()
                ->set(Where::equal('user_id', $user->id))
                ->set(Where::equal('type', ProfitModel::TYPE_BITCOINOVNET_PROFIT))
            )
            ->get();
        return (int) $profits_count['cnt'];
    }

    public static function createWithdrawalRequest(
        BalanceModel $balance,
        float $amount,
        string $card_number): BitcoinovnetWithdrawal {
        return Transaction::wrap(function () use ($balance, $amount, $card_number) {
            $withdrawal = new BitcoinovnetWithdrawal();
            $withdrawal->status = BitcoinovnetWithdrawal::STATUS_PENDING;
            $withdrawal->user_id = $balance->user_id;
            $withdrawal->amount = $amount;
            $withdrawal->currency = $balance->currency;
            $withdrawal->card_number = $card_number;
            $withdrawal->save();

            BalanceHistorySaver::make()
                ->setFromRaw(UserBalanceHistoryModel::TYPE_BALANCE, $balance->id, $balance->user_id, $balance->currency)
                ->setCreatedAt($withdrawal->created_at_timestamp)
                ->setFromAmount($withdrawal->amount)
                ->setOperation(UserBalanceHistoryModel::OPERATION_BITCOINOVNET_WITHDRAWAL)
                ->setObjectId($withdrawal->id)
                ->setCreatedAt(time())
                ->save();

            return $withdrawal;
        });
    }


    public static function withdrawal(BalanceModel $balance, float $amount): bool {
        return Transaction::wrap(function () use ($balance, $amount) {
            $balance = BalanceModel::queryBuilder()
                ->forUpdate()
                ->where(Where::equal('id', $balance->id))
                ->get();

            /** @var BalanceModel $balance */
            $balance = BalanceModel::rowsToSet([$balance])->first();

            if ($balance->amount < $amount) {
                return false;
            }

            $res = $balance->decrAmount($amount);

            if (!$res) {
                return false;
            }

            return true;
        });
    }

    public static function updateBitcoinovnetBalance() {
        $settings = settings();

        try {
            $inst = Factory::getBtcBitcoinovnetInstance();
            $info = $inst->getWalletInfo();

            $settings->bitcoinovnet_btc_balance = $info['balance'];
            $settings->save();
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * @param string $key
     * @param array $rules
     * @throws \Exception
     */
    public static function floodControl(string $key, array $rules) {
        $expired_at = floodControlWithExpiredAt($key, $rules);
        if (is_int($expired_at)) {
            $e = new FloodControlExpiredAtException();
            $e->setExpiredAt($expired_at);
            throw $e;
        }
    }

    public static function getOrCreateUserAfterReservation(ReservedCardModel $reservation): ?array {
        if ($reservation->email === null) {
            return null;
        }

        return Transaction::wrap(function () use ($reservation) {
            if (!is_null($reservation->user_id)) {
                $user = UserModel::get($reservation->user_id);
            } else {
                $sign_up_module = new SignUpModule($reservation->email);
                $sign_up_module->setPlatform(PLATFORM_BITCOINOVNET)
                    ->validate();

                try {
                    $user = $sign_up_module->getUser();
                } catch (\Exception $e) {
                    $user = $sign_up_module->signUp()->getUser();
                }

                $reservation->user_id = $user->id;
                $reservation->save();
            }

            $card = static::getOrCreateUserCard($user, $reservation);

            return [$user, $card];
        });
    }

    public static function getOrCreateUserCard(UserModel $user, ReservedCardModel $reservation): BitcoinovnetUserCardModel {
        if ($reservation->email !== $user->email && $user->id !== $reservation->user_id) {
            throw new \LogicException();
        }

        $card = BitcoinovnetUserCardModel::first(Where::and()
            ->set(Where::equal('user_id', $user->id))
            ->set(Where::equal('card_number', $reservation->card_number))
        );

        if ($card !== null) {
            return $card;
        }

        $card = new BitcoinovnetUserCardModel();
        $card->user_id = $user->id;
        $card->card_number = $reservation->card_number;
        $card->card_owner = $reservation->card_owner_name;
        $card->validated = $reservation->validate;
        $card->photo_name = $reservation->photo_name;
        $card->save();

        return $card;
    }

    public static function userReservations(UserModel $user, int $start_from = null, int $count = null): PaginatorById {
        return ReservedCardModel::queryBuilder()
            ->where(Where::equal('user_id', $user->id))
            ->paginateById($start_from, $count);
    }

    public static function userCards(UserModel $user): ModelSet {
        return BitcoinovnetUserCardModel::select(Where::and()
            ->set(Where::equal('user_id', $user->id))
            ->set(Where::equal('validated', 1))
        );
    }

    public static function ratesXml(): \SimpleXMLElement {
        $merchants = [
            'CARDRUB', 'QWRUB', 'YAMRUB', 'SBERRUB', 'ACRUB', 'TBRUB', 'RFBRUB', 'TCSBRUB',
            'RUSSTRUB', 'GPBRUB', 'PSBRUB', 'AVBRUB', 'RNKBRUB', 'KUKRUB', 'MIR',
        ];

        $rate = FiatWalletModule::swapRate(CURRENCY_RUB, CURRENCY_BTC);
        $available_balance = (float) FastExchangeService::getAvailableBalanceInBtc();
        $transaction_min_amount = settings()->bitcoinovnet_min_transaction_amount;
        $transaction_max_amount = settings()->bitcoinovnet_max_transaction_amount;

        $xml = new \SimpleXMLElement('<rates/>');

        foreach ($merchants as $merchant) {
            $item = $xml->addChild('item');
            $item->addChild('from', $merchant);
            $item->addChild('to', 'BTC');
            $item->addChild('in', $rate);
            $item->addChild('out', 1);
            $item->addChild('amount', $available_balance);
            $item->addChild('minAmount', $transaction_min_amount . ' RUB');
            $item->addChild('maxAmount', $transaction_max_amount . ' RUB');
            $param = 'cardverify';
            if ($merchant !== 'QWRUB') {
                $param .= ', otherin';
            }
            $item->addChild('param', $param);
        }

        return $xml;
    }
}