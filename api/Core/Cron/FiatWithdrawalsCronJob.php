<?php

namespace Cron;

use Core\App;
use Core\Exceptions\Withdrawal\InsufficientFundsException;
use Core\Services\Redis\RedisAdapter;
use Core\Services\Telegram\SendService;
use Db\Model\ModelSet;
use Db\Transaction;
use Db\Where;
use Models\UserBalanceHistoryModel;
use Models\UserModel;
use Models\UserWithdrawalLimitModel;
use Models\WithdrawalModel;
use Modules\FiatWalletModule;
use Modules\UserWithdrawalLimitModule;

class FiatWithdrawalsCronJob implements CronJobInterface {
    static $balance_empty_error = false;

    public function exec() {
        $start_at = time();

        $settings = settings();
        $day_limit = $settings->withdrawal_amount_day_limit;
        $timer = $settings->fiat_withdrawal_manual_timer * 3600;

        $withdrawals = WithdrawalModel::queryBuilder()
            ->columns([])
            ->where(Where::equal('status', UserBalanceHistoryModel::STATUS_CONFIRMATION))
            ->select();
        $withdrawals = WithdrawalModel::rowsToSet($withdrawals);

        $user_ids = $withdrawals->column('user_id');
        $users = UserModel::select(Where::and()
            ->set('id', Where::OperatorIN, $user_ids)
            ->set('join_date', Where::OperatorGreaterEq, '2020-05-20')
        );

        $limits = UserWithdrawalLimitModel::select(
            Where::and()->set('user_id', Where::OperatorIN, $user_ids)
        );

        [$approve_withdrawals, $manual_withdrawals] = Transaction::wrap(
            function () use ($withdrawals, $limits, $users, $day_limit, $start_at) {
                $limits_map = [];
                foreach ($limits as $limit) {
                    /** @var UserWithdrawalLimitModel $limit */
                    $limits_map[$limit->user_id] = $limit;
                }

                $approve_withdrawals = [];
                $manual_withdrawals = [];

                foreach ($withdrawals as $withdrawal) {
                    if ($this->isTimeOver($start_at)) {
                        break;
                    }
                    /** @var WithdrawalModel $withdrawal */
                    $amount_in_usd = FiatWalletModule::getAmountInAnotherCurrency(
                        $withdrawal->currency,
                        FiatWalletModule::CURRENCY_USD,
                        $withdrawal->amount
                    );

                    if ($amount_in_usd > $day_limit || $users->getItem($withdrawal->user_id) === null) {
                        $manual_withdrawals[] = $withdrawal;
                        continue;
                    }

                    if (isset($limits_map[$withdrawal->user_id])) {
                        $limit = UserWithdrawalLimitModule::updateLimit($limits_map[$withdrawal->user_id]);
                        /** @var UserWithdrawalLimitModel $limit */
                        $limits_map[$withdrawal->user_id] = $limit;

                        if (($limit->amount + $amount_in_usd) > $day_limit) {
                            $manual_withdrawals[] = $withdrawal;
                            continue;
                        }
                        [$withdrawal, $limit] = $this->approve($withdrawal, $amount_in_usd, $limit);
                        $limits_map[$withdrawal->user_id] = $limit;
                        $approve_withdrawals[] = $withdrawal;
                    } else {
                        [$withdrawal, $limit] = $limit = $this->approve($withdrawal, $amount_in_usd);
                        $limits_map[$withdrawal->user_id] = $limit;
                        $approve_withdrawals[] = $withdrawal;
                    }
                }

                return [$approve_withdrawals, $manual_withdrawals];
            });

        if (self::$balance_empty_error && App::isProduction()) {
            $telegram_service = new SendService();
            $telegram_service->sendMessage('#ERROR Xendit: Insufficient Funds');
        }

        foreach ($manual_withdrawals as $key => $withdrawal) {
            /** @var WithdrawalModel $withdrawal */
            if (!RedisAdapter::shared()->get("manual_fiat_withdrawal_$withdrawal->id")) {
                RedisAdapter::shared()->set("manual_fiat_withdrawal_$withdrawal->id", 1, $timer);
            } else {
                unset($manual_withdrawals[$key]);
            }
        }

        $user_ids = array_unique(
            array_merge(
                array_map(function ($w) {return $w->user_id; }, $manual_withdrawals),
                array_map(function ($w) {return $w->user_id; }, $approve_withdrawals)
            )
        );
        $users = UserModel::select(Where::in('id', $user_ids));
        $this->sendTelegramMessages($approve_withdrawals, $manual_withdrawals, $users);
    }

    private function approve(WithdrawalModel $withdrawal, float $amount_in_usd, UserWithdrawalLimitModel $limit = null) {
        if (is_null($limit)) {
            $limit = UserWithdrawalLimitModule::create($withdrawal->user_id, $amount_in_usd);
        } else {
            $limit->incrLimit($amount_in_usd);
        }

        try {
            FiatWalletModule::approveWithdrawal($withdrawal);
        } catch (\Exception $e) {
            if ($e instanceof InsufficientFundsException) {
                self::$balance_empty_error = true;
            }
            $limit->decrLimit($amount_in_usd);
        }

        return [$withdrawal, $limit];
    }

    private function sendTelegramMessages(array $approve_withdrawals, array $manual_withdrawals, ModelSet $users): void {
        $this->send($approve_withdrawals, $users);
        $this->send($manual_withdrawals, $users, false);
    }

    private function send($withdrawals, $users, $auto_approve = true) {
        if (empty($withdrawals)) {
            return;
        }

        $telegram_service = new SendService();
        $withdrawal_part = [];
        $counter = 0;
        $all_count = count($withdrawals);
        foreach ($withdrawals as $key => $withdrawal) {
            if ($counter === 9) {
                if (App::isProduction()) {
                    if ($auto_approve) {
                        $telegram_service->sendMessage(FiatWalletModule::telegramWithdrawalsApprove($withdrawal_part, $users));
                    } else {
                        $telegram_service->sendMessage(FiatWalletModule::telegramWithdrawalsManual($withdrawal_part, $users));
                    }
                }
                $withdrawal_part = [
                    0 => $withdrawal
                ];
                $counter = 0;
                continue;
            }

            $withdrawal_part[] = $withdrawal;
            $counter++;

            if ($key + 1 === $all_count) {
                if (App::isProduction()) {
                    if ($auto_approve) {
                        $telegram_service->sendMessage(FiatWalletModule::telegramWithdrawalsApprove($withdrawal_part, $users));
                    } else {
                        $telegram_service->sendMessage(FiatWalletModule::telegramWithdrawalsManual($withdrawal_part, $users));
                    }
                }
            }
        }
    }

    private function isTimeOver(int $start_time) {
        if (time() - $start_time >= 270) {
            return true;
        }

        return false;
    }
}
