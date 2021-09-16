<?php

namespace Core\Blockchain;

use Core\Services\Mail\MailAdapter;
use Core\Services\Mail\Templates;
use Core\Services\Telegram\SendService;
use DateTime;
use Db\Db;
use Db\Transaction;
use Db\Where;
use Models\AddressModel;
use Models\TransactionModel;
use Models\UserModel;
use Models\WalletModel;
use Modules\BitcoinovnetModule;
use Modules\FiatWalletModule;
use Modules\NotificationsModule;
use Modules\WalletModule;

class BlockchainNotify {

    private $currency;
    private $transaction_id;

    const CONFIRMATION = [
      'btc' => 2,
      'ltc' => 2,
      'eth' => 8,
    ];

    /* @var \Blockchain\Platforms\PlatformInterface $crypto_instance */
    private $crypto_instance;
    private $transaction = null;

    public function __construct($currency, $transaction_id = null) {
        $this->currency = $currency;
        $this->transaction_id = $transaction_id;

        $this->crypto_instance = Factory::getInstance($currency);

        if ($transaction_id) {
            $this->transaction = $this->getTransaction($transaction_id);
        }
    }

    public function process() {
        if (!$this->transaction) {
            throw new \Exception('Transaction can\'t be NULL');
        }

        switch ($this->currency) {
            case 'eth':
                $this->resolveEthereum();
                break;
            case 'btc':
            case 'ltc':
                $this->resolveBitcoin();
                break;
        }
    }

    public function blockChainNotify() {
        if (!in_array($this->currency, ['btc', 'ltc', 'eth'], true)) {
            throw new \Exception('Unsupported currency');
        }

        $transactions = TransactionModel::select(Where::and()
            ->set('currency', Where::OperatorEq, $this->currency)
            ->set('status', Where::OperatorEq, 'unconfirmed')
        );

        /* @var TransactionModel $t */
        foreach ($transactions as $t) {
            try {
                $this->transaction = $this->getTransaction($t->txid);
                $this->updateTransaction($t);
            } catch (\Exception $e) {

            }
        }
    }

    private function getTransaction($txid): array {
        $transaction = null;
        try {
            $transaction = $this->crypto_instance->getTransactionInfo($txid);
        } catch (\Exception $e) {}

        if (!$transaction || !isset($transaction['txid'])) {
            $this->crypto_instance = Factory::getBtcBitcoinovnetInstance();
            $transaction = $this->crypto_instance->getTransactionInfo($txid);
        }

        return $transaction;
    }

    private function resolveEthereum() {
        $trs_category = 'send';
        $trs_wallet = null;
        $user_id = null;
        $transaction = $this->transaction;
        $txid = $this->transaction_id;
        $currency = $this->currency;

        $address = AddressModel::select(Where::and()
            ->set('address', Where::OperatorEq, $this->transaction['to'])
            ->set('currency', Where::OperatorEq, $this->currency)
            ->set('commerce_address', Where::OperatorEq, 0)
        );

        if (!$address->isEmpty()) {
            $trs_category = 'receive';
            $trs_wallet = $this->transaction['to'];
        }

        if ($trs_wallet) {
            $address = $address->first();
            /* @var \Models\AddressModel $address */

            $user_id = $address->user_id;
        }

        [$t, $need_notify] = Transaction::wrap(function () use ($trs_category, $txid, $transaction, $user_id, $currency, $trs_wallet) {
            TransactionModel::lockTableWrite();
            $local_transaction = TransactionModel::queryBuilder()
                ->columns([])
                ->where(Where::and()
                    ->set('txid', Where::OperatorEq, $txid)
                    ->set('category', Where::OperatorEq, $trs_category))
                ->forUpdate(true)
                ->select();
            $local_transaction = TransactionModel::rowsToSet($local_transaction);
            $need_notify = false;
            if ($local_transaction->isEmpty()) {
                $wallet_address = array_get_val($transaction, 'from');
                $wallet = WalletModule::getWalletByAddress($trs_wallet, $currency);
                $t = new TransactionModel();
                $t->user_id = $user_id;
                $t->currency = $currency;
                $t->status = 'unconfirmed';
                $t->amount = $transaction['amount'];
                $t->wallet_to = $wallet_address;
                $t->created_at = date('Y-m-d H:i:s');
                $t->updated_at = date('Y-m-d H:i:s');
                $t->category = $trs_category;
                $t->txid = $txid;
                $t->platform = PLATFORM_FINDIRI;
                $t->user_wallet = $trs_wallet;
                $t->wallet_id = $wallet ? $wallet->id : null;

                $this->updateTransaction($t);
                Db::unlockTables();

                $need_notify = true;
                if ($need_notify) {
                    $this->notifyIncome($t);
                }
            } else {
                $t = $local_transaction->first();
            }

            return [$t, $need_notify];
        });

        if ($t->category === TransactionModel::RECEIVE_CATEGORY && $need_notify) {
            $this->notifyReceive($t);
        }
    }

