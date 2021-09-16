<?php

namespace Models;

use Db\Db;
use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\FloatField;
use Db\Model\Field\IntField;
use Db\Model\Model;
use Db\Where;

/* @property int agent_percent
 * @property int representative_percent
 * @property float deposit_withdraw_min
 * @property float dynamic_minimal_coeff
 * @property float dynamic_point_x
 * @property float dynamic_point_y
 * @property float dynamic_start_percent
 * @property float wallet_withdraw_daily_max
 * @property float deposit_fast_withdraw_threshold
 * @property float deposit_profit_drop
 * @property float withdraw_disabled_coeff
 * @property string host
 * @property float exchange_commision
 * @property int wallet_withdraw_delay
 * @property int wallet_withdraw_enabled
 * @property string wallet_refill_emails
 * @property int wallet_withdraw_email_notif
 * @property string wallet_withdraw_emails
 * @property float pool_percent
 * @property float token_price
 * @property float token_sold_amount
 * @property float fiat_exchange_fee_sell
 * @property float fiat_exchange_fee_buy
 * @property int agent_token_percent
 * @property float withdrawal_amount_day_limit
 * @property int fiat_withdrawal_manual_timer
 * @property double swap_usd_daily_limit
 * @property double swap_min_fiat_wallet_transaction_in_usd
 * @property int bitcoinovnet_book_time
 * @property int bitcoinovnet_manual_book_time
 * @property double bitcoinovnet_max_change_course
 * @property int bitcoinovnet_rate_update
 * @property float bitcoinovnet_max_transaction_amount
 * @property float bitcoinovnet_min_transaction_amount
 * @property float bitcoinovnet_btc_balance
 * @property float bitcoinovnet_agent_max_percent
 * @property float bitcoinovnet_withdrawal_min_balance_amount
 * @property float bitcoinovnet_swap_percent
 * @property float bitcoinovnet_swap_manual_percent
 * @property float bitcoinovnet_net_profit_percent
 * @property float bitcoinovnet_active
*/
class SiteSettingsModel extends Model {
    const TYPE_INVESTMENT_SETTINGS = 'Investment',
        TYPE_EXCHANGE_SETTINGS = 'Exchange',
        TYPE_SWAP_SETTINGS = 'Swap',
        TYPE_WALLET_SETTINGS = 'Wallet',
        TYPE_TOKEN_SETTINGS = 'Token',
        TYPE_FEES_SETTINGS = 'Fees',
        TYPE_BITCOINOVNET = 'Bitcoinov.net';

    const SETTINGS_BY_TYPE = [
        self::TYPE_INVESTMENT_SETTINGS => [
            'agent_percent',
            'representative_percent',
            'deposit_withdraw_min',
            'dynamic_minimal_coeff',
            'dynamic_point_x',
            'dynamic_point_y',
            'dynamic_start_percent',
            'deposit_fast_withdraw_threshold',
            'deposit_profit_drop',
            'withdraw_disabled_coeff',
            'pool_percent',
        ],
        self::TYPE_EXCHANGE_SETTINGS => [
            'exchange_commision'
        ],
        self::TYPE_SWAP_SETTINGS => [
            'fiat_exchange_fee_sell',
            'fiat_exchange_fee_buy',
            'swap_usd_daily_limit',
            'swap_min_fiat_wallet_transaction_in_usd',
        ],
        self::TYPE_WALLET_SETTINGS => [
            'wallet_withdraw_enabled',
            'wallet_withdraw_daily_max',
            'wallet_withdraw_delay',
            'wallet_withdraw_email_notif',
            'wallet_refill_emails',
            'wallet_withdraw_emails',
            'withdrawal_amount_day_limit',
            'fiat_withdrawal_manual_timer'
        ],
        self::TYPE_TOKEN_SETTINGS => [
            'token_price',
            'token_sold_amount',
            'agent_token_percent',
        ],
        self::TYPE_FEES_SETTINGS => [
            'xendit_percent_fee',
            'xendit_min_fee',
            'rub_refill_percent_fee',
        ],
        self::TYPE_BITCOINOVNET => [
            'bitcoinovnet_active',
            'bitcoinovnet_book_time',
            'bitcoinovnet_manual_book_time',
            'bitcoinovnet_max_change_course',
            'bitcoinovnet_rate_update',
            'bitcoinovnet_max_transaction_amount',
            'bitcoinovnet_min_transaction_amount',
            'bitcoinovnet_btc_balance',
            'bitcoinovnet_agent_max_percent',
            'bitcoinovnet_swap_percent',
            'bitcoinovnet_swap_manual_percent',
            'bitcoinovnet_net_profit_percent',
            'bitcoinovnet_withdrawal_min_balance_amount',
        ]
    ];

