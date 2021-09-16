<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IntField;
use Db\Model\Field\TextField;
use Db\Model\Model;

/**
 * @property string class
 * @property float serialized_queue
 * @property string tries
 * @property string current_try
 * @property int done
 * @property int failed
 * @property int is_working
 */
class QueueModel extends Model {
    protected static $table_name = 'queue_jobs';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'class' => CharField::init()->setLength(256),
            'serialized_queue' => TextField::init(),
            'tries' => IntField::init()->setUnsigned()->setNull(),
            'current_try' => IntField::init()->setUnsigned()->setDefault(0),
            'done' => IntField::init()->setUnsigned()->setLength(1)->setDefault(0),
            'failed' => IntField::init()->setUnsigned()->setLength(1)->setDefault(0),
            'is_working' => IntField::init()->setUnsigned()->setLength(1)->setDefault(0),
        ];
    }
}
