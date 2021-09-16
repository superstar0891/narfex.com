<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\CreatedAtField;
use Db\Model\Field\IdField;
use Db\Model\Field\RandomTokenField;
use Db\Model\Field\TextField;
use Db\Model\Model;

/**
 * @property int app_id
 * @property string token
 * @property string type
 * @property int owner_id
 * @property string ip
 * @property string public_key
 * @property string permissions
 * @property string allow_ips
 * @property string name
 * @property int add_date
 */
class AppTokenModel extends Model {
    const TYPE_EXCHANGE = 'exchange';
    const TYPE_USER = 'user';

    protected static $table_name = 'apps_tokens';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'app_id' =>  IdField::init(),
            'token' =>  CharField::init()->setLength(128),
            'public_key' => RandomTokenField::init()->setNull(true),
            'type' =>  CharField::init()->setLength(32),
            'owner_id' =>  IdField::init(),
            'ip' => CharField::init()->setLength(20),
            'permissions' => TextField::init()->setNull(true),
            'allow_ips' => TextField::init()->setNull(true),
            'name' => CharField::init()->setNull(true)->setLength(128),
            'add_date' => CreatedAtField::init(),
        ];
    }
}
