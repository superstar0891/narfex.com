<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\TextField;
use Db\Model\Model;

/**
 * @property int id
 * @property string name
 * @property string value
 * @property string lang
 */
class SiteLangModel extends Model {
    protected static $table_name = 'site_lang';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'name' => CharField::init()->setLength(150),
            'value' => TextField::init(),
            'lang' =>  CharField::init()->setLength(2),
        ];
    }
}
