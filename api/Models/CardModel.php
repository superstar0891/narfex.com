<?php

namespace Models;

use Db\Db;
use Db\Model\Field\BooleanField;
use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Model;
use Db\Where;

/**
 * @property string name
 * @property string merchant
 * @property string card_number
 * @property string wallet_number
 * @property float balance
 * @property float available_amount
 * @property int added_by
 * @property string oauth_token
 * @property string hook_id
 * @property string secret_key
 * @property bool booked
 * @property int book_expiration
 * @property bool active
 * @property float refill_limit
 * @property float turnover_limit
 * @property int limit_date_till_timestamp
 */
class CardModel extends Model {
    protected static $table_name = 'merchant_cards';

    const MERCHANT_QIWI = 'qiwi',
        MERCHANT_TINKOFF = 'tinkoff';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'name' => CharField::init()->setLength(255)->setNull(true),
            'merchant' => CharField::init()->setLength(32),
            'card_number' => CharField::init()->setLength(100),
            'wallet_number' => CharField::init()->setLength(100),
            'balance' => DoubleField::init()->setDefault(0),
            'added_by' => IdField::init(),
            'oauth_token' => CharField::init()->setLength(100)->setNull(true),
            'hook_id' => CharField::init()->setLength(100)->setNull(true),
            'secret_key' => CharField::init()->setLength(100)->setNull(true),
            'booked' => IdField::init()->setDefault(0),
            'book_expiration' => IntField::init()->setLength(10)->setNull(true),
            'active' => IntField::init()->setLength(1)->setDefault(0),
            'available_amount' => DoubleField::init()->setDefault(0),
        ];
    }

    public function booked(ReservedCardModel $reservation) {
        $this->booked = $reservation->id;
        if (is_null($reservation->session_id)) {
            $this->book_expiration = time() + settings()->bitcoinovnet_book_time;
        } else {
            $this->book_expiration = time() + settings()->bitcoinovnet_manual_book_time;
        }
        $this->save();
    }

    public function unbook() {
        $this->booked = 0;
        $this->book_expiration = null;
        $this->save();
    }

    public function toJson(): array {
        return [
            'number' => $this->card_number,
            'expire_in' => (int) $this->book_expiration,
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

    public function isBookedBy(ReservedCardModel $reservation = null) {
        return $this->booked == $reservation->id;
    }

    public function isBooked() {
        return $this->booked > 0 && $this->book_expiration > time();
    }

    public function setSecretKeyHash($raw_hash): CardModel {
        $hash = encrypt($raw_hash);
        $hex_hash = bin2hex($hash);
        $this->secret_key = $hex_hash;
        return $this;
    }

    public function getSecretDecoded(): string {
        $hash = hex2bin($this->secret_key);
        return decrypt($hash);
    }
}
