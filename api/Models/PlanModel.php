<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IntField;
use Db\Model\Model;

/**
 * @property int id
 * @property int days
 * @property string description
 * @property int min
 * @property int max
 * @property double percent
 * @property string currency
 */
class PlanModel extends Model {
    protected static $table_name = 'plans';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'days' => IntField::init()->setLength(32),
            'description' => CharField::init()->setLength(255),
            'min' =>  DoubleField::init(),
            'max' =>  DoubleField::init(),
            'percent' => DoubleField::init(),
            'currency' => CharField::init()->setLength(16),
        ];
    }
}
