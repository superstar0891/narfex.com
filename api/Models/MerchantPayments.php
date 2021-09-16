<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Field\TextField;
use Db\Model\Model;

/**
 * @property string merchant
 * @property string blockchain_txid
 * @property string merchant_txid
 * @property int card_id
 * @property int reservation_id
 * @property int user_id
 * @property string account
 * @property string status
 * @property string type
 * @property string comment
 * @property float amount
 * @property float total
 * @property string currency
 * @property float commission
 * @property string extra
 */
class MerchantPayments extends Model {
    const MERCHANT_QIWI = 'qiwi';

    protected static $table_name = 'merchant_payments';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'blockchain_txid' => CharField::init()->setNull(),
            'merchant_txid' => CharField::init(),
            'card_id' => IdField::init(),
            'reservation_id' => IdField::init()->setNull(),
            'user_id' => IdField::init()->setNull(),
            'merchant' => CharField::init()->setLength(32),
            'account' => CharField::init()->setNull(),
            'type' => CharField::init()->setLength(32),
            'status' => CharField::init()->setLength(32),
            'comment' => TextField::init()->setNull(),
            'amount' => DoubleField::init(),
            'total' => DoubleField::init()->setNull(),
            'currency' => CharField::init()->setLength(32),
            'commission' => DoubleField::init()->setNull(),
            'extra' => TextField::init()->setNull(),
        ];
    }
}
