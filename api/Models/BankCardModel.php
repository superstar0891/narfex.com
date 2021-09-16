<?php

namespace Models;

use Core\Services\Merchant\CardsService;
use Db\Db;
use Db\Model\Field\BooleanField;
use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Model;
use Db\Where;

/**
 * @property int    added_by
 * @property string bank
 * @property string number
 * @property string holder_name
 * @property string expiration_date
 * @property int    code
 * @property int    booked_by
 * @property int    book_expiration
 * @property bool   active
 * @property string mobile_number
 * @property float  balance
 * @property int    managed_by
 */
class BankCardModel extends Model {

    protected static $table_name = 'bank_cards';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'added_by' => IdField::init(),
            'bank' => CharField::init()->setLength(32),
            'number' => CharField::init()->setLength(20),
            'holder_name' => CharField::init()->setDefault(64),
            'expiration_date' => CharField::init()->setLength(5),
            'code' => IntField::init()->setLength(4),
            'booked_by' => IdField::init()->setNull(true),
            'book_expiration' => IntField::init()->setLength(10)->setNull(true),
            'active' => BooleanField::init()->setDefault(false),
            'mobile_number' => CharField::init()->setLength(20),
            'balance' => DoubleField::init()->setDefault(0),
            'managed_by' => IdField::init()->setNull(true),
        ];
    }

    public function toJson(): array {
        return [
            'number' => $this->number,
            'expire_in' => (int) $this->book_expiration,
            'bank' => [
                'code' => $this->bank,
                'name' => $this->getBankName(),
                'holder_name' => $this->holder_name,
            ]
        ];
    }

    public function incrBalance($amount): bool {
        $ret = Db::add(static::getTableName(), 'balance', $amount, Where::equal('id', (int) $this->id));
        if ($ret) {
            $this->balance += $amount;
        }
        return $ret;
    }

    public function decrBalance($amount): bool {
        $ret = Db::sub(static::getTableName(), 'balance', $amount, Where::equal('id', (int) $this->id));
        if ($ret) {
            $this->balance -= $amount;
        }
        return $ret;
    }

    public function getBankName(): string {
        return CardsService::BANKS[$this->bank]['name'];
    }
}
