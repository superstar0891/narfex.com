<?php

use Db\Model\Exception\ModelNotFoundException;
use Db\Transaction;
use Db\Where;
use Models\TransactionModel;
use Models\UserModel;
use Models\WalletModel;
use Phinx\Migration\AbstractMigration;

class MigrateWalletsToTransactions extends AbstractMigration
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
        $transactions = TransactionModel::select(
            Where::and()
                ->set(Where::equal('category', 'receive'))
                ->set('user_id', Where::OperatorIsNot, null)
        );

        $users_ids = $transactions->column('user_id');

        $wallets = WalletModel::select(
            Where::in('user_id', $users_ids)
        );

        $wallets_map = [];
        foreach ($wallets as $wallet) {
            /** @var WalletModel $wallet */
            $wallets_map[$wallet->address] = $wallet;
        }

        Transaction::wrap(function() use ($wallets_map, $transactions) {
            foreach ($transactions as $transaction) {
                /** @var TransactionModel $transaction */
                $wallet = $wallets_map[$transaction->user_wallet] ?? null;
                if (!$wallet) {
                    continue;
                }

                try {
                    $transaction->wallet_id = $wallet->id;
                    $transaction->save();
                } catch (ModelNotFoundException $e) {
                    continue;
                }
            }
        });

    }
}