    private function resolveBitcoin() {
        $trs_txid = $this->transaction['txid'];

        if (!$trs_txid) {
            return;
        }

        foreach ($this->transaction['details'] as $detail) {
            //bitcoinovnet create recive transaction
            if (
                isset($detail['label'])
                &&
                substr($detail['label'], 0, 19) === 'user_account_system'
                &&
                $detail['category'] === 'receive'
            ) {
                $trs_wallet = $detail['address'];
                $trs_amount = $detail['amount'];

                if (!$trs_wallet) {
                    continue;
                }

                $app_id = null;
                $trs_commerce = null;

                Transaction::wrap(function () use ($trs_txid, $trs_wallet, $trs_amount) {
                    $t = TransactionModel::queryBuilder()
                        ->columns([])
                        ->where(Where::and()
                            ->set('txid', Where::OperatorEq, $trs_txid)
                            ->set('category', Where::OperatorEq, 'receive')
                            ->set('platform', Where::OperatorEq, PLATFORM_BITCOINOVNET)
                            ->set('user_wallet', Where::OperatorEq, $trs_wallet))
                        ->forUpdate(true)
                        ->select();
                    $t = TransactionModel::rowsToSet($t);

                    if ($t->isEmpty()) {
                        $t = new TransactionModel();
                        $t->status = 'unconfirmed';
                        $t->currency = $this->currency;
                        $t->amount = $trs_amount;
                        $t->wallet_to = null;
                        $t->created_at = date('Y-m-d H:i:s');
                        $t->updated_at = date('Y-m-d H:i:s');
                        $t->category = 'receive';
                        $t->txid = $trs_txid;
                        $t->platform = PLATFORM_BITCOINOVNET;
                        $t->user_wallet = $trs_wallet;

                        $telegram = new SendService(SendService::CHAT_BITCOINOVNET);
                        try {
                            BitcoinovnetModule::updateBitcoinovnetBalance();
                            $telegram->sendMessageSafety('Bitcoinovnet refill, amount: ' . formatNum($trs_amount, 6));
                            BitcoinovnetModule::addShort($trs_amount);
                        } catch (\Exception $e) {
                            $telegram->sendMessageSafety('Can\'t open short order: ' . $e->getMessage());
                        }
                    } else {
                        $t = $t->first();
                        /* @var \Models\TransactionModel $t */
                    }

                    $this->updateTransaction($t);

                    return $t;
                });
            } elseif (
                isset($detail['label']) && substr($detail['label'], 0, 13) === 'user_account_'
                &&
                $detail['category'] === 'receive'
            ) {
                $trs_wallet = $detail['address'];
                $trs_amount = $detail['amount'];

                if (!$trs_wallet) {
                    continue;
                }

                $user_id = null;
                $app_id = null;
                $trs_commerce = null;

                $address = AddressModel::select(Where::and()
                    ->set('address', Where::OperatorEq, $trs_wallet)
                    ->set('currency', Where::OperatorEq, $this->currency)
                    ->set('commerce_address', Where::OperatorEq, 0)
                );

                if ($address->isEmpty()) {
                    continue;
                }

                $address = $address->first();
                /* @var \Models\AddressModel $address */

                $user_id = $address->user_id;

                [$t, $need_notify] = Transaction::wrap(function () use ($trs_txid, $trs_wallet, $user_id, $trs_amount) {
                    $t = TransactionModel::queryBuilder()
                        ->columns([])
                        ->where(Where::and()
                            ->set('txid', Where::OperatorEq, $trs_txid)
                            ->set('category', Where::OperatorEq, 'receive')
                            ->set('platform', Where::OperatorEq, PLATFORM_FINDIRI)
                            ->set('user_wallet', Where::OperatorEq, $trs_wallet))
                        ->forUpdate(true)
                        ->select();
                    $t = TransactionModel::rowsToSet($t);

                    $need_notify = false;
                    if ($t->isEmpty()) {
                        $address = null;
                        $wallet = WalletModule::getWalletByAddress($trs_wallet, $this->currency);

                        $t = new TransactionModel();
                        $t->user_id = $user_id;
                        $t->status = 'unconfirmed';
                        $t->currency = $this->currency;
                        $t->amount = $trs_amount;
                        $t->wallet_to = $address;
                        $t->created_at = date('Y-m-d H:i:s');
                        $t->updated_at = date('Y-m-d H:i:s');
                        $t->category = 'receive';
                        $t->txid = $trs_txid;
                        $t->platform = PLATFORM_FINDIRI;
                        $t->user_wallet = $trs_wallet;
                        $t->wallet_id = $wallet ? $wallet->id : null;

                        $need_notify = true;
                    } else {
                        $t = $t->first();
                        /* @var \Models\TransactionModel $t */
                    }

                    $this->updateTransaction($t);

                    if ($need_notify) {
                        $this->notifyIncome($t);
                    }

                    return [$t, $need_notify];
                });

                if ($t->category === TransactionModel::RECEIVE_CATEGORY && $need_notify) {
                    $this->notifyReceive($t);
                }
            }
        }
    }

