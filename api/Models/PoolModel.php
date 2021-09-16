<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\CreatedAtField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Field\UpdatedAtField;
use Db\Model\Model;

/**
 * @property int user_id
 * @property int wallet_id
 * @property double amount
 * @property double amount_in_pool
 * @property string currency
 * @property string wallet_address
 * @property string exchange_name
 * @property int date_start
 * @property int date_update
 * @property int status
 * @property int deposit_id
 */
class PoolModel extends Model {

    const STATUS_IN_REVIEW = 1;
    const STATUS_ACCEPTED = 2;
    const STATUS_REJECTED = 3;

    protected static $table_name = 'pool';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'wallet_id' => IdField::init(),
            'amount' => DoubleField::init(),
            'amount_in_pool' => DoubleField::init()->setDefault(0),
            'currency' => CharField::init()->setLength(32),
            'wallet_address' => CharField::init()->setDefault(''),
            'exchange_name' => CharField::init()->setDefault(''),
            'date_start' => CreatedAtField::init(),
            'date_update' => UpdatedAtField::init(),
            'status' => IntField::init()->setDefault(0),
            'deposit_id' => IdField::init()->setNull(true),
        ];
    }
}
