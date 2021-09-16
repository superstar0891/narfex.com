<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Model;

/**
 * @property string name
 * @property string primary_currency
 * @property float primary_amount
 * @property string secondary_currency
 * @property float buy_rate
 * @property float buy_fee
 * @property float fiat_to_usd
 * @property float|null short_rate
 * @property float|null short_fee
 * @property float|null close_short_fee
 * @property float|null close_short_rate
 * @property string|null close_short_currency
 * @property float|null close_long_fee
 * @property float|null close_long_rate
 * @property string|null close_long_currency
 * @property int|null account_id
 * @property int|null close_at_timestamp
 */
class StackModel extends Model {
    protected static $table_name = 'hedging_stack';

    protected static function fields(): array {
        return [
            'account_id' => IdField::init()->setNull(),
            'name' => CharField::init()->setNull(),
            'primary_currency' => CharField::init()->setLength(32),
            'primary_amount' => DoubleField::init(),
            'secondary_currency' => CharField::init()->setLength(32),
            'buy_rate' => DoubleField::init(),
            'fiat_to_usd' => DoubleField::init(),
            'short_rate' => DoubleField::init()->setNull(),
            'buy_fee' => DoubleField::init(),
            'short_fee' => DoubleField::init()->setNull(),
            'close_short_fee' => DoubleField::init()->setNull(),
            'close_short_rate' => DoubleField::init()->setNull(),
            'close_short_currency' => CharField::init()->setLength(32)->setNull(),
            'close_long_fee' => DoubleField::init()->setNull(),
            'close_long_rate' => DoubleField::init()->setNull(),
            'close_long_currency' => CharField::init()->setLength(32)->setNull(),
            'close_at_timestamp' => IntField::init()->setLength(10)->setNull()->setUnsigned(),
        ];
    }

    public function getShortAmount(): float {
        $amount = ($this->buy_rate / $this->fiat_to_usd) * $this->primary_amount;
        return ceil($amount);
    }
}
