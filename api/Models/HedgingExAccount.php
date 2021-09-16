<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Model;

/**
 * @property string exchange
 * @property string description
 * @property string public_key
 * @property string private_key
 */
class HedgingExAccount extends Model {
    protected static $table_name = 'hedging_exchange_accounts';

    const EXCHANGE_BINANCE = 'binance',
        EXCHANGE_BITMEX = 'bitmex';

    protected static function fields(): array {
        return [
            'exchange' => CharField::init()->setLength(255),
            'description' => CharField::init()->setLength(255),
            'public_key' => CharField::init()->setLength(255),
            'private_key' => CharField::init()->setLength(255),
        ];
    }

    public function getPrivateKeyDecoded(): string {
        $hash = hex2bin($this->private_key);
        return decrypt($hash);
    }
}
