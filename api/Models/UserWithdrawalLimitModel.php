<?php

namespace Models;

use Db\Db;
use Db\Model\Field\FloatField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Model;
use Db\Where;

/**
 * @property int id
 * @property int user_id
 * @property float amount
 * @property int started_at
 * @property int created_at_timestamp
 * @property int updated_at_timestamp
 * @property int deleted_at
 */
class UserWithdrawalLimitModel extends Model {
    const LIMIT_UPDATE_HOURS = 24;

    protected static $table_name = 'user_withdrawal_limits';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'amount' => FloatField::init()->setDefault(0),
            'started_at' => IntField::init()->setLength(10)->setNull(true)->setUnsigned(),
        ];
    }

    public function incrLimit(float $amount): bool {
        $ret = Db::add(static::getTableName(), 'amount', $amount, Where::equal('id', (int) $this->id));
        if ($ret) {
            $this->amount += $amount;
        }
        return $ret;
    }

    public function decrLimit(float $amount): bool {
        $ret = Db::sub(static::getTableName(), 'amount', $amount, Where::equal('id', (int) $this->id));
        if ($ret) {
            $this->amount -= $amount;
        }
        return $ret;
    }
}
