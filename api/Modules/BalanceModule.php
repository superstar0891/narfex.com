<?php

namespace Modules;

use Core\Services\Merchant\XenditException;
use Core\Services\Merchant\XenditService;
use Db\Model\ModelSet;
use Db\Where;
use Models\BalanceHistoryModel;
use Models\BalanceModel;
use Models\UserModel;
use Models\XenditWalletModel;

class BalanceModule {
    public static function getBalance(int $balance_id, int $user_id): ?BalanceModel {
        /** @var BalanceModel $balance */
        $balance = BalanceModel::first(
            Where::and()
                ->set(Where::equal('id', $balance_id))
                ->set(Where::equal('user_id', $user_id))
        );

        return $balance;
    }

    public static function getAvailableFiatBalances(UserModel $user) {
        $needed_currencies = [CURRENCY_IDR, CURRENCY_USD, CURRENCY_RUB];
        $balances = BalanceModel::select(
            Where::and()
                ->set(Where::equal('user_id', $user->id))
                ->set(Where::equal('category', BalanceModel::CATEGORY_FIAT))
                ->set(Where::in('currency', $needed_currencies))
        );

        $existing_currencies = [];
        foreach ($balances as $balance) {
            /** @var BalanceModel $balance */
            $existing_currencies[] = $balance->currency;
        }

        foreach ($needed_currencies as $needed_currency) {
            if (!in_array($needed_currency, $existing_currencies)) {
                $balances->push(BalanceModule::createBalance($user->id, $needed_currency, BalanceModel::CATEGORY_FIAT));
            }
        }

        return $balances;
    }

    public static function getBalances($user_id, $category): ModelSet {
        $balances = BalanceModel::select(Where::and()
            ->set('user_id', Where::OperatorEq, $user_id)
            ->set('category', Where::OperatorEq, $category)
        );

        if ($category === BalanceModel::CATEGORY_EXCHANGE) {
            $primary_currencies = ['btc', 'eth', 'ltc', 'usdt'];
        } else if ($category === BalanceModel::CATEGORY_FIAT) {
            $primary_currencies = KERNEL_CONFIG['fiat']['currencies'];
        } else {
            $primary_currencies = [];
        }

        if (count($primary_currencies)) {
            $balances_map = [];
            /* @var \Models\BalanceModel $balance */
            foreach ($balances as $balance) {
                $balances_map[$balance->currency] = true;
            }

            foreach ($primary_currencies as $currency) {
                if (isset($balances_map[$currency])) {
                    continue;
                }

                $balances->push(BalanceModule::createBalance($user_id, $currency, $category));
            }
        }

        return $balances;
    }

    public static function getHistory(int $user_id, $category): ModelSet {

        if (is_string($category)) {
            $category = [$category];
        }
        $builder = BalanceHistoryModel::queryBuilder()
            ->columns([])
            ->where(Where::and()
                ->set('user_id', Where::OperatorEq, $user_id)
                ->set(Where::or()
                    ->set('from_balance_category', Where::OperatorIN, $category)
                    ->set('to_balance_category', Where::OperatorIN, $category)
                    ->set('type', Where::OperatorIN, $category)
                ))
            ->orderBy(['id' => 'DESC'])
            ->limit(100)
            ->select();
        return BalanceHistoryModel::rowsToSet($builder);
    }

    public static function getFakeBalance($id, $currency): BalanceModel {
        $data = [
            BalanceModel::FAKE_WALLET => [
                'category' => 'wallet',
            ],
            BalanceModel::FAKE_INVOICE => [
                'category' => 'invoice',
            ],
            BalanceModel::FAKE_REFILL => [
                'category' => 'refill',
            ],
            BalanceModel::FAKE_WITHDRAW => [
                'category' => 'withdraw',
            ],
        ];

        if (!isset($data[$id])) {
            throw new \Exception();
        }

        $info = $data[$id];

        $balance = new BalanceModel();
        $balance->setId($id);
        $balance->user_id = 0;
        $balance->currency = $currency;
        $balance->category = $info['category'];
        $balance->amount = 0;
        return $balance;
    }

    public static function addHistory(
        $type,
        $amount,
        $user_id,
        BalanceModel $from_balance,
        BalanceModel $to_balance,
        array $extra = [],
        string $status = BalanceHistoryModel::STATUS_COMPLETED): BalanceHistoryModel {
            $history = new BalanceHistoryModel();
            $history->amount = $amount;
            $history->from_balance_id = $from_balance->id;
            $history->from_balance_category = $from_balance->category;
            $history->to_balance_id = $to_balance->id;
            $history->to_balance_category = $to_balance->category;
            $history->type = $type;
            $history->status = $status;
            $history->user_id = $user_id;
            $history->extra = json_encode($extra);
            $history->save();
            return $history;
    }

    public static function getBalanceOrCreate(int $user_id, string $currency, string $category): BalanceModel {
        $balance = BalanceModel::select(Where::and()
            ->set('user_id', Where::OperatorEq, $user_id)
            ->set('currency', Where::OperatorEq, $currency)
            ->set('category', Where::OperatorEq, $category));

        if ($balance->isEmpty()) {
            $balance = self::createBalance($user_id, $currency, $category);
        } else {
            $balance = $balance->first();
        }

        return $balance;
    }

    public static function createBalance(int $user_id, string $currency, string $category): BalanceModel {
        $balance = new BalanceModel();
        $balance->user_id = $user_id;
        $balance->currency = $currency;
        $balance->category = $category;
        $balance->amount = 0;
        $balance->save();
        return $balance;
    }

    public static function getXenditVirtualAccount(UserModel $user, string $bank_code): ?XenditWalletModel {
        $accounts = XenditWalletModel::queryBuilder()->where(
            Where::and()
                ->set('user_id', Where::OperatorEq, $user->id)
                ->set('bank_code', Where::OperatorEq, $bank_code)
        )->select();
        $accounts = XenditWalletModel::rowsToSet($accounts);
        if ($accounts->isEmpty()) {
            try {
                return XenditService::createVirtualAccount($user, $bank_code);
            } catch (XenditException $e) {
                return null;
            }
        } else {
            /**
             * @var XenditWalletModel $account
             */
            $account = $accounts->first();
            return $account;
        }
    }

    public static function getXenditVirtualAccountsByUserId(int $user_id): ModelSet {
        return XenditWalletModel::select(Where::equal('user_id', $user_id));
    }

    public static function getTransactionsByUserAndBalanceIds(int $user_id, ?int $balance_id = null, int $start_from = 0, ?int $count = 20, $order_by = 'DESC') {
        $where = Where::and()
            ->set(Where::equal('user_id', $user_id));

        if ($balance_id) {
            $where->set(
                Where::or()
                    ->set(Where::equal('from_balance_id', $balance_id))
                    ->set(Where::equal('to_balance_id', $balance_id))
            );
        }

        $history_items_paginator = BalanceHistoryModel::queryBuilder()
            ->where($where)
            ->orderBy(['id' => 'desc'])
            ->paginate($start_from, $count);

        return $history_items_paginator;
    }
}
