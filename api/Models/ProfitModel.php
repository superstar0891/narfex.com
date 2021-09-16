<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DateTimeField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Model;

/**
 * @property int user_id
 * @property int deposit_id
 * @property int wallet_id
 * @property string type
 * @property double amount
 * @property string created_at
 * @property int target_id
 * @property string currency
 * @property float rate
 * @property float agent_percent_profit
 */
class ProfitModel extends Model {

    protected static $table_name = 'profits';

    protected static $fields = [];

    const TYPE_AGENT_PROFIT = 'agent_profit',
        TYPE_INVEST_PROFIT = 'invest_profit',
        TYPE_POOL_PROFIT = 'pool_profit',
        TYPE_REFERRAL_PROFIT = 'referral_profit',
        TYPE_REINVEST_PROFIT = 'reinvest_profit',
        TYPE_RETURN_DEPOSIT = 'return_deposit',
        TYPE_TOKEN_PROFIT = 'token_profit',
        TYPE_SAVING_PROFIT = 'saving_profit',
        TYPE_SAVING_ACCRUAL = 'saving_accrual',
        TYPE_PROMO_CODE_REWARD = 'promo_code_reward',
        TYPE_BITCOINOVNET_PROFIT = 'agent_bitcoinovnet_profit',
        REFERRAL_PROFITS = [
            self::TYPE_TOKEN_PROFIT,
            self::TYPE_AGENT_PROFIT,
            self::TYPE_REFERRAL_PROFIT
        ];

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'deposit_id' => IdField::init()->setNull(true),
            'wallet_id' => IdField::init()->setNull(true),
            'type' => CharField::init()->setLength(32),
            'amount' => DoubleField::init(),
            'created_at' => DateTimeField::init(),
            'target_id' => IdField::init()->setNull(true),
            'currency' => CharField::init()->setLength(10)->setNull(true),
            'rate' => DoubleField::init()->setDefault(0)->setNull(),
            'agent_percent_profit' => DoubleField::init()->setDefault(0)->setNull(),
        ];
    }
}
