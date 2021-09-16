<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Field\TextField;
use Db\Model\Model;

/**
 * @property int id
 * @property int user_id
 * @property string title
 * @property string message
 * @property string type
 * @property int important
 * @property int unread
 * @property int object_id
 * @property string extra
 */
class NotificationModel extends Model {
    const TYPE_DEPOSIT_COMPLETED = 'deposit_completed',
        TYPE_VERIFICATION = 'verification',
        TYPE_AGENT_INVITE = 'agent_invite',
        TYPE_POOL_APPROVED = 'pool_approved',
        TYPE_POOL_DECLINE = 'pool_declined',
        TYPE_TRANSACTION_SEND = 'transaction_send',
        TYPE_TRANSACTION_RECEIVE = 'transaction_receive',
        TYPE_TRANSFER_RECEIVE = 'transfer_receive',
        TYPE_WITHDRAWAL = 'withdrawal',
        TYPE_REFILL = 'refill',
        TYPE_USER_AUTHORIZE = 'user_authorize',
        TYPE_SAVING_ACCRUAL = 'saving_accrual';

    protected static $table_name = 'notifications';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'title' => CharField::init()->setNull(true),
            'message' => TextField::init()->setNull(true),
            'important' => IntField::init()->setLength(1)->setDefault(0),
            'type' => CharField::init()->setLength(60),
            'unread' => IntField::init()->setLength(1)->setDefault(1),
            'object_id' => IdField::init()->setNull(true),
            'extra' => CharField::init()->setDefault(''),
        ];
    }
}
