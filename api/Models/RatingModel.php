<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IntField;
use Db\Model\Model;

/**
 * @property int user_id
 * @property string user_login
 * @property float amount
 * @property string currency
 * @property int rank
 */
class RatingModel extends Model {
    protected static $table_name = 'rating';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'user_id' => IntField::init()->setUnsigned(),
            'user_login' => CharField::init()->setLength(256),
            'amount' => DoubleField::init(),
            'currency' => DoubleField::init(),
            'rank' => IntField::init()->setUnsigned(),
        ];
    }

    public function toJson(): array {
        return [
            'user_login' => $this->user_login,
            'rank' => $this->rank,
            'amount' => floatval($this->amount),
        ];
    }
}
