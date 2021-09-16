<?php


namespace Serializers\BalanceHistory;


use Core\Exceptions\BalanceHistory\BalanceHistoryException;
use Db\Model\ModelSet;
use Models\TransferModel;
use Models\UserModel;

class TransferSerializer {
    public static function serialize(TransferModel $transfer, UserModel $user, ModelSet $users): array {
        $type = $user->id === $transfer->from_user_id ? 'send' : 'receive';
        /** @var UserModel $another_user */
        $another_user = $type === 'send' ? $users->getItem($transfer->to_user_id) : $users->getItem($transfer->from_user_id);
        return [
            'address' => $another_user->login,
            'amount' => (float) $transfer->amount,
            'created_at' => (int) $transfer->created_at_timestamp,
            'currency' => $transfer->currency,
            'id' => (int) $transfer->id,
            'status' => 'done',
            'type' => "transfer_{$type}",
        ];
    }

    public static function serializeWithUser(TransferModel $transfer, UserModel $user) {
        if (!$transfer->getSecondUser()) {
            throw new BalanceHistoryException("There's no user in the transfer");
        }

        $type = $user->id === $transfer->from_user_id ? 'send' : 'receive';
        return [
            'address' => $transfer->getSecondUser()->login,
            'amount' => (float) $transfer->amount,
            'created_at' => (int) $transfer->created_at_timestamp,
            'currency' => $transfer->currency,
            'id' => (int) $transfer->id,
            'status' => 'done',
            'type' => "transfer_{$type}",
        ];
    }
}
