<?php


namespace Modules;


use Db\Model\ModelSet;
use Db\Where;
use Models\BankCardOperationModel;
use Models\BitcoinovnetWithdrawal;
use Models\InternalTransactionModel;
use Models\ProfitModel;
use Models\RefillModel;
use Models\SwapModel;
use Models\TransactionModel;
use Models\TransferModel;
use Models\UserBalanceHistoryModel;
use Models\UserModel;
use Models\WithdrawalModel;
use Models\WithdrawalRequest;

class BalanceHistoryModule {
    /**
     * @param string $operation
     * @param ModelSet $items
     * @param string $class
     * @return null
     * @throws \Db\Exception\InvalidWhereOperatorException
     */
    public static function getItemsByModelSet(string $operation, ModelSet $items, string $class): ?ModelSet {
        $ids = $items->filter(function(UserBalanceHistoryModel $item) use ($operation) {
            return $item->operation === $operation;
        })->column('object_id');

        if (!empty($ids)) {
            return $class::select(
                Where::in('id', $ids)
            );
        }

        return null;
    }

    public static function getObjectsByModelSet(ModelSet $items): array {
        $transactions = BalanceHistoryModule::getItemsByModelSet(UserBalanceHistoryModel::OPERATION_TRANSACTION, $items, TransactionModel::class);
        $withdrawal_requests = BalanceHistoryModule::getItemsByModelSet(UserBalanceHistoryModel::OPERATION_WITHDRAWAL_REQUEST, $items, WithdrawalRequest::class);
        $transfers = BalanceHistoryModule::getItemsByModelSet(UserBalanceHistoryModel::OPERATION_TRANSFER, $items, TransferModel::class);
        $swaps = BalanceHistoryModule::getItemsByModelSet(UserBalanceHistoryModel::OPERATION_SWAP, $items, SwapModel::class);
        $refills = BalanceHistoryModule::getItemsByModelSet(UserBalanceHistoryModel::OPERATION_REFILL, $items, RefillModel::class);
        $withdrawals = BalanceHistoryModule::getItemsByModelSet(UserBalanceHistoryModel::OPERATION_WITHDRAWAL, $items, WithdrawalModel::class);
        $bank_card_operation = BalanceHistoryModule::getItemsByModelSet(UserBalanceHistoryModel::OPERATION_BANK_CARD_REFILL_REJECT, $items, BankCardOperationModel::class);
        $profits = BalanceHistoryModule::getItemsByModelSet(UserBalanceHistoryModel::OPERATION_SAVING_ACCRUAL, $items, ProfitModel::class);
        $internal_transactions = BalanceHistoryModule::getItemsByModelSet(UserBalanceHistoryModel::OPERATION_INTERNAL_TRANSACTION, $items, InternalTransactionModel::class);
        $promo_rewards = BalanceHistoryModule::getItemsByModelSet(UserBalanceHistoryModel::OPERATION_PROMO_REWARD, $items, ProfitModel::class);
        $bitcoinovnet_profits = BalanceHistoryModule::getItemsByModelSet(UserBalanceHistoryModel::OPERATION_BITCOINOVNET_PROFIT, $items, ProfitModel::class);
        $bitcoinovnet_withdrawals = BalanceHistoryModule::getItemsByModelSet(UserBalanceHistoryModel::OPERATION_BITCOINOVNET_WITHDRAWAL, $items, BitcoinovnetWithdrawal::class);

        $transfers_users = null;

        if ($transfers) {
            $user_ids = array_unique(
                array_merge(
                    $transfers->column('from_user_id'),
                    $transfers->column('to_user_id')
                )
            );
            $transfers_users = UserModel::select(Where::in('id', $user_ids), false);
        }

        $promo_rewards_users = new ModelSet();
        if (!is_null($promo_rewards)) {
            $promo_rewards_users = UserModel::select(Where::in('id', $promo_rewards->column('target_id')));
        }

        return compact(
            'transfers', 'transactions', 'transfers_users',
            'swaps', 'refills', 'withdrawals',
            'withdrawal_requests', 'bank_card_operation', 'profits',
            'internal_transactions', 'promo_rewards', 'promo_rewards_users',
            'bitcoinovnet_profits', 'bitcoinovnet_withdrawals'
        );
    }
}
