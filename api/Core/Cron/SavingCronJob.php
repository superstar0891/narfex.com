<?php

namespace Cron;


use Core\Services\BalanceHistory\BalanceHistorySaver;
use Db\Transaction;
use Db\Where;
use Models\ProfitModel;
use Models\UserBalanceHistoryModel;
use Modules\NotificationsModule;
use Modules\WalletModule;

class SavingCronJob implements CronJobInterface {
    public function exec() {

        if (date('t') != (int) date('d')) {
            return;
        }

        $start_of_month = mktime(0, 0, 0, date('n'), 1);

        $profit = ProfitModel::first(Where::and()
            ->set(Where::equal('type', ProfitModel::TYPE_SAVING_ACCRUAL))
            ->set('created_at_timestamp', Where::OperatorGreaterEq, $start_of_month)
        );

        if ($profit) {
            return;
        }

        $profits = ProfitModel::queryBuilder()
            ->columns(['user_id', 'SUM(amount)' => 'total_amount', 'currency'], true)
            ->where(Where::and()
                ->set(Where::equal('type', ProfitModel::TYPE_SAVING_PROFIT))
                ->set('created_at_timestamp', Where::OperatorGreaterEq, $start_of_month)
            )
            ->groupBy(['user_id', 'currency'])
            ->select();

        Transaction::wrap(function () use ($profits) {
            foreach ($profits as $row) {

                $wallet = WalletModule::getWallet($row['user_id'], $row['currency']);

                $profit = new ProfitModel();
                $profit->amount = $row['total_amount'];
                $profit->type = ProfitModel::TYPE_SAVING_ACCRUAL;
                $profit->user_id = $row['user_id'];
                $profit->currency = $row['currency'];
                $profit->wallet_id = $wallet->id;
                $profit->created_at = date('Y-m-d H:i:s');
                $profit->save();

                BalanceHistorySaver::make()
                    ->setToRaw(
                        UserBalanceHistoryModel::TYPE_WALLET,
                        $wallet->id,
                        $wallet->user_id,
                        $row['currency']
                    )
                    ->setToAmount($row['total_amount'])
                    ->setOperation(UserBalanceHistoryModel::OPERATION_SAVING_ACCRUAL)
                    ->setObjectId($profit->id)
                    ->save();

                NotificationsModule::sendSavingAccrualNotification($profit);
            }
        });
    }
}
