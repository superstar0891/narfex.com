<?php

namespace Models;

use Core\Services\Mail\MailAdapter;
use Core\Services\Mail\Templates;
use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\EmailField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Model;

/**
 * @property string request_id
 * @property int card_id
 * @property int user_id
 * @property string txid
 * @property string operation
 * @property float amount
 * @property float got_amount
 * @property string wallet_address
 * @property string currency
 * @property string card_number
 * @property string card_owner_name
 * @property string email
 * @property string promo_code
 * @property string status
 * @property string hash
 * @property float fee
 * @property float initial_rate
 * @property float current_rate
 * @property float rate_update_at_timestamp
 * @property int validate
 * @property string photo_name
 * @property int profit_id
 * @property int session_id
 */
class ReservedCardModel extends Model {

    const REQUEST_ID_START_FROM = 1000;

    const OPERATION_SELL = 'sell';
    const OPERATION_BUY = 'buy';

    const STATUS_WAIT_FOR_SEND = 'wait_for_send';
    const STATUS_WAIT_FOR_PAY = 'wait_for_pay';
    const STATUS_MODERATION = 'moderation';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_WRONG_AMOUNT = 'wrong_amount';
    const STATUS_EXPIRED = 'expired';
    const STATUS_BLOCKCHAIN_START_SEND = 'blockchain_start_send';

    public static $statuses = [
        self::STATUS_WAIT_FOR_SEND => self::STATUS_WAIT_FOR_SEND,
        self::STATUS_WAIT_FOR_PAY => self::STATUS_WAIT_FOR_PAY,
        self::STATUS_MODERATION => self::STATUS_MODERATION,
        self::STATUS_CANCELLED => self::STATUS_CANCELLED,
        self::STATUS_CONFIRMED => self::STATUS_CONFIRMED,
        self::STATUS_REJECTED => self::STATUS_REJECTED,
        self::STATUS_EXPIRED => self::STATUS_EXPIRED,
        self::STATUS_BLOCKCHAIN_START_SEND => self::STATUS_BLOCKCHAIN_START_SEND,
    ];

    public static $client_statuses = [
        self::STATUS_WAIT_FOR_SEND => self::STATUS_WAIT_FOR_SEND,
        self::STATUS_WAIT_FOR_PAY => self::STATUS_WAIT_FOR_PAY,
        self::STATUS_MODERATION => self::STATUS_WAIT_FOR_SEND,
        self::STATUS_CANCELLED => self::STATUS_CANCELLED,
        self::STATUS_CONFIRMED => self::STATUS_CONFIRMED,
        self::STATUS_REJECTED => self::STATUS_REJECTED,
        self::STATUS_EXPIRED => self::STATUS_EXPIRED,
        self::STATUS_BLOCKCHAIN_START_SEND => self::STATUS_WAIT_FOR_SEND,
    ];

    public static $status_translate = [
        self::STATUS_WAIT_FOR_SEND => 'Создана',
        self::STATUS_WAIT_FOR_PAY => 'Ожидает пополнения',
        self::STATUS_MODERATION => 'На модерации',
        self::STATUS_CANCELLED => 'Отменена',
        self::STATUS_CONFIRMED => 'Выполнена',
        self::STATUS_REJECTED => 'Отклонена',
        self::STATUS_EXPIRED => 'Просрочена',
        self::STATUS_WRONG_AMOUNT => 'Неверная сумма пополнения',
        self::STATUS_BLOCKCHAIN_START_SEND => 'Средства отправлены',
    ];

