<?php

namespace Modules;

use Blockchain\Exception\CallListenerMethodException;
use Core\App;
use Core\Blockchain\Factory;
use Db\Exception\DbAdapterException;
use Db\Exception\InvalidSelectQueryException;
use Db\Exception\InvalidWhereOperatorException;
use Db\Model\Exception\TableNameUndefinedException;
use Db\Model\Model;
use Db\Transaction;
use Db\Where;
use Engine\Request;
use Exceptions\WithdrawalRequests\EnoughMoneyTransferException;
use Exceptions\WithdrawalRequests\InvalidWithdrawalStatusException;
use Exceptions\WithdrawalRequests\WalletNotFoundException;
use Models\AddressModel;
use Models\Logs\BlockhainWithdrawalRequestLog;
use Models\TransactionModel;
use Models\UserBalanceHistoryModel;
use Models\WalletModel;
use Models\WithdrawalRequest;

class BlockchainWithdrawalModule {

    /**
     * @param WithdrawalRequest $withdrawal
     * @throws EnoughMoneyTransferException
     * @throws InvalidWithdrawalStatusException
     * @throws CallListenerMethodException
     */
    public static function processNow(WithdrawalRequest $withdrawal) {
        if ($withdrawal->status !== WithdrawalRequest::STATUS_PENDING) {
            throw new InvalidWithdrawalStatusException('Withdrawal request status must be pending');
        }

        static::process($withdrawal);
    }

    /**
     * @param WithdrawalRequest $withdrawal
     * @throws EnoughMoneyTransferException
     * @throws InvalidWithdrawalStatusException
     * @throws CallListenerMethodException
     */
    public static function processInJob(WithdrawalRequest $withdrawal) {
        if (
            $withdrawal->status !== WithdrawalRequest::STATUS_BOOST
            &&
            ($withdrawal->status !== WithdrawalRequest::STATUS_PENDING || $withdrawal->exec_at > time())
        ) {
            throw new InvalidWithdrawalStatusException('Incorrect withdrawal request status or exec_at date');
        }

        static::process($withdrawal);
    }

    /**
     * @param WithdrawalRequest $withdrawal
     * @throws EnoughMoneyTransferException
     * @throws CallListenerMethodException
     */
    private static function process(WithdrawalRequest $withdrawal) {
        $password = null;

        if (App::isProduction()) {
            $instance = Factory::getInstance($withdrawal->currency);
        } else {
            $instance = null;
        }

        if ($withdrawal->currency === CURRENCY_ETH) {
            $address = AddressModel::select(Where::and()
                ->set(Where::equal('address', KERNEL_CONFIG['eth_root_address']))
                ->set(Where::equal('currency', CURRENCY_ETH))
            );
            if ($address->isEmpty()) {
                throw new \Exception('Root address not found');
            }
            /* @var AddressModel $address */
            $address = $address->first();
            if (App::isProduction()) {
                $balance = $instance->getWalletInfo($address->address)['balance'];
            } else {
                $balance = 100000000;
            }
            $options = json_decode($address->options, true);
            $password = $options['passphrase'];
        } else {
            if (App::isProduction()) {
                $balance = $instance->getWalletInfo()['balance'];
            } else {
                $balance = 100000000;
            }
        }

        if ($balance < $withdrawal->amount) {
            throw new EnoughMoneyTransferException('not enough money to transfer founds');
        }

        Transaction::wrap(function () use ($withdrawal, $password, $instance) {
            $withdrawal_model = WithdrawalRequest::queryBuilder()
                ->columns([])
                ->where(Where::and()
                    ->set('id', Where::OperatorEq, $withdrawal->id)
                    ->set('status', Where::OperatorEq, WithdrawalRequest::STATUS_DONE))
                ->forUpdate(true)
                ->select();
            $withdrawal_model = WithdrawalRequest::rowsToSet($withdrawal_model);

            if ($withdrawal_model->isEmpty()) {
                /** @var WithdrawalRequest $withdrawal_model */
                $withdrawal->status = WithdrawalRequest::STATUS_DONE;
                $withdrawal->save();

                $from_address = $withdrawal->user_address;
                if ($withdrawal->currency === CURRENCY_ETH) {
                    $from_address = KERNEL_CONFIG['eth_root_address'];
                }

                if (App::isProduction()) {
                    $txid = $instance->sendToAddress(
                        $from_address,
                        $withdrawal->to_address,
                        $withdrawal->amount,
                        $password
                    );
                } else {
                    $txid = bin2hex(openssl_random_pseudo_bytes(64));
                }

                static::addLog($withdrawal, BlockhainWithdrawalRequestLog::PROCESS_WITHDRAWAL_REQUEST);

                $transaction = WalletModule::createTransaction('send', $withdrawal->currency, $withdrawal->amount, [
                    'status' => TransactionModel::STATUS_UNCONFIRMED,
                    'txid' => $txid,
                    'from' => $withdrawal->user_address,
                    'to' => $withdrawal->to_address,
                    'user_id' => $withdrawal->user_id,
                    'created_at' => $withdrawal->created_at_timestamp,
                    'wallet_id' => $withdrawal->wallet_id,
                ]);
                
                /** @var UserBalanceHistoryModel $history */
                $history = UserBalanceHistoryModel::first(
                    Where::and()
                        ->set(Where::equal('object_id', $withdrawal->id))
                        ->set(Where::equal('operation', UserBalanceHistoryModel::OPERATION_WITHDRAWAL_REQUEST))
                );

                $history->object_id = $transaction->id;
                $history->operation = UserBalanceHistoryModel::OPERATION_TRANSACTION;
                $history->save();
            }
        });
    }

