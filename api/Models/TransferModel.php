<?php

namespace Models;

use Core\Services\BalanceHistory\BalanceHistorySaver;
use Db\Model\Field\CharField;
use Db\Model\Field\DateTimeField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Model;
use Db\Transaction;

/**
 * @property int from_user_id
 * @property string to_user_id
 * @property double from_wallet_id
 * @property int to_wallet_id
 * @property string currency
 * @property double amount
 * @property string created_at
 */
class TransferModel extends Model {

    protected static $table_name = 'transfers';

    protected static $fields = [];
    private $second_user = null;

    protected static function fields(): array {
        return [
            'from_user_id' => IdField::init(),
            'to_user_id' => CharField::init()->setLength(128),
            'from_wallet_id' => DoubleField::init(),
            'to_wallet_id' => IntField::init(),
            'currency' => CharField::init()->setLength(16),
            'amount' => DoubleField::init(),
            'created_at' => DateTimeField::init(),
        ];
    }

    public function save() {
        if ($this->existsInDatabase()) {
            parent::save();
            return $this;
        } else {
            return Transaction::wrap(function(){
                parent::save();
                BalanceHistorySaver::make()
                    ->setFromRaw(UserBalanceHistoryModel::TYPE_WALLET, $this->from_wallet_id, $this->from_user_id, $this->currency)
                    ->setToRaw(UserBalanceHistoryModel::TYPE_WALLET, $this->to_wallet_id, $this->to_user_id, $this->currency)
                    ->setCreatedAt($this->created_at_timestamp)
                    ->setOperation(UserBalanceHistoryModel::OPERATION_TRANSFER)
                    ->setObjectId($this->id)
                    ->save();
                return $this;
            });
        }
    }

    public function withUser(UserModel $user) {
        $this->second_user = $user;
        return $this;
    }

    /**
     * @return UserModel
     */
    public function getSecondUser() {
        return $this->second_user;
    }

}