    protected static $table_name = 'reserved_merchant_cards';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'request_id' => CharField::init(),
            'card_id' => IdField::init(),
            'user_id' => IdField::init()->setNull(),
            'profit_id' => IdField::init()->setNull(),
            'session_id' => IdField::init()->setNull(),
            'txid' => CharField::init()->setLength(100)->setNull(),
            'operation' => CharField::init()->setLength(32),
            'wallet_address' => CharField::init()->setLength(100),
            'currency' => CharField::init()->setLength(32),
            'card_number' => CharField::init()->setLength(16),
            'card_owner_name' => CharField::init()->setLength(255),
            'email' => EmailField::init()->setNull(),
            'promo_code' => CharField::init()->setLength(100)->setNull(),
            'amount' => DoubleField::init(),
            'got_amount' => DoubleField::init()->setNull(),
            'status' => CharField::init()->setLength(32),
            'fee' => DoubleField::init(),
            'initial_rate' => DoubleField::init(),
            'current_rate' => DoubleField::init(),
            'rate_update_at_timestamp' => IntField::init()->setLength(10),
            'hash' => CharField::init()->setLength(100),
            'validate' => IntField::init()->setDefault(0)->setLength(1),
            'photo_name' => CharField::init()->setNull(),
        ];
    }

    public function toJson(): array {
        return [
            'request_id' => $this->getRequestId(),
            'hash' => $this->hash,
            'wallet_address' => $this->wallet_address,
            'amount' => (float) $this->amount,
            'status' => self::$client_statuses[$this->status],
            'rate' => (float) $this->current_rate,
            'rate_update' => (int) $this->rate_update_at_timestamp,
            'txid' => $this->txid,
            'validation' => (bool) $this->validate,
            'created_at' => (int) $this->created_at_timestamp,
        ];
    }

    public function isValid(): bool {
        return (bool) $this->validate;
    }

    public function validate() {
        $this->validate = 1;
        $this->save();
    }

    public function unvalidate() {
        $this->validate = 0;
        $this->save();
    }

    public function getRequestId() {
        return $this->request_id;
    }

    /**
     * @throws \Exception
     */
    public function cancelled() {
        if (!in_array($this->status, [
            self::STATUS_CANCELLED,
            self::STATUS_WAIT_FOR_SEND,
            self::STATUS_WAIT_FOR_PAY
        ])) {
            throw new \Exception('Wrong status, request cannot be canceled');
        }
        $this->status = self::STATUS_CANCELLED;
        $this->save();
    }

    /**
     * @throws \Exception
     */
    public function moderation() {
        if (in_array($this->status, [
            self::STATUS_CANCELLED,
            self::STATUS_CONFIRMED,
            self::STATUS_REJECTED,
            self::STATUS_EXPIRED,
            self::STATUS_BLOCKCHAIN_START_SEND,
        ])) {
            throw new \Exception('Wrong status, request cannot be sent to moderation');
        }
        $this->status = self::STATUS_MODERATION;
        $this->save();
        if ($this->email) {
            MailAdapter::sendBitcoinovnet(
                $this->email,
                'Заявка отправлена на ручную модерацию',
                Templates::MODERATION_BITCOINOVNET,
                $this->toJsonReservationEmailInfo()
            );
        }
    }

    /**
     * @throws \Exception
     */
    public function reject() {
        if (in_array($this->status, [
            self::STATUS_CANCELLED,
            self::STATUS_CONFIRMED,
            self::STATUS_REJECTED,
            self::STATUS_EXPIRED,
        ])) {
            throw new \Exception('Wrong status, request cannot be reject');
        }
        $this->status = self::STATUS_REJECTED;
        $this->save();
        if ($this->email) {
            MailAdapter::sendBitcoinovnet(
                $this->email,
                'Заявка отклонена',
                Templates::REJECT_BITCOINOVNET,
                $this->toJsonReservationEmailInfo()
            );
        }
    }

    /**
     * @throws \Exception
     */
    public function confirm() {
        if (in_array($this->status, [
            self::STATUS_CANCELLED,
            self::STATUS_CONFIRMED,
            self::STATUS_REJECTED,
            self::STATUS_EXPIRED,
        ])) {
            throw new \Exception('Wrong status, request cannot be confirmed');
        }
        $this->status = self::STATUS_CONFIRMED;
        $this->save();
        if ($this->email) {
            MailAdapter::sendBitcoinovnet(
                $this->email,
                'Заявка выполнена',
                Templates::DONE_BITCOINOVNET,
                $this->toJsonReservationEmailInfo()
            );
        }
    }

    public function toJsonReservationEmailInfo() {
        return [
            'rate' => $this->current_rate,
            'status' => self::$status_translate[$this->status],
            'request_id' => $this->request_id,
            'amount' => (int) ceil($this->amount),
            'link' => sprintf('https://bitcoinov.net/request/%s/%s', $this->request_id, $this->hash),
            'txid' => $this->txid,
            'created_at' => (new \DateTime())
                ->setTimestamp($this->created_at_timestamp)
                ->setTimezone(new \DateTimeZone('Europe/Moscow'))
                ->format('d-m-Y H:i'),
        ];
    }
}
