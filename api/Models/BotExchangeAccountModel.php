<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\IdField;
use Db\Model\Model;

/**
 * @property int user_id
 * @property string exchange
 * @property string name
 * @property string api_key
 * @property string api_secret
 */
class BotExchangeAccountModel extends Model {
    protected static $table_name = 'bot_exchange_accounts';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'exchange' => CharField::init()->setLength(30),
            'name' => CharField::init()->setLength(125),
            'api_key' => CharField::init()->setLength(125),
            'api_secret' => CharField::init()->setLength(125),
        ];
    }

    public function toJson(): array {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
        ];
    }
}
