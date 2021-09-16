<?php


namespace Models;


use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Field\TextField;
use Db\Model\Model;

/**
 * @property int user_id
 * @property string platform
 * @property string account
 * @property string text
 * @property string exchange
 * @property float rate
 * @property float amount
 * @property string currency
 * @property string type
 */
class HedgingTransactionModel extends Model {
    protected static $table_name = 'bitcoinovnet_hedging_transactions';

    const EXCHANGE_BITMEX = 'bitmex',
        EXCHANGE_BINANCE = 'binance';

    const TYPE_SELL = 'sell',
        TYPE_BUY = 'buy';

    const ACCOUNT_SHORT = 'short',
        ACCOUNT_LONG = 'long';

    const PLATFORM_BITCOINOVNET = 'bitcoinovnet';

    protected static function fields(): array {
        return [
            'user_id' => IdField::init()->setNull(),
            'platform' => CharField::init(),
            'account' => CharField::init(),
            'text' => TextField::init()->setNull(),
            'exchange' => CharField::init(),
            'rate' => DoubleField::init(),
            'amount' => DoubleField::init(),
            'currency' => CharField::init(),
            'type' => CharField::init(),
        ];
    }

    public function isShort() {
        return $this->account === self::ACCOUNT_SHORT;
    }

    public function isLong() {
        return $this->account === self::ACCOUNT_LONG;
    }

    public function isBuy() {
        return $this->type === self::TYPE_BUY;
    }

    public function isSell() {
        return $this->type === self::TYPE_SELL;
    }
}
