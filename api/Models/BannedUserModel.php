<?php


namespace Models;


use Db\Model\Field\CharField;
use Db\Model\Field\IdField;
use Db\Model\Model;

/**
 * Class BannedUserModel
 * @package Models
 * @property int $user_id
 * @property string $reason
 * @property int $admin_id
 */
class BannedUserModel extends Model {
    protected static $table_name = 'banned_users';

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'admin_id' => IdField::init()->setNull(),
            'reason' => CharField::init()->setNull()
        ];
    }
}
