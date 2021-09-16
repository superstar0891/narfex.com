<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DateTimeField;
use Db\Model\Field\IntField;
use Db\Model\Field\TextField;
use Db\Model\Model;

/**
 * @property int user_id
 * @property string address
 * @property string currency
 * @property string options
 * @property string created_at
 */
class AddressModel extends Model {
    protected static $table_name = 'addresses';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'user_id' => IntField::init(),
            'address' => CharField::init()->setLength(128),
            'currency' => CharField::init()->setLength(16),
            'options' => TextField::init(),
            'created_at' => DateTimeField::init(),
            'commerce_address' => IntField::init()->setDefault(0),
            '_delete' => IntField::init()->setDefault(0),
        ];
    }

    public function getOptions(): array {
        return json_decode($this->options, true) ?: [];
    }
}
