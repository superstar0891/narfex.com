<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Model;

/**
 * @property int user_id
 * @property string currency
 * @property float amount
 * @property string status
 * @property string card_number
 * @property int admin_id
 * @property string reject_message
 */
class BitcoinovnetWithdrawal extends Model {
    protected static $table_name = 'bitcoinovnet_withdrawals';

    const STATUS_PENDING = 'pending',
        STATUS_CONFIRMED = 'confirmed',
        STATUS_REJECT = 'reject';

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'currency' => CharField::init(),
            'amount' => DoubleField::init(),
            'status' => CharField::init(),
            'card_number' => IntField::init(),
            'admin_id' => IdField::init()->setNull(),
            'reject_message' => CharField::init()->setNull(),
        ];
    }

    public function toJson(): array {
        $data = [
            'status' => $this->status,
            'card_number' => $this->card_number,
            'type' => 'withdrawal',
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'created_at' => $this->created_at_timestamp,
        ];

        if ($this->isRejected()) {
            $data['reject_message'] = $this->reject_message;
        }

        return $data;
    }

    public function isRejected() {
        return $this->status === self::STATUS_REJECT;
    }

    public function isConfirmed(): bool {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isPending(): bool {
        return $this->status === self::STATUS_PENDING;
    }

    public function reject(string $reject_message) {
        $this->reject_message = $reject_message;
        $this->status = self::STATUS_REJECT;
        return $this;
    }

    public function approve() {
        $this->status = self::STATUS_CONFIRMED;
        return $this;
    }
}
