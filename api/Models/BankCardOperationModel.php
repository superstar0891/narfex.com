<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Model;

/**
 * @property int card_id
 * @property int user_id
 * @property string operation
 * @property float amount
 * @property float got_amount
 * @property string status
 * @property int manager_id
 * @property float fee
 */
class BankCardOperationModel extends Model {

    const OPERATION_BOOK = 'book';
    const OPERATION_WITHDRAWAL = 'withdrawal';

    const STATUS_WAIT_FOR_PAY = 'wait_for_pay';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_WAIT_FOR_REVIEW = 'wait_for_review';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';
    const STATUS_WAIT_FOR_ADMIN_REVIEW = 'wait_for_admin_review';

    protected static $table_name = 'bank_cards_operations';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'card_id' => IdField::init(),
            'user_id' => IdField::init(),
            'operation' => CharField::init()->setLength(32),
            'amount' => DoubleField::init(),
            'got_amount' => DoubleField::init()->setNull(true),
            'status' => CharField::init()->setLength(32),
            'manager_id' => IdField::init()->setNull(true),
            'fee' => DoubleField::init()->setNull(true),
        ];
    }

    public function toJson(): array {
        $status = $this->status;
        if ($status === self::STATUS_WAIT_FOR_ADMIN_REVIEW) {
            $status = self::STATUS_WAIT_FOR_REVIEW;
        }

        return [
            'id' => (int) $this->id,
            'amount' => (double) $this->amount,
            'status' => $status,
            'fee' => (double) $this->fee,
        ];
    }
}
