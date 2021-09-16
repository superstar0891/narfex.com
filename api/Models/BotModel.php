<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Field\TextField;
use Db\Model\Field\UpdatedAtField;
use Db\Model\Model;
use Modules\WalletModule;

/**
 * @property int user_id
 * @property string type
 * @property string symbol
 * @property string name
 * @property string exchange
 * @property double trade_amount
 * @property double max_trade_amount
 * @property string indicators
 * @property int leverage
 * @property string time_frame
 * @property string position
 * @property double position_amount
 * @property double roe
 * @property string status
 * @property double take_profit
 * @property int start_date
 * @property double balance
 * @property double liquidation_price
 */
class BotModel extends Model {
    protected static $table_name = 'bots';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'type' => CharField::init()->setLength(30)->setDefault('default'),
            'symbol' => CharField::init()->setLength(30)->setDefault(''),
            'name' => CharField::init()->setLength(125)->setNull(true),
            'balance' => DoubleField::init()->setDefault(0),
            'exchange' => TextField::init()->setNull(true),
            'trade_amount' => DoubleField::init()->setDefault(0),
            'max_trade_amount' => DoubleField::init()->setDefault(0),
            'indicators' => TextField::init()->setDefault(''),
            'leverage' => IntField::init()->setDefault(1),
            'time_frame' => CharField::init()->setDefault('15m'),
            'position' => CharField::init()->setDefault('none'),
            'position_amount' => DoubleField::init()->setDefault(0),
            'roe' => DoubleField::init()->setDefault(0),
            'status' => CharField::init()->setLength(30)->setDefault('deactivated'),
            'start_date' => UpdatedAtField::init()->setNull(true),
            'take_profit' => DoubleField::init()->setDefault(0),
            'liquidation_price' => DoubleField::init()->setDefault(0),
        ];
    }

    public function toJson(): array {
        $exchange = json_decode($this->exchange, true) ?: [];
        if ($this->symbol) {
            list($asset) = explode('/', $this->symbol);
        } else {
            $asset = 'btc';
        }

        $indicators = json_decode($this->indicators, true);
        if ($indicators) {
            foreach ($indicators as $k => $v) {
                $indicators[$k]['params'] =  json_decode($v['params'], true);
            }
        } else {
            $indicators = [];
        }

        return [
            'id' => (int) $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'exchange' => empty($exchange) ? '' : $exchange['name'],
            'symbol' => $this->symbol,
            'trade_amount' => (double) $this->trade_amount,
            'max_trade_amount' => (double) $this->max_trade_amount,
            'leverage' => (double) $this->leverage,
            'time_frame' => $this->time_frame,
            'position' => $this->position,
            'position_amount' => (double) $this->position_amount,
            'roe' => (double) $this->roe,
            'status' => $this->status,
            'take_profit' => (double) $this->take_profit,
            'start_date' => (int) $this->start_date,
            'balance' => (double) $this->balance,
            'balance_usd' => (double) WalletModule::getUsdPrice($asset) * $this->balance,
            'liquidation_price' => (double) $this->liquidation_price,
            'indicators' => $indicators,
        ];
    }
}
