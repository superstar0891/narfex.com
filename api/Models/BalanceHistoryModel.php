<?php

namespace Models;

use Core\Services\Merchant\XenditService;
use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Field\TextField;
use Db\Model\Model;

/**
 * @property int from_balance_id
 * @property int to_balance_id
 * @property double amount
 * @property string type
 * @property string user_id
 * @property string from_balance_category
 * @property string to_balance_category
 * @property string status
 * @property string extra
 */
class BalanceHistoryModel extends Model {
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CONFIRMATION = 'confirmation';
    const STATUS_FAILED = 'failed';

    const TYPE_SEND = 'send';
    const TYPE_RECEIVE = 'receive';
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_REFILL = 'refill';
    const TYPE_BUY_TOKEN = 'buy_token';
    const TYPE_SELL_TOKEN = 'sell_token';

    protected static $table_name = 'balances_history';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'from_balance_id' => IdField::init()->setNull(true),
            'to_balance_id' => IdField::init()->setNull(true),
            'amount' => DoubleField::init(),
            'type' => CharField::init()->setLength(60),
            'user_id' => IdField::init(),
            'from_balance_category' => CharField::init()->setLength(32)->setNull(true),
            'to_balance_category' => CharField::init()->setLength(32)->setNull(true),
            'status' => CharField::init()->setLength(32)->setDefault(self::STATUS_COMPLETED),
            'extra' => TextField::init()->setNull(true),
        ];
    }

    public static function withdrawalExtraGenerate (
        string $account_holder_name,
        string $account_number,
        string $currency,
        string $bank_code,
        ?int $admin_id = null,
        ?string $external_id = null,
        ?string $fail_reason = null,
        int $fee = XenditService::WITHDRAWAL_FEE,
        array $email_to = []): array {
        return compact('account_number', 'account_holder_name', 'currency', 'bank_code', 'fee', 'external_id', 'fail_reason', 'admin_id', 'email_to');
    }

    public static function refillExtraGenerate (string $id, string $payment_id, string $transaction_timestamp, string $bank_code, string $fee, string $currency): array {
        $arr = compact('id', 'payment_id', 'transaction_timestamp', 'bank_code', 'fee');
        $arr['currency'] = mb_strtoupper($currency);
        return $arr;
    }

    public static function sellTokenExtraGenerate(float $token_price, float $btc_amount, ?float $price) {
        return compact('token_price', 'btc_amount', 'price');
    }
}
