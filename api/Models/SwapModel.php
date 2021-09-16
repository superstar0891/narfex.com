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
use Modules\FiatWalletModule;
use Modules\WalletModule;

/**
 * @property int user_id
 * @property string from_type
 * @property int from_id
 * @property string to_type
 * @property int to_id
 * @property float from_amount
 * @property float to_amount
 * @property string from_currency
 * @property string to_currency
 * @property float fee
 * @property float rate
 * @property int status
 */
class SwapModel extends Model {
    use SetFromAndToId;
    protected $from_user_id_field = 'user_id';
    protected $to_user_id_field = 'user_id';
    protected $from_currency_field = 'from_currency';
    protected $to_currency_field = 'to_currency';

    protected static $table_name = 'swaps';

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'from_type' => CharField::init(),
            'from_id' => IdField::init(),
            'to_type' => CharField::init(),
            'to_id' => IdField::init(),
            'from_amount' => DoubleField::init(),
            'to_amount' => DoubleField::init(),
            'from_currency' => CharField::init(),
            'to_currency' => CharField::init(),
            'fee' => DoubleField::init()->setNull()->setDefault(null),
            'rate' => DoubleField::init(),
            'status' => IntField::init()->setUnsigned(),
        ];
    }

    public function toUSD() {
        if ($this->from_type === UserBalanceHistoryModel::TYPE_WALLET) {
            $rate = FiatWalletModule::getRate($this->from_currency, CURRENCY_USD, false);

            if (in_array($this->from_currency, array_keys(blockchain_currencies()), true)) {
                return $this->from_amount * $rate;
            } else {
                return $this->from_amount / $rate;
            }
        } else {
            $rate = FiatWalletModule::getRate($this->to_currency, CURRENCY_USD, false);

            if (in_array($this->to_currency, array_keys(blockchain_currencies()), true)) {
                return $this->to_amount * $rate;
            } else {
                return $this->to_amount / $rate;
            }
        }
    }

    public function save() {
        if ($this->existsInDatabase()) {
            parent::save();
            return $this;
        } else {
            return Transaction::wrap(function(){
                parent::save();
                BalanceHistorySaver::make()
                    ->setFromRaw($this->from_type, $this->from_id, $this->user_id, $this->from_currency)
                    ->setToRaw($this->to_type, $this->to_id, $this->user_id, $this->to_currency)
                    ->setCreatedAt($this->created_at_timestamp)
                    ->setFromAmount($this->from_amount)
                    ->setToAmount($this->to_amount)
                    ->setOperation(UserBalanceHistoryModel::OPERATION_SWAP)
                    ->setObjectId($this->id)
                    ->save();
                return $this;
            });
        }
    }
}
