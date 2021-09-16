<?php


namespace Serializers\BalanceHistory;


use Db\Model\ModelSet;
use Models\BankCardModel;
use Models\ProfitModel;
use Models\UserBalanceHistoryModel;
use Models\UserModel;
use Modules\BalanceHistoryModule;

class HistorySerializer {
    public static function serializeItems(ModelSet $items, UserModel $user) {
        $res = [];
        $objects_sets = BalanceHistoryModule::getObjectsByModelSet($items);
        extract($objects_sets);
        /**
         * @var ModelSet $swaps
         * @var ModelSet $refills
         * @var ModelSet $withdrawals
         * @var ModelSet $transactions
         * @var ModelSet $transfers
         * @var ModelSet $withdrawal_requests
         * @var ModelSet $transfers_users
         * @var ModelSet $bank_card_operation
         * @var ModelSet $profits
         * @var ModelSet $internal_transactions
         * @var ModelSet $promo_rewards
         * @var ModelSet $promo_rewards_users
         * @var ModelSet $bitcoinovnet_profits
         * @var ModelSet $bitcoinovnet_withdrawals
         */

        foreach ($items as $item) {
            /** @var UserBalanceHistoryModel $item */
            switch ($item->operation) {
                case UserBalanceHistoryModel::OPERATION_SWAP:
                    $res[] = SwapSerializer::serialize($swaps->getItem($item->object_id));
                    break;
                case UserBalanceHistoryModel::OPERATION_WITHDRAWAL:
                    $res[] = WithdrawalSerializer::serialize($withdrawals->getItem($item->object_id));
                    break;
                case UserBalanceHistoryModel::OPERATION_REFILL:
                    $res[] = RefillSerializer::serialize($refills->getItem($item->object_id));
                    break;
                case UserBalanceHistoryModel::OPERATION_TRANSFER:
                    $res[] = TransferSerializer::serialize($transfers->getItem($item->object_id), $user, $transfers_users);
                    break;
                case UserBalanceHistoryModel::OPERATION_TRANSACTION:
                    $res[] = TransactionSerializer::serialize($transactions->getItem($item->object_id));
                    break;
                case UserBalanceHistoryModel::OPERATION_WITHDRAWAL_REQUEST:
                    $res[] = TransactionSerializer::withdrawalRequestListItem($withdrawal_requests->getItem($item->object_id));
                    break;
                case UserBalanceHistoryModel::OPERATION_BANK_CARD_REFILL_REJECT:
                    $res[] = BankCardOperationSerializer::serialize($bank_card_operation->getItem($item->object_id), self::cards());
                    break;
                case UserBalanceHistoryModel::OPERATION_SAVING_ACCRUAL:
                    $res[] = ProfitSerializer::listItem($profits->getItem($item->object_id));
                    break;
                case UserBalanceHistoryModel::OPERATION_INTERNAL_TRANSACTION:
                    $res[] = InternalTransactionSerializer::listItem($internal_transactions->getItem($item->object_id));
                    break;
                case UserBalanceHistoryModel::OPERATION_PROMO_REWARD:
                    $profit = $promo_rewards->getItem($item->object_id);
                    /** @var ProfitModel|null $profit */
                    $res[] = ProfitSerializer::promoListItem($profit, $promo_rewards_users);
                    break;
                case UserBalanceHistoryModel::OPERATION_BITCOINOVNET_PROFIT:
                    $res[] = ProfitSerializer::bitcoinovnetProfit($bitcoinovnet_profits->getItem($item->object_id));
                    break;
                case UserBalanceHistoryModel::OPERATION_BITCOINOVNET_WITHDRAWAL:
                    $res[] = $bitcoinovnet_withdrawals->getItem($item->object_id)->toJson();
                    break;
            }
        }

        return $res;
    }

    private static function cards(): ModelSet {
        static $cards = null;

        if ($cards !== null) {
            return $cards;
        }

        return $cards = BankCardModel::select();
    }

}
