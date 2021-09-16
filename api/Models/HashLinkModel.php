<?php


namespace Models;


use Db\Model\Field\CharField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Field\TextField;
use Db\Model\Model;

/**
 * @property string hash
 * @property string type
 * @property int user_id
 * @property string extra
 * @property int expired_at
 */
class HashLinkModel extends Model {
    protected static $table_name = 'hashed_links';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'hash' => CharField::init()->setLength(50),
            'type' => TextField::init()->setLength(50),
            'extra' => TextField::init()->setNull(),
            'expired_at' => IntField::init(),
        ];
    }

    public function getExtra(): ?\stdClass {
        return $this->extra ? json_decode($this->extra) : null;
    }
}
