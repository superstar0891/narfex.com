<?php

namespace Models;

use Core\Services\BalanceHistory\BalanceHistorySaver;
use Db\Model\Field\CharField;
use Db\Model\Field\CreatedAtField;
use Db\Model\Field\DecimalField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Field\UpdatedAtField;
use Db\Model\Model;
use Db\Transaction;
use Exceptions\WithdrawalRequests\InvalidWithdrawalStatusException;

/**
 * @property string user_id
 * @property string user_address
 * @property double amount
 * @property int wallet_id
 * @property double currency
 * @property double to_address
 * @property double exec_at
 * @property double status
 * @property double updated_at
 * @property double created_at
 * @property double paused_at
 */
class WithdrawalRequest extends Model {
    const STATUS_DONE = 'done',
        STATUS_PAUSED = 'paused',
        STATUS_BOOST = 'boost',
        STATUS_REJECTED = 'rejected',
        STATUS_PENDING = 'pending';

    protected static $table_name = 'withdrawal_requests';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'wallet_id' => IdField::init()->setNull(),
            'user_address' => CharField::init()->setLength(256),
            'amount' => DecimalField::init(),
            'currency' => CharField::init()->setLength(16),
            'to_address' => CharField::init()->setLength(256),
            'exec_at' => IntField::init(),
            'status' => CharField::init()->setLength(16),
            'updated_at' => UpdatedAtField::init(),
            'created_at' => CreatedAtField::init(),
            'paused_at' => CreatedAtField::init(),
        ];
    }

    public function pause() {
        $this->status = static::STATUS_PAUSED;
        $this->paused_at = time();
    }

    public function reject() {
        $this->status = static::STATUS_REJECTED;
    }

    /**
     * @throws InvalidWithdrawalStatusException
     */
    public function start() {
        if ($this->status !== static::STATUS_PAUSED) {
            throw new InvalidWithdrawalStatusException('Status must be pause');
        }
        $this->status = static::STATUS_PENDING;
        $diff = (new \DateTime)->setTimestamp($this->exec_at)
            ->diff((new \DateTime)->setTimestamp($this->paused_at), true)
            ->s;
        $this->paused_at = null;
        $this->exec_at = time() + $diff;
    }

    public function save() {
        if ($this->existsInDatabase()) {
            parent::save();
            return $this;
        } else {
            Transaction::wrap(function(){
                parent::save();
                BalanceHistorySaver::make()
                    ->setCreatedAt($this->created_at_timestamp)
                    ->setOperation(UserBalanceHistoryModel::OPERATION_WITHDRAWAL_REQUEST)
                    ->setFromRaw(
                        UserBalanceHistoryModel::TYPE_WALLET,
                        $this->wallet_id,
                        $this->user_id,
                        $this->currency
                    )
                    ->setObjectId($this->id)
                    ->save();
                return $this;
            });
        }
    }
}
