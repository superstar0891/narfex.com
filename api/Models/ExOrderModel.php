<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Model;

/**
 * @property int id
 * @property string action
 * @property string type
 * @property string primary_coin
 * @property string secondary_coin
 * @property int user_id
 * @property double amount
 * @property double filled
 * @property double price
 * @property string status
 * @property double fee
 * @property double avg_price
 * @property int avg_price_count
 */
class ExOrderModel extends Model {
    protected static $table_name = 'ex_orders';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'action' => CharField::init()->setLength(32),
            'type' => CharField::init()->setLength(32),
            'primary_coin' => CharField::init()->setLength(32),
            'secondary_coin' => CharField::init()->setLength(32),
            'user_id' => IdField::init(),
            'amount' => DoubleField::init(),
            'filled' => DoubleField::init()->setDefault(0),
            'price' => DoubleField::init()->setDefault(0),
            'status' => CharField::init()->setLength(32),
            'fee' => DoubleField::init()->setDefault(0),
            'avg_price' => DoubleField::init()->setDefault(0),
            'avg_price_count' => IntField::init()->setDefault(0),
        ];
    }

    public function getAvgPrice(): float {
        return $this->avg_price_count > 0 ? $this->avg_price / $this->avg_price_count : 0;
    }

    public function getMarket(): string {
        return strtoupper($this->primary_coin . '/' . $this->secondary_coin);
    }

    public function toJson(): array {
        return [
            'id' => (int) $this->id,
            'action' => $this->action,
            'type' => $this->type,
            'primary_coin' => strtolower($this->primary_coin),
            'secondary_coin' => strtolower($this->secondary_coin),
            'amount' => (double) $this->amount,
            'filled' => (double) $this->filled,
            'price' => (double) $this->price,
            'status' => $this->status,
            'updated_at' => $this->updated_at_timestamp,
            'fee' => (double) $this->fee,
            'avg_price' => (double) $this->getAvgPrice(),
        ];
    }
}
