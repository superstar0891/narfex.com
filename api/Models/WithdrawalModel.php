<?php


namespace Models;


use Core\Services\BalanceHistory\BalanceHistorySaver;
use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Model;
use Db\Transaction;

/**
 * @property int user_id
 * @property float amount
 * @property string currency
 * @property int from_id
 * @property string from_type
 * @property int admin_id
 * @property int status
 * @property float fee
 * @property string provider
 * @property string bank_code
 * @property string reject_message
 * @property string external_id
 * @property string account_number
 * @property string account_holder_name
 * @property string email_to
 * @property int approved_at_timestamp
 */
class WithdrawalModel extends Model {
    const PROVIDER_XENDIT = 'xendit';

    protected static $table_name = 'withdrawals';

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'currency' => CharField::init(),
            'amount' => DoubleField::init(),
            'fee' => DoubleField::init()->setNull(),
            'from_id' => IdField::init(),
            'from_type' => CharField::init(),
            'provider' => CharField::init(),
            'status' => IntField::init(),
            'bank_code' => CharField::init()->setNull(),
            'external_id' => CharField::init()->setNull(),
            'admin_id' => IdField::init()->setNull(),
            'reject_message' => CharField::init()->setNull(),
            'account_number' => CharField::init()->setNull(),
            'account_holder_name' => CharField::init()->setNull(),
            'approved_at_timestamp' => IntField::init()->setNull(),
            'email_to' => CharField::init()->setNull(),
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
                    ->setFromRaw($this->from_type, $this->from_id, $this->user_id, $this->currency)
                    ->setCreatedAt($this->created_at_timestamp)
                    ->setFromAmount($this->amount)
                    ->setOperation(UserBalanceHistoryModel::OPERATION_WITHDRAWAL)
                    ->setObjectId($this->id)
                    ->save();
                return $this;
            });
        }
    }

    public function isRejectedByAdmin() {
        return !is_null($this->admin_id);
    }
}
