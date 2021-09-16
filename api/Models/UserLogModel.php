<?php

namespace Models;

use Db\Model\Field\BooleanField;
use Db\Model\Field\CharField;
use Db\Model\Field\IdField;
use Db\Model\Field\TextField;
use Db\Model\Model;
use Models\Logs\LogHelper;
use Phinx\Db\Adapter\MysqlAdapter;

/**
 * @property int|null user_id
 * @property string action
 * @property bool admin
 * @property LogHelper|null extra
 */
class UserLogModel extends Model {
    protected static $table_name = 'user_logs';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'user_id' => IdField::init()->setNull(true)->setDefault(null),
            'action' =>  CharField::init(),
            'admin' =>  BooleanField::init(),
            'extra' =>  TextField::init()->setLength(MysqlAdapter::TEXT_LONG),
        ];
    }

    function __get(string $name) {
        if ($name == 'extra') {
            $extra = json_decode($this->values['extra'], true);
            if (isset($extra['helper'])) {
                return self::parseHelper($extra);
            }
            return null;
        }
        return parent::__get($name);
    }

    public static function parseHelper(array $extra): ?LogHelper {
        if (isset($extra['helper'])) {
            $helper = $extra['helper'];
            return new $helper($extra);
        }

        return null;
    }

}
