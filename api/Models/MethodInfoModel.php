<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\TextField;
use Db\Model\Model;
use Phinx\Db\Adapter\MysqlAdapter;

/**
 * @property string $method_key
 * @property string lang
 * @property string short_description
 * @property string description
 * @property string result
 * @property string result_example
 * @property string param_descriptions
 */
class MethodInfoModel extends Model {
    protected static $table_name = 'methods_info';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'method_key' => CharField::init()->setLength(150),
            'lang' => CharField::init()->setLength(2),
            'short_description' => TextField::init()->setLength(MysqlAdapter::TEXT_REGULAR),
            'description' => TextField::init()->setLength(MysqlAdapter::TEXT_LONG),
            'result' =>  TextField::init()->setLength(MysqlAdapter::TEXT_LONG),
            'result_example' =>  TextField::init()->setLength(MysqlAdapter::TEXT_LONG),
            'param_descriptions' => TextField::init()->setLength(MysqlAdapter::TEXT_LONG),
        ];
    }
}
