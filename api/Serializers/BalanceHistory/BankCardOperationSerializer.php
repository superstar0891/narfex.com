<?php

namespace Serializers\BalanceHistory;

use Db\Model\ModelSet;
use Models\BankCardModel;
use \Models\BankCardOperationModel;

class BankCardOperationSerializer {
    public static function serialize(BankCardOperationModel $operation, ModelSet $cards): array {
        $card = $cards->getItem($operation->card_id);
        /** @var BankCardModel $card */
        return [
            'id' => (int) $operation->id,
            'created_at' => (int) $operation->created_at_timestamp,
            'currency' => CURRENCY_RUB,
            'amount' => (double) $operation->amount,
            'fee' => (double) $operation->fee,
            'provider' => $card ? $card->getBankName() : '',
            'type' => 'refill',
            'status' => 'reject',
            'bank_code' => $card->bank,
        ];
    }
}
