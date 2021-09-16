<?php

use Core\Services\BalanceHistory\BalanceHistorySaver;
use Db\Model\Exception\ModelNotFoundException;
use Db\Transaction;
use Db\Where;
use Models\BalanceHistoryModel;
use Models\BalanceModel;
use Models\InternalTransactionModel;
use Models\RefillModel;
use Models\SwapModel;
use Models\TransactionModel;
use Models\TransferModel;
use Models\UserBalanceHistoryModel;
use Models\WithdrawalModel;
use Modules\BalanceModule;
use Modules\WalletModule;
use Phinx\Migration\AbstractMigration;

class MigrateHistoriesData extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        function execSwaps() {
            $swaps = BalanceHistoryModel::select(
                Where::and()
                    ->set(Where::in('type', ['send', 'receive']))
                    ->set('from_balance_category', Where::OperatorNotIN, ['partners', 'exchange', 'invoice'])
                    ->set('to_balance_category', Where::OperatorNotIN, ['partners', 'exchange', 'invoice'])
            );

            foreach ($swaps as $item) {
                /** @var BalanceHistoryModel $item */
                $swap = new SwapModel();
                $extra = json_decode($item->extra);
                $swap->rate = $extra->price;
                $swap->created_at_timestamp = $item->created_at_timestamp;
                $swap->status = UserBalanceHistoryModel::STATUS_COMPLETED;
                if ($item->from_balance_category === 'fiat') {
                    $balance = BalanceModule::getBalanceOrCreate($item->user_id, $extra->from_currency, BalanceModel::CATEGORY_FIAT);
                    $wallet = WalletModule::getWallet($item->user_id, $extra->to_currency);
                    if (!$wallet) {
                        continue;
                    }
                    $swap->setFrom($balance);
                    $swap->setTo($wallet);
                    $swap->from_amount = $extra->fiat_amount;
                    $swap->to_amount = $extra->crypto_amount;
                } else {
                    $balance = BalanceModule::getBalanceOrCreate($item->user_id, $extra->to_currency, BalanceModel::CATEGORY_FIAT);
                    $wallet = WalletModule::getWallet($item->user_id, $extra->from_currency);
                    if (!$wallet) {
                        continue;
                    }

                    $swap->setTo($balance);
                    $swap->setFrom($wallet);
                    $swap->from_amount = $extra->crypto_amount;
                    $swap->to_amount = $extra->fiat_amount;
                }

                $swap->created_at_timestamp = $item->created_at_timestamp;
                $swap->save();
            }
        }

        function execRefills() {
            $refills = BalanceHistoryModel::select(
                Where::and()
                    ->set(Where::equal('type', 'refill'))
            );

            foreach ($refills as $item) {
                $extra = json_decode($item->extra);
                $refill = new RefillModel();
                $refill->currency = strtolower($extra->currency);
                $refill->bank_code = $extra->bank_code ?? null;
                $refill->amount = $item->amount;
                $refill->user_id = $item->user_id;
                $refill->to_type = UserBalanceHistoryModel::TYPE_BALANCE;
                $refill->to_id = $item->to_balance_id;
                $refill->provider = 'xendit';
                $refill->external_id = $extra->payment_id;
                $refill->fee = $extra->fee;
                $refill->created_at_timestamp = $item->created_at_timestamp;
                $refill->save();
            }
        }

        function execWithdrawals() {
            $withdrawals = BalanceHistoryModel::select(
                Where::and()
                    ->set(Where::equal('type', 'withdrawal'))
            );

            foreach ($withdrawals as $item) {
                $withdrawal = new WithdrawalModel();
                $extra = json_decode($item->extra);
                $withdrawal->fee = $extra->fee ?? null;
                $withdrawal->provider = 'xendit';
                $withdrawal->amount = $item->amount;
                $withdrawal->status = array_keys(UserBalanceHistoryModel::STATUSES_MAP, $item->status)[0];
                $withdrawal->user_id = $item->user_id;
                $withdrawal->bank_code = $extra->bank_code;
                $withdrawal->account_number = $extra->account_number;
                $withdrawal->account_holder_name = $extra->account_holder_name;
                $withdrawal->currency = strtolower($extra->currency);
                $withdrawal->admin_id = $extra->admin_id ?? null;
                $withdrawal->reject_message = $extra->fail_reason ?? null;
                $withdrawal->external_id = $extra->external_id ?? null;
                $withdrawal->from_id = $item->from_balance_id;
                $withdrawal->from_type = UserBalanceHistoryModel::TYPE_BALANCE;
                $withdrawal->created_at_timestamp = $item->created_at_timestamp;
                $withdrawal->save();
            }
        };

        function execTransactions() {
            $transactions = TransactionModel::select();
            foreach ($transactions as $transaction) {
                /** @var TransactionModel $transaction */
                if (!$transaction->user_id) {
                    continue;
                }
                $saver = BalanceHistorySaver::make()
                    ->setObjectId($transaction->id)
                    ->setCreatedAt(strtotime($transaction->created_at))
                    ->setOperation(UserBalanceHistoryModel::OPERATION_TRANSACTION)
                    ->save();
                if ($transaction->category === TransactionModel::SEND_CATEGORY) {
                    $saver
                        ->setFrom(WalletModule::getWallet($transaction->user_id, $transaction->currency))
                        ->setFromAmount($transaction->amount);
                } else {
                    $saver
                        ->setTo(WalletModule::getWallet($transaction->user_id, $transaction->currency))
                        ->setToAmount($transaction->amount);
                }

                $saver->save();
            }
        }

        function execTransfers() {
            $transfers = TransferModel::select();
            foreach ($transfers as $transfer) {
                /** @var TransferModel $transfer */
                BalanceHistorySaver::make()
                    ->setFrom(WalletModule::getWallet($transfer->from_user_id, $transfer->currency))
                    ->setTo(WalletModule::getWallet($transfer->to_user_id, $transfer->currency))
                    ->setOperation(UserBalanceHistoryModel::OPERATION_TRANSFER)
                    ->setCreatedAt(strtotime($transfer->created_at))
                    ->setFromAmount($transfer->amount)
                    ->setToAmount($transfer->amount)
                    ->setObjectId($transfer->id)
                    ->save();
            }
        }

        function execInvoices() {
            $invoices = BalanceHistoryModel::select(
                Where::equal('from_balance_category', 'invoice')
            );

            foreach ($invoices as $invoice) {
                /** @var BalanceHistoryModel $invoice */
                $refill = new RefillModel();
                $refill->amount = $invoice->amount;
                $refill->currency = 'idr';
                $refill->user_id = $invoice->user_id;
                $refill->provider = 'xendit';
                $refill->created_at_timestamp = $invoice->created_at_timestamp;
                $refill->to_type = UserBalanceHistoryModel::TYPE_BALANCE;
                $refill->to_id = BalanceModule::getBalanceOrCreate($invoice->user_id, 'idr', BalanceModel::CATEGORY_FIAT)->id;
                $refill->save();
            }
        }

        function execInternalTransactions() {
            $transactions = BalanceHistoryModel::select(
                Where::and()
                    ->set(
                        Where::or()
                            ->set(Where::equal('from_balance_category', 'exchange'))
                            ->set(Where::equal('to_balance_category', 'exchange'))
                    )
            );

            foreach ($transactions as $transaction) {
                /** @var BalanceHistoryModel $transaction */
                $internal_transaction = new InternalTransactionModel();
                $internal_transaction->user_id = $transaction->user_id;
                $internal_transaction->amount = $transaction->amount;
                $internal_transaction->created_at_timestamp = $transaction->created_at_timestamp;
                if ($transaction->from_balance_category === 'exchange') {
                    /** @var BalanceModel $from_balance */
                    try {
                        $from_balance = BalanceModel::get($transaction->from_balance_id);
                    } catch (ModelNotFoundException $e) {
                        continue;
                    }
                    $to_wallet = WalletModule::getWallet($transaction->user_id, $from_balance->currency);
                    if (!$to_wallet) {
                        continue;
                    }
                    $internal_transaction->setFrom($from_balance);
                    $internal_transaction->setTo($to_wallet);
                    $internal_transaction->from_category = InternalTransactionModel::CATEGORY_EXCHANGE;
                    $internal_transaction->to_category = InternalTransactionModel::CATEGORY_WALLET;
                    $internal_transaction->currency = $from_balance->currency;
                } else {
                    /** @var BalanceModel $to_balance */
                    try {
                        $to_balance = BalanceModel::get($transaction->to_balance_id);
                    } catch (ModelNotFoundException $e) {
                        continue;
                    }
                    $from_wallet = WalletModule::getWallet($transaction->user_id, $to_balance->currency);
                    if (!$from_wallet) {
                        continue;
                    }
                    $internal_transaction->setFrom($from_wallet);
                    $internal_transaction->setTo($to_balance);
                    $internal_transaction->from_category = InternalTransactionModel::CATEGORY_WALLET;
                    $internal_transaction->to_category = InternalTransactionModel::CATEGORY_EXCHANGE;
                    $internal_transaction->currency = $to_balance->currency;
                }

                $internal_transaction->save();
            }
        }

        function execPartnersTransactions() {
            $transactions = BalanceHistoryModel::select(
                Where::and()
                    ->set(Where::equal('from_balance_category', 'partners'))
                    ->set(Where::in('user_id', [161, 3368, 1983]))
            );

            foreach ($transactions as $transaction) {
                /** @var BalanceHistoryModel $transaction */

                /** @var BalanceModel $partner_balance */
                try {
                    $partner_balance = BalanceModel::get($transaction->from_balance_id);
                } catch (ModelNotFoundException $e) {
                    continue;
                }
                $wallet = WalletModule::getWallet($transaction->user_id, $partner_balance->currency);
                if (!$wallet) {
                    continue;
                }
                $internal_transaction = new InternalTransactionModel();
                $internal_transaction->user_id = $transaction->user_id;
                $internal_transaction->from_category = InternalTransactionModel::CATEGORY_PARTNERS;
                $internal_transaction->to_category = InternalTransactionModel::CATEGORY_WALLET;
                $internal_transaction->setFrom($partner_balance);
                $internal_transaction->setTo($wallet);
                $internal_transaction->amount = $transaction->amount;
                $internal_transaction->save();
            }
        }

        Transaction::wrap(function(){
            echo 'Integrating withdrawals...' . PHP_EOL;
            execWithdrawals();
            echo 'Withdrawals are integrated.' . PHP_EOL;
            echo 'Integrating refills...' . PHP_EOL;
            execRefills();
            echo 'Refills are integrated.' . PHP_EOL;
            echo 'Integrating invoices...' . PHP_EOL;
            execInvoices();
            echo 'Invoices are integrated.' . PHP_EOL;
            echo 'Integrating swaps...' . PHP_EOL;
            execSwaps();
            echo 'Swaps are integrated.' . PHP_EOL;
            echo 'Integrating transactions...' . PHP_EOL;
            execTransactions();
            echo 'Transactions are integrated.' . PHP_EOL;
            echo 'Integrating transfers...' . PHP_EOL;
            execTransfers();
            echo 'Transfers are integrated.' . PHP_EOL;
            echo 'Integrating internal transactions...' . PHP_EOL;
            execInternalTransactions();
            echo 'Internal transactions are integrated.' . PHP_EOL;
            echo 'Integrating transactions from partners balances...' . PHP_EOL;
            execPartnersTransactions();
            echo 'Transactions from partners balances are integrated.' . PHP_EOL;
            echo 'Done.' . PHP_EOL;
        });
    }
}
