<?php

namespace Serializers;

use Models\BotExchangeAccountModel;

class BotsSerializer {
    public static function exchangeListItem(BotExchangeAccountModel $exchange) {
        return [
            'id' => (int) $exchange->id,
            'name' => $exchange->name,
        ];
    }
}