    protected static $table_name = 'site_settings';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'agent_percent' => IntField::init(),
            'representative_percent' => IntField::init(),
            'deposit_withdraw_min' => FloatField::init(),
            'dynamic_minimal_coeff' => FloatField::init(),
            'dynamic_point_x' => FloatField::init(),
            'dynamic_point_y' => FloatField::init(),
            'dynamic_start_percent' => FloatField::init(),
            'wallet_withdraw_daily_max' => FloatField::init(),
            'deposit_fast_withdraw_threshold' => FloatField::init(),
            'deposit_profit_drop' => FloatField::init(),
            'withdraw_disabled_coeff' => FloatField::init(),
            'host' => CharField::init(),
            'exchange_commision' => FloatField::init(),
            'wallet_withdraw_delay' => IntField::init(),
            'wallet_withdraw_enabled' => IntField::init(),
            'wallet_refill_emails' => CharField::init(),
            'wallet_withdraw_email_notif' => IntField::init(),
            'wallet_withdraw_emails' => CharField::init(),
            'pool_percent' => DoubleField::init()->setDefault(0),
            'token_price' => DoubleField::init()->setDefault(0.1),
            'token_sold_amount' => DoubleField::init()->setDefault(0),
            'fiat_exchange_fee_sell' => DoubleField::init()->setDefault(2),
            'fiat_exchange_fee_buy' => DoubleField::init()->setDefault(2),
            'agent_token_percent' => IntField::init(),
            'withdrawal_amount_day_limit' => DoubleField::init()->setDefault(100),
            'fiat_withdrawal_manual_timer' => IntField::init()->setDefault(24),
            'swap_usd_daily_limit' => DoubleField::init()->setDefault(20000),
            'swap_min_fiat_wallet_transaction_in_usd' => DoubleField::init()->setDefault(20),
            'xendit_percent_fee' => DoubleField::init(),
            'xendit_min_fee' => DoubleField::init(),
            'rub_refill_percent_fee' => DoubleField::init(),
            'bitcoinovnet_book_time' => IntField::init()->setDefault(1800),
            'bitcoinovnet_manual_book_time' => IntField::init()->setDefault(1800),
            'bitcoinovnet_max_change_course' => DoubleField::init()->setDefault(3),
            'bitcoinovnet_rate_update' => IntField::init()->setDefault(120),
            'bitcoinovnet_max_transaction_amount' => DoubleField::init()->setDefault(150000),
            'bitcoinovnet_min_transaction_amount' => DoubleField::init()->setDefault(150000),
            'bitcoinovnet_btc_balance' => DoubleField::init()->setDefault(0),
            'bitcoinovnet_agent_max_percent' => DoubleField::init()->setDefault(30),
            'bitcoinovnet_swap_percent' => DoubleField::init()->setDefault(4),
            'bitcoinovnet_swap_manual_percent' => DoubleField::init()->setDefault(4),
            'bitcoinovnet_net_profit_percent' => DoubleField::init()->setDefault(2),
            'bitcoinovnet_withdrawal_min_balance_amount' => DoubleField::init()->setDefault(2000),
            'bitcoinovnet_active' => IntField::init()->setDefault(1)->setLength(1),
        ];
    }

    public function getFiatExchangeFee(bool $is_buy): float {
        return $is_buy ? $this->fiat_exchange_fee_buy : $this->fiat_exchange_fee_sell;
    }

    public function getBitcoinovnetSwapFee(): float {
        return ManualSessionModel::getCurrentSession() instanceof ManualSessionModel ?
            $this->bitcoinovnet_swap_manual_percent :
            $this->bitcoinovnet_swap_percent;
    }

    public function getBitcoinovnetNetProfit(): float {
        return $this->bitcoinovnet_net_profit_percent;
    }

    public function decrBitcoinovnetBtcBalance($amount): bool {
        $ret = Db::sub(static::getTableName(), 'bitcoinovnet_btc_balance', $amount, Where::equal('id', (int) $this->id));
        if ($ret) {
            $this->bitcoinovnet_btc_balance -= $amount;
        }
        return $ret;
    }

    public function getBitcoinovnetBookTime() {
        return ManualSessionModel::getCurrentSession() instanceof ManualSessionModel ?
            $this->bitcoinovnet_manual_book_time :
            $this->bitcoinovnet_book_time;
    }
}
