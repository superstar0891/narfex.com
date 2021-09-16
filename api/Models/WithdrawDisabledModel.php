<?php

namespace Models;

use Db\Model\Field\IdField;
use Db\Model\Field\TextField;
use Db\Model\Model;

/**
 * @property int user_id
 * @property string reason
 * @property int banner_id
 */
class WithdrawDisabledModel extends Model {
    protected static $table_name = 'withdraw_disabled';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'reason' => TextField::init(),
            'banner_id' => IdField::init(),
        ];
    }
}
