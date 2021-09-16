<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Model;

/**
 * @property int bot_id
 * @property double amount
 * @property string side
 * @property double price
 * @property double enter_price
 * @property string type
 */
class BotTradeModel extends Model {
    protected static $table_name = 'bot_trades';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'bot_id' => IdField::init(),
            'amount' => DoubleField::init(),
            'side' => CharField::init()->setLength(16),
            'price' => DoubleField::init(),
            'enter_price' => DoubleField::init()->setDefault(0),
            'type' => CharField::init(),
        ];
    }

    public function toJson(): array {
        if ($this->enter_price > 0) {
            if ($this->side === 'sell') {
                $percent = ($this->enter_price - $this->price) / $this->enter_price;
            } else {
                $percent = ($this->price - $this->enter_price) / $this->enter_price;
            }

            $percent *= 100;
            $profit = ($percent * $this->amount);
        } else {
            $percent = 0;
            $profit = 0;
        }

        return [
            'id' => (int) $this->id,
            'amount' => (double) $this->amount,
            'side' => $this->side,
            'price' => (double) $this->price,
            'type' => $this->type,
            'created_at' => (int) $this->created_at_timestamp,
            'percent' => (double) round($percent, 2),
            'profit' => (double) round($profit, 2),
        ];
    }
}
