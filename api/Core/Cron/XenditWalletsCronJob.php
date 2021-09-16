<?php


namespace Cron;


use Core\Services\Merchant\XenditService;
use Db\Where;
use Models\XenditWalletModel;

class XenditWalletsCronJob implements CronJobInterface {
    public function exec() {
        $available_accounts = XenditWalletModel::queryBuilder()
            ->where(
                Where::and()
                    ->set('status', Where::OperatorEq, XenditWalletModel::STATUS_ACTIVE)
                    ->set('user_id', Where::OperatorIs, null)
            )
            ->columns(['COUNT(id)' => 'cnt'], true)
            ->get();

        $needed_available_accounts = 200;
        $count = $available_accounts['cnt'] ?? 0;
        echo $count;
        if ($count < $needed_available_accounts) {
            $banks = XenditService::getBanks();
            $need_to_register = ($needed_available_accounts - $count) / count($banks);
            $need_to_register = $need_to_register < 60 ? $need_to_register : 60;
            for ($i = 0; $i < $need_to_register; $i++) {
                foreach ($banks as $bank) {
                    XenditService::createVirtualAccount($bank['code']);
                }
            }
        }
    }
}
