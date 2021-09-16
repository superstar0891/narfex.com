<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Field\TextField;
use Db\Model\Model;

/**
 * @property int user_id
 * @property string name
 * @property string platform
 * @property string status
 * @property string ip
 * @property string content
 */
class ReviewModel extends Model {
    protected static $table_name = 'reviews';

    protected static $fields = [];

    const STATUS_MODERATION = 0,
        STATUS_PUBLIC = 1;

    protected static function fields(): array {
        return [
            'user_id' => IdField::init()->setNull(true),
            'status' => IntField::init()->setLength(1),
            'name' => CharField::init()->setLength(255),
            'ip' => CharField::init()->setLength(255)->setNull(true),
            'platform' => CharField::init()->setLength(100)->setDefault(PLATFORM_FINDIRI),
            'content' => TextField::init(),
        ];
    }

    public function toJson(): array {
        return  [
            'name' => $this->name,
            'content' => $this->content,
            'created_at' => $this->created_at_timestamp,
        ];
    }
}
