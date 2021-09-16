<?php


namespace Models;


use Core\Services\BalanceHistory\SetAmounts;
use Core\Services\BalanceHistory\SetFromAndToId;
use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Model;

/**
 * @property int from_user_id
 * @property int to_user_id
 * @property string from_type
 * @property int from_id
 * @property string to_type
 * @property int to_id
 * @property string from_currency
 * @property string to_currency
 * @property float from_amount
 * @property float to_amount
 * @property string operation
 * @property int object_id
 */
class UserBalanceHistoryModel extends Model {
    use SetAmounts, SetFromAndToId;

    protected $from_user_id_field = 'from_user_id';
    protected $to_user_id_field = 'to_user_id';
    protected $from_currency_field = 'from_currency';
    protected $to_currency_field = 'to_currency';

    const TYPE_BALANCE = 0,
        TYPE_WALLET = 1;

    const OPERATION_SWAP = 1,
        OPERATION_TRANSACTION = 2,
        OPERATION_TRANSFER = 3,
        OPERATION_REFILL = 4,
        OPERATION_WITHDRAWAL = 5,
        OPERATION_WITHDRAWAL_REQUEST = 6,
        OPERATION_INTERNAL_TRANSACTION = 7,
        OPERATION_BANK_CARD_REFILL_REJECT = 8,
        OPERATION_SAVING_ACCRUAL = 9,
        OPERATION_PROMO_REWARD = 10,
        OPERATION_BITCOINOVNET_PROFIT = 11,
        OPERATION_BITCOINOVNET_WITHDRAWAL = 12;

    const OPERATIONS_MAP = [
        self::OPERATION_SWAP => 'swap',
        self::OPERATION_TRANSACTION => 'transaction',
        self::OPERATION_TRANSFER => 'transfer',
        self::OPERATION_REFILL => 'refill',
        self::OPERATION_WITHDRAWAL => 'withdrawal',
        self::OPERATION_WITHDRAWAL_REQUEST => 'withdrawal_request',
        self::OPERATION_INTERNAL_TRANSACTION => 'internal_transaction',
        self::OPERATION_BANK_CARD_REFILL_REJECT => 'bank_card_refill_reject',
        self::OPERATION_SAVING_ACCRUAL => 'operation_accrual',
        self::OPERATION_PROMO_REWARD => 'promo_reward',
    ];

    const STATUS_COMPLETED = 1,
        STATUS_PENDING = 0,
        STATUS_CONFIRMATION = 2,
        STATUS_FAILED = 3,
        STATUS_ACCEPTED = 4;

    const STATUSES_MAP = [
        self::STATUS_COMPLETED => 'completed',
        self::STATUS_PENDING => 'pending',
        self::STATUS_CONFIRMATION => 'confirmation',
        self::STATUS_FAILED => 'failed',
        self::STATUS_ACCEPTED => 'accepted',
    ];

    protected static $table_name = 'user_balance_histories';

    protected static function fields(): array {
        return [
            'from_user_id' => IdField::init()->setNull(),
            'to_user_id' => IdField::init()->setNull(),
            'from_type' => CharField::init()->setNull(),
            'from_id' => IdField::init()->setNull(),
            'to_type' => CharField::init()->setNull(),
            'to_id' => IdField::init()->setNull(),
            'from_currency' => CharField::init()->setNull()->setLength(16),
            'to_currency' => CharField::init()->setNull()->setLength(16),
            'from_amount' => DoubleField::init()->setNull(),
            'to_amount' => DoubleField::init()->setNull(),
            'operation' => CharField::init(),
            'object_id' => IdField::init()->setNull(),
        ];
    }
}
