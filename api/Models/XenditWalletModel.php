<?php


namespace Models;


use Db\Model\Field\CharField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Model;

/**
 * @property int user_id
 * @property string account_number
 * @property string bank_code
 * @property string status
 * @property int expired_at
 */
class XenditWalletModel extends Model {

    const STATUS_PENDING = 'pending';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_ACTIVE = 'active';

    protected static $table_name = 'xendit_wallets';

    protected static function fields(): array {
        return [
            'user_id' => IdField::init()->setNull(true),
            'account_number' => CharField::init()->setNull(true)->setLength(150),
            'bank_code' => CharField::init()->setLength(150),
            'status' => CharField::init()->setNull(true)->setLength(50),
            'expired_at' => IntField::init()
        ];
    }
}
