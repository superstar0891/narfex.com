<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Model;
use Modules\FiatWalletModule;

/**
 * @property string exchange
 * @property double amount
 * @property string currency
 * @property double rate
 * @property double real_rate
 * @property string status
 * @property int user_id
 * @property double exchange_rate
 * @property double fiat_amount
 * @property string fiat_currency
 * @property double close_rate
 * @property string buy_currency
 * @property double buy_rate
 * @property double fiat_rate
 */
class ExternalExchangePositionModel extends Model {

    const STATUS_PENDING = 'pending';
    const STATUS_CLOSED = 'closed';
    const STATUS_IN_QUEUE = 'in_queue';

    const EXCHANGE_BITMEX = 'bitmex';

    protected static $table_name = 'external_exchange_position';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'exchange' => CharField::init()->setLength(32),
            'amount' => DoubleField::init(),
            'currency' => CharField::init()->setLength(12),
            'rate' => DoubleField::init(), // Курс, по которому произошла конвертация в usd
            'status' => CharField::init()->setLength(32),
            'real_rate' => DoubleField::init()->setDefault(0), // Курс, по коротому была открыта позиция
            'user_id' => IdField::init(),
            'close_rate' => DoubleField::init()->setDefault(0), // Курс закрытия позиции (крипта-фиат)
            'buy_currency' => CharField::init()->setLength(12)->setNull(true),
            'buy_rate' => DoubleField::init()->setDefault(0),
            'exchange_rate' => DoubleField::init(), // Курс, по коротому произошел обмен (usd-крипта)
            'fiat_rate' => DoubleField::init(), // Курс, по коротому произошел обмен (фиат-крипта)
            'fiat_amount' => DoubleField::init(),
            'fiat_currency' => DoubleField::init(),
        ];
    }

    private function getOpenRate(): float {
        // Если удалось открыть по более выгодной цене, то берем ее
        if ($this->real_rate > $this->rate) {
            $rate = $this->real_rate;
        } else {
            $rate = $this->rate;
        }

        return $rate;
    }

    public function getHedgingProfit(): array {
        $rate = $this->getOpenRate();
        if ($this->status === self::STATUS_CLOSED) {
            $rate_diff = ($rate - $this->close_rate) / $rate;
        } else {
            $rate_diff = 0;
        }

        return [
            'percent' => round($rate_diff * 100, 3, PHP_ROUND_HALF_DOWN),
            'amount' => number_format($this->amount * $rate_diff, 10, '.', ''),
        ];
    }

    public function getExchangeProfit(): array {
        if ($this->status === self::STATUS_CLOSED) {
            $rate_diff = ($this->exchange_rate - $this->buy_rate) / $this->exchange_rate;
        } else {
            $rate_diff = 0;
        }

        return [
            'percent' => round($rate_diff * 100, 3, PHP_ROUND_HALF_DOWN),
            'amount' => number_format($this->amount * $rate_diff, 10, '.', ''),
        ];
    }

    public function isClosed() {
        return $this->status === self::STATUS_CLOSED;
    }
}
