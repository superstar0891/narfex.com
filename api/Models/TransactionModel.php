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
 * @property int user_id
 * @property string txid
 * @property string platform
 * @property double amount
 * @property int confirmations
 * @property int wallet_id
 * @property string status
 * @property string currency
 * @property string category
 * @property string user_wallet
 * @property string wallet_to
 * @property string created_at
 * @property string updated_at
 */
class TransactionModel extends Model {
    const STATUS_UNCONFIRMED = 'unconfirmed',
        STATUS_CONFIRMED = 'confirmed',
        STATUS_CANCELED = 'canceled';

    protected static $table_name = 'transactions';

    protected static $fields = [];

    const RECEIVE_CATEGORY = 'receive',
        SEND_CATEGORY = 'send';

    protected static function fields(): array {
        return [
            'user_id' =>  IdField::init()->setNull(true),
            'txid' => CharField::init()->setLength(128)->setNull(true),
            'platform' => CharField::init()->setLength(100)->setDefault('findiri'),
            'amount' => DoubleField::init(),
            'wallet_id' => IdField::init()->setNull(),
            'confirmations' => IntField::init()->setDefault(0),
            'status' => CharField::init()->setLength(16),
            'currency' => CharField::init()->setLength(16),
            'category' => CharField::init()->setLength(16),
            'user_wallet' => CharField::init()->setLength(128)->setNull(true),
            'wallet_to' => CharField::init()->setLength(128)->setNull(true),
            'created_at' => DateTimeField::init(),
            'updated_at' => DateTimeField::init(),
        ];
    }

    public function isReceive() {
        return $this->category === self::RECEIVE_CATEGORY;
    }

    public function isCancelled() {
        return $this->status === self::STATUS_CANCELED;
    }

    public function save() {
        $exists_in_db = $this->existsInDatabase();
        parent::save();
        if (!$exists_in_db && $this->category === self::RECEIVE_CATEGORY && $this->user_id && $this->wallet_id) {
            BalanceHistorySaver::make()
                ->setObjectId($this->id)
                ->setToRaw(UserBalanceHistoryModel::TYPE_WALLET, $this->wallet_id, $this->user_id, $this->currency)
                ->setToAmount($this->amount)
                ->setOperation(UserBalanceHistoryModel::OPERATION_TRANSACTION)
                ->setCreatedAt($this->created_at_timestamp)
                ->save();
        }

    }
}
