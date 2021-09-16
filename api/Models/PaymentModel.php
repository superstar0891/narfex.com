<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DateTimeField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Model;

/**
 * @property int user_id
 * @property int wallet_id
 * @property string wallet_address
 * @property string status
 * @property double amount
 * @property string created_at
 */
class PaymentModel extends Model {
    protected static $table_name = 'payments';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'wallet_id' => IdField::init(),
            'wallet_address' => CharField::init()->setLength(256)->setNull(true),
            'status' => CharField::init()->setLength(32),
            'amount' => DoubleField::init(),
            'created_at' => DateTimeField::init(),
        ];
    }
}
