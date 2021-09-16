<?php

namespace Models;

use Db\Model\Field\DateTimeField;
use Db\Model\Field\FloatField;
use Db\Model\Field\IntField;
use Db\Model\Model;

/**
 * @property int deposit_id
 * @property float old_coeff
 * @property float new_coeff
 * @property string created_at
 */
class DepositCoeffHistoryModel extends Model {
    protected static $table_name = 'deposit_coeff_history';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'deposit_id' => IntField::init(),
            'old_coeff' => FloatField::init(),
            'new_coeff' => FloatField::init(),
            'created_at' => DateTimeField::init(),
        ];
    }
}
