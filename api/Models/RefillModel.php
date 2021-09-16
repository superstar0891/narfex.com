<?php


namespace Models;


use Core\Services\BalanceHistory\BalanceHistorySaver;
use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Model;
use Db\Transaction;

/**
 * @property int user_id
 * @property string currency
 * @property float amount
 * @property float fee
 * @property int to_id
 * @property string to_type
 * @property string provider
 * @property string bank_code
 * @property string external_id
 */
class RefillModel extends Model {

    const PROVIDER_XENDIT = 'xendit';
    const PROVIDER_CARDS = 'cards';

    protected static $table_name = 'refills';

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'currency' => CharField::init(),
            'amount' => DoubleField::init(),
            'fee' => DoubleField::init()->setNull(),
            'to_id' => IdField::init(),
            'to_type' => CharField::init(),
            'provider' => CharField::init(),
            'bank_code' => CharField::init()->setNull(),
            'external_id' => CharField::init()->setNull(),
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
                    ->setToRaw($this->to_type, $this->to_id, $this->user_id, $this->currency)
                    ->setCreatedAt($this->created_at_timestamp)
                    ->setToAmount($this->amount)
                    ->setOperation(UserBalanceHistoryModel::OPERATION_REFILL)
                    ->setObjectId($this->id)
                    ->save();
                return $this;
            });
        }
    }
}
