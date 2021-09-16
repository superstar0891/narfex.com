<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\TextField;
use Db\Model\Model;

/**
 * @property int id
 * @property string name
 * @property int value
 * @property int lang
 */
class LangModel extends Model {
    protected static $table_name = 'lang';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'name' => CharField::init()->setLength(150),
            'value' => TextField::init(),
            'lang' =>  CharField::init()->setLength(2),
        ];
    }
}
