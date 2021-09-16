<?php


namespace Core\Services\BalanceHistory;


use Core\Exceptions\BalanceHistory\BalanceHistoryException;
use Models\BalanceModel;
use Models\UserBalanceHistoryModel;
use Models\WalletModel;

trait SetFromAndToId {
    public function setFromRaw(string $type, int $id, int $user_id, string $currency) {
        $this->from_type = $type;
        $this->from_id = $id;
        $this->{$this->from_user_id_field} = $user_id;
        $this->{$this->from_currency_field} = $currency;

        return $this;
    }

    public function setToRaw(string $type, int $id, int $user_id, string $currency) {
        $this->to_type = $type;
        $this->to_id = $id;
        $this->{$this->to_user_id_field} = $user_id;
        $this->{$this->to_currency_field} = $currency;

        return $this;
    }

    public function setFrom($from) {
        /** @var BalanceModel|WalletModel $from */
        $this->from_type = self::getTypeByObject($from);
        $this->from_id = $from->id;
        $this->{$this->from_user_id_field} = $from->user_id;
        $this->{$this->from_currency_field} = $from->currency;

        return $this;
    }

    public function setTo($to) {
        /** @var BalanceModel|WalletModel $to */
        $this->to_type = self::getTypeByObject($to);
        $this->to_id = $to->id;
        $this->{$this->to_currency_field} = $to->currency;
        $this->{$this->to_user_id_field} = $to->user_id;

        return $this;
    }

    public static function getTypeByObject($object): int {
        $class = get_class($object);
        if (!in_array($class, [WalletModel::class, BalanceModel::class])) {
            throw new BalanceHistoryException('Wrong class');
        }
        return $class === WalletModel::class ? UserBalanceHistoryModel::TYPE_WALLET : UserBalanceHistoryModel::TYPE_BALANCE;
    }
}
