<?php


namespace Api\History;


use Core\Response\JsonResponse;
use Core\Services\BalanceHistory\BalanceHistoryGetter;
use Db\Where;
use Engine\Request;
use Models\BalanceModel;
use Models\UserBalanceHistoryModel;
use Models\WalletModel;
use Serializers\BalanceHistory\HistorySerializer;
use Serializers\PagingSerializer;

class History {
    public static function get($request) {
        /**
         * @var int $start_from
         * @var int $count
         * @var int $balance_id
         * @var int $wallet_id
         * @var string $operations
         */
        extract($request['params']);

        $user = Request::getUser();
        $history_getter = new BalanceHistoryGetter;
        $history_getter->setUsersIds([$user->id]);

        if ($balance_id && $wallet_id) {
            JsonResponse::apiError();
        }

        if ($wallet_id) {
            /** @var WalletModel $wallet */
            $wallet = WalletModel::first(
                Where::and()
                    ->set(Where::equal('user_id', $user->id))
                    ->set(Where::equal('id', $wallet_id))
            );

            if (!$wallet) {
                JsonResponse::errorMessage('wallet_not_found');
            }

            $history_getter->setFromOrTo($wallet);
        }
        if ($balance_id) {
            /** @var BalanceModel $balance */
            $balance = BalanceModel::first(
                Where::and()
                    ->set(Where::equal('user_id', $user->id))
                    ->set(Where::equal('id', $balance_id))
            );

            if (!$balance) {
                JsonResponse::errorMessage('balance_not_found');
            }

            $history_getter->setFromOrTo($balance);
        }

        if ($operations) {
            $operations = array_intersect(UserBalanceHistoryModel::OPERATIONS_MAP, explode(',', $operations));
            if (isset($operations[UserBalanceHistoryModel::OPERATION_TRANSACTION])) {
                $operations[UserBalanceHistoryModel::OPERATION_WITHDRAWAL_REQUEST] = 1;
            }
            /** @var array $operations */
            $operations = array_unique(array_keys($operations));

            // don't show internal transactions to users now
            $history_getter->setOperations($operations);
        }

        $transactions = $history_getter->paginateById((int) $start_from, (int) $count);
        $serialized_transactions = HistorySerializer::serializeItems($transactions->getItems(), $user);

        JsonResponse::ok(PagingSerializer::detail($transactions->getNext(), $serialized_transactions));
    }
}
