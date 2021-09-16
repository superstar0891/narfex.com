<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\IntField;
use Db\Model\Field\TextField;
use Db\Model\Model;

/**
 * @property int queue_id
 * @property string class
 * @property string error_trace
 * @property string error_message
 */
class QueueErrorModel extends Model {
    protected static $table_name = 'queue_errors';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'class' => CharField::init()->setLength(256),
            'error_message' => TextField::init(),
            'error_trace' => TextField::init(),
            'queue_id' => IntField::init()->setUnsigned()->setNull(),
        ];
    }
}
