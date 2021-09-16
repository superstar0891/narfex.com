<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Model;

/**
 * @property int user_id
 * @property double amount
 * @property string currency
 * @property string payment_type
 * @property string status
 */
class FiatPaymentModel extends Model {
    protected static $table_name = 'fiat_payments';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'amount' => DoubleField::init(),
            'currency' => CharField::init()->setLength(32),
            'payment_type' => CharField::init()->setLength(32),
            'status' => CharField::init()->setLength(32),
        ];
    }
}
