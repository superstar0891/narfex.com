<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\TextField;
use Db\Model\Model;

/**
 * @property int id
 * @property string name
 * @property string value
 * @property string type
 * @property int lang
 */
class LangsModel extends Model {

    const
        WEB_LANG = 'web',
        MOBILE_LANG = 'mobile',
        BACKEND_LANG = 'backend';

    protected static $table_name = 'langs';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'name' => CharField::init()->setLength(150),
            'value' => TextField::init(),
            'type' => TextField::init(),
            'lang' =>  CharField::init()->setLength(2),
        ];
    }
}