    /**
     * @param WithdrawalRequest $withdrawal
     * @return WithdrawalRequest
     * @throws WalletNotFoundException
     * @throws DbAdapterException
     * @throws InvalidSelectQueryException
     * @throws InvalidWhereOperatorException
     * @throws TableNameUndefinedException
     */
    public static function reject(WithdrawalRequest $withdrawal): WithdrawalRequest {
        /** @var WalletModel $wallet */
        $wallet = WalletModel::first(Where::and()
            ->set('user_id', Where::OperatorEq, $withdrawal->user_id)
            ->set('address', Where::OperatorEq, $withdrawal->user_address)
        );

        if (!$wallet) {
            throw new WalletNotFoundException('Wallet was not found');
        }

        $limits = KERNEL_CONFIG['wallet']['withdraw_limits'][$withdrawal->currency];
        $fee = $limits['fee'];

        return Transaction::wrap(function () use ($wallet, $withdrawal, $fee) {
            $wallet->addAmount($withdrawal->amount + $fee);

            $transaction = WalletModule::createTransaction('send', $withdrawal->currency, $withdrawal->amount, [
                'status' => TransactionModel::STATUS_CANCELED,
                'from' => $withdrawal->user_address,
                'to' => $withdrawal->to_address,
                'user_id' => $withdrawal->user_id,
                'created_at' => $withdrawal->created_at_timestamp,
                'wallet_id' => $wallet->id
            ]);

            NotificationsModule::sendTransactionNotification($transaction);

            static::addLog($withdrawal, BlockhainWithdrawalRequestLog::REJECT_WITHDRAWAL_REQUEST);

            $withdrawal->reject();
            $withdrawal->save();

            /** @var UserBalanceHistoryModel $history */
            $history = UserBalanceHistoryModel::first(
                Where::and()
                    ->set(Where::equal('object_id', $withdrawal->id))
                    ->set(Where::equal('operation', UserBalanceHistoryModel::OPERATION_WITHDRAWAL_REQUEST))
            );

            $history->object_id = $transaction->id;
            $history->operation = UserBalanceHistoryModel::OPERATION_TRANSACTION;
            $history->save();

            return $withdrawal;
        });
    }

    public static function pause(WithdrawalRequest $withdrawal): WithdrawalRequest {
        $withdrawal = Transaction::wrap(function () use ($withdrawal) {
            $withdrawal->pause();
            $withdrawal->save();
            static::addLog($withdrawal, BlockhainWithdrawalRequestLog::PAUSED_WITHDRAWAL_REQUEST);
            return $withdrawal;
        });

        return $withdrawal;
    }

    public static function start(WithdrawalRequest $withdrawal): WithdrawalRequest {
        $withdrawal = Transaction::wrap(function () use ($withdrawal) {
            $withdrawal->start();
            $withdrawal->save();
            static::addLog($withdrawal, BlockhainWithdrawalRequestLog::START_WITHDRAWAL_REQUEST);
            return $withdrawal;
        });

        return $withdrawal;
    }

    private static function addLog(WithdrawalRequest $withdrawal, string $action) {
        $user = Request::getUser();
        $isAdmin = $user ? $user->isAdmin() : false;
        UserLogModule::addLog(
            $action,
            new BlockhainWithdrawalRequestLog([
                'withdrawal_id' => $withdrawal->id,
                'currency' => $withdrawal->currency,
                'amount' => $withdrawal->amount,
            ]),
            $isAdmin,
            $user
        );
    }
}
