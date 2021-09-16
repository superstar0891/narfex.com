<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DateTimeField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Model;

/**
 * @property int user_id
 * @property string country
 * @property string ip
 * @property string browser
 * @property int device
 * @property string action
 * @property string created_at
 */
class LogModel extends Model {
    protected static $table_name = 'logging';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'country' => CharField::init()->setLength(60)->setDefault(''),
            'ip' => CharField::init()->setNull(true),
            'browser' => CharField::init()->setNull(true),
            'device' => IntField::init()->setDefault(0),
            'action' => CharField::init(),
            'created_at' => DateTimeField::init(),
        ];
    }
}
