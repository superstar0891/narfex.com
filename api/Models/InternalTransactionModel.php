<?php


namespace Models;


use Core\Services\BalanceHistory\BalanceHistorySaver;
use Core\Services\BalanceHistory\SetFromAndToId;
use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Model;
use Db\Transaction;

/**
 * @property int user_id
 * @property string from_type
 * @property string from_category
 * @property int from_id
 * @property string to_type
 * @property string to_category
 * @property int to_id
 * @property float amount
 * @property string currency
 */
class InternalTransactionModel extends Model {
    use SetFromAndToId;

    const CATEGORY_FIAT = 0;
    const CATEGORY_EXCHANGE = 1;
    const CATEGORY_PARTNERS = 2;
    const CATEGORY_WALLET = 3;

    public static $categories = [
        self::CATEGORY_FIAT => 'fiat',
        self::CATEGORY_EXCHANGE => 'exchange',
        self::CATEGORY_PARTNERS => 'partners',
        self::CATEGORY_WALLET => 'wallet',
    ];

    protected $from_user_id_field = 'user_id';
    protected $to_user_id_field = 'user_id';
    protected $from_currency_field = 'currency';
    protected $to_currency_field = 'currency';
    protected static $table_name = 'internal_transactions';

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'from_type' => CharField::init(),
            'from_id' => IdField::init(),
            'from_category' => IntField::init(),
            'to_type' => CharField::init(),
            'to_id' => IdField::init(),
            'to_category' => IntField::init(),
            'amount' => DoubleField::init(),
            'currency' => CharField::init(),
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
                    ->setToRaw($this->to_type, $this->to_id, $this->user_id, $this->currency)
                    ->setCreatedAt($this->created_at_timestamp)
                    ->setToAmount($this->amount)
                    ->setOperation(UserBalanceHistoryModel::OPERATION_INTERNAL_TRANSACTION)
                    ->setObjectId($this->id)
                    ->save();
                return $this;
            });
        }
    }
}
