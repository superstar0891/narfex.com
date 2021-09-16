<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Model;
use Phinx\Db\Adapter\MysqlAdapter;

/**
 * @property int stack_id
 * @property string type
 * @property float amount
 * @property string currency
 * @property float|null sale_rate
 * @property float|null fiat_to_usd
 * @property float|null sale_currency
 * @property float|null long_rate
 * @property float|null long_fee
 * @property string|null swap_rate
 * @property string|null swap_fee
 * @property int|null account_id
 */
class StackHistoryModel extends Model {
    protected static $table_name = 'hedging_stack_history';

    const TYPE_SALE = 'sale',
        TYPE_WITHDRAWAL = 'withdrawal';

    protected static function fields(): array {
        return [
            'stack_id' => IdField::init(),
            'account_id' => IdField::init()->setNull(),
            'type' => CharField::init()->setLength(32),
            'currency' => CharField::init()->setLength(32),
            'amount' => DoubleField::init(),
            'sale_rate' => DoubleField::init()->setNull(),
            'fiat_to_usd' => DoubleField::init()->setNull(),
            'sale_currency' => CharField::init()->setLength(32)->setNull(),
            'long_rate' => DoubleField::init()->setNull(),
            'long_fee' => DoubleField::init()->setNull(),
            'swap_rate' => CharField::init()->setNull()->setLength(MysqlAdapter::TEXT_REGULAR),
            'swap_fee' => CharField::init()->setNull()->setLength(MysqlAdapter::TEXT_REGULAR),
        ];
    }

    public function getLongAmount(): float {
        $amount = ($this->sale_rate / $this->fiat_to_usd) * $this->amount;
        return ceil($amount);
    }
}