    private function updateTransaction(TransactionModel $t) {
        $required_confirmations = self::CONFIRMATION[$t->currency];
        $trs_conf = (int) $this->transaction['confirmations'];
        $trs_status = $trs_conf >= $required_confirmations ? 'confirmed' : 'unconfirmed';

        if ($trs_conf < 0) {
            //$trs_status = 'failed';
            $trs_conf = 0;
        }

        if ($t->status === 'unconfirmed' && $trs_status === 'confirmed') {
            if ($t->category === 'receive' && $t->user_id > 0 && $t->user_wallet && $t->platform === PLATFORM_FINDIRI) {
                $this->incrementWalletAmount($t);
            }
        }

        $t->status = $trs_status;
        $t->confirmations = $trs_conf;
        $t->updated_at = date('Y-m-d H:i:s');
        $t->save();
    }

    private function incrementWalletAmount(TransactionModel $t) {
        $wallet = WalletModel::select(Where::and()
            ->set('user_id', Where::OperatorEq, $t->user_id)
            ->set('currency', Where::OperatorEq, $t->currency)
        );

        if (!$wallet->isEmpty()) {
            $wallet = $wallet->first();
            /* @var WalletModel $wallet */
            if (!$wallet->addAmount($t->amount)) {
                throw new \Exception();
            }
        }
    }

    private function notifyReceive(TransactionModel $t) {
        NotificationsModule::sendReceiveNotification($t);

        $user = UserModel::get($t->user_id);
        $telegram_service = new SendService();
        $telegram_service->sendMessage('#blockchain_receive' . PHP_EOL . sprintf('ID: %s, %s(%s), Date: %s, %s',
                $t->id,
                $user->login,
                $user->id,
                (new DateTime())->setTimestamp($t->created_at_timestamp)->format('d.m.Y H:i:s'),
                formatNum($t->amount, 8) . ' ' . strtoupper($t->currency)
            ));
    }

    private function notifyIncome(TransactionModel $t) {
        if (!settings()->wallet_withdraw_email_notif) {
            return;
        }

        $message = <<<HTML
User ID: {user_id}<br />
Amount: {amount} {currency}<br/>
Txid: {txid}<br/>
Date: {date}
HTML;
        $message = str_replace([
            '{user_id}',
            '{amount}',
            '{currency}',
            '{txid}',
            '{date}',
        ], [
            $t->user_id,
            number_format($t->amount, 6, '.', ''),
            strtoupper($t->currency),
            $t->txid,
            date('d/m/Y H:i'),
        ], $message);

        foreach (explode(',', settings()->wallet_refill_emails) as $to) {
            MailAdapter::send(trim($to), 'New refill', Templates::SIMPLE, [
                'caption' => $message,
            ]);
        }
    }
}
