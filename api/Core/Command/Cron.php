<?php

namespace Core\Command;

use Cron\AddressPoolCronJob;
use Cron\AvailableCardAmountJob;
use Cron\BankCardsCronJob;
use Cron\DailySavingCronJob;
use Cron\EthTransferCronJob;
use Cron\FiatWithdrawalsCronJob;
use Cron\GettingRateCronJob;
use Cron\HedgingCronJob;
use Cron\MerchantCardCronJob;
use Cron\ProfitCronJob;
use Cron\QueueCronJob;
use Cron\SavingCronJob;
use Cron\UpdatePartnerRating;
use Cron\XenditWalletsCronJob;

class Cron implements CommandInterface {
    private $job;

    private static $available_jobs = [
        'profit' => ProfitCronJob::class,
        'address_pool' => AddressPoolCronJob::class,
        'eth_transfer' => EthTransferCronJob::class,
        'hedging' => HedgingCronJob::class,
        'xendit_wallets_check' => XenditWalletsCronJob::class,
        'fiat_withdrawals' => FiatWithdrawalsCronJob::class,
        'getting_rates' => GettingRateCronJob::class,
        'bank_cards' => BankCardsCronJob::class,
        'saving_daily' => DailySavingCronJob::class,
        'saving' => SavingCronJob::class,
        'merchant_cards' => MerchantCardCronJob::class,
        'update_partner_rating' => UpdatePartnerRating::class,
        'queue_cron_job' => QueueCronJob::class,
        'update_available_card_amount' => AvailableCardAmountJob::class,
    ];

    function __construct(string $job) {
        $this->job = $job;
    }

    public function exec() {
        if (!isset(self::$available_jobs[$this->job])) {
            die("Unknown cron job \n");
        }

        (new self::$available_jobs[$this->job])->exec();
    }
}
