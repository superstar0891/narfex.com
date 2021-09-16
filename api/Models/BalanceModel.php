<?php

namespace Models;

use Db\Db;
use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Model;
use Db\Transaction;
use Db\Where;
use Models\Traits\ConvertToUsd;

/**
 * @property int id
 * @property int user_id
 * @property double amount
 * @property double lock_amount
 * @property string currency
 * @property string category
 * @property int has_history
 */
class BalanceModel extends Model {
    use ConvertToUsd;

    const FAKE_WALLET = 1;
    const FAKE_INVOICE = 2;
    const FAKE_WITHDRAW = 3;
    const FAKE_REFILL = 4;

    const CATEGORY_PARTNERS = 'partners';
    const CATEGORY_EXCHANGE = 'exchange';
    const CATEGORY_FIAT = 'fiat';
    const CATEGORY_WALLET = 'wallet';
    const CATEGORY_BITCOINOVNET_AGENT = 'bitcoinovnet_agent';

    protected static $table_name = 'balances';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'amount' => DoubleField::init()->setDefault(0),
            'lock_amount' => DoubleField::init()->setDefault(0),
            'currency' => CharField::init()->setLength(10),
            'category' => CharField::init()->setLength(60),
            'has_history' => IntField::init()->setLength(1)->setDefault(0)
        ];
    }

    public function incrAmount($amount): bool {
        return Transaction::wrap(function () use ($amount) {
            $ret = Db::add(static::getTableName(), 'amount', $amount, Where::equal('id', (int) $this->id));
            if ($ret) {
                $this->amount += $amount;
            }

            if (!$this->has_history && $this->amount != 0) {
                $this->has_history = 1;
                $this->save();
            }

            return $ret;
        });
    }

    public function decrAmount($amount): bool {
        $ret = Db::sub(static::getTableName(), 'amount', $amount, Where::equal('id', (int) $this->id));
        if ($ret) {
            $this->amount -= $amount;
        }
        return $ret;
    }

    public function decrLockedAmount($amount): bool {
        $ret = Db::sub(static::getTableName(), 'lock_amount', $amount, Where::equal('id', (int) $this->id));
        if ($ret) {
            $this->lock_amount -= $amount;
        }
        return $ret;
    }

    public function incrLockedAmount($amount): bool {
        $ret = Db::add(static::getTableName(), 'lock_amount', $amount, Where::equal('id', (int) $this->id));
        if ($ret) {
            $this->lock_amount += $amount;
        }
        return $ret;
    }

    public function alignAmount(string $quote): float {
        return $this->amount * WalletModel::getRate($quote, $this->currency);
    }

    public function checkAmount($amount): bool {
        $balance_row = Db::get(static::getTableName(),null, Where::equal('id', $this->id), true);
        if ($balance_row['amount'] >= $amount) {
            return true;
        }

        return false;
    }
}
