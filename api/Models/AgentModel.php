<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\IdField;
use Db\Model\Model;

/**
 * @property int user_id
 * @property string platform
 */
class AgentModel extends Model {

    protected static $table_name = 'agents';

    protected static $fields = [];

    const PLATFORM_BITCOINOVNET = 'bitcoinovnet';

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'platform' => CharField::init()->setLength(32),
        ];
    }
}
