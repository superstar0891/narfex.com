<?php


namespace Tests;


use Db\Db;
use Db\Exception\InvalidSelectQueryException;
use Db\Where;

trait ResetDatabase {
    public function setUp(): void {
        $this->truncate();

        Db::query("
        INSERT INTO `site_settings` (`id`, `cb_commission`, `site_name`, `host`, `default_language`, `currencies`, `exchange_commision`, `liquidity_amount`, `liquidity_limit`, `liquidity_count`, `liquidity_spread`, `commerce_expiration`, `commerce_commision`, `wallet_withdraw_daily_max`, `wallet_withdraw_enabled`, `wallet_withdraw_daily_freq`, `wallet_withdraw_hour_freq`, `wallet_withdraw_email_notif`, `wallet_withdraw_emails`, `pool_percent`, `wallet_withdraw_delay`, `wallet_refill_emails`, `deposit_fast_withdraw_threshold`, `deposit_profit_drop`, `deposit_withdraw_min`, `dynamic_minimal_coeff`, `dynamic_point_x`, `dynamic_point_y`, `dynamic_start_percent`, `withdraw_disabled_coeff`, `pool_amount`, `pool_date`, `created_at_timestamp`, `updated_at_timestamp`, `deleted_at`, `agent_percent`, `representative_percent`, `token_price`, `token_sold_amount`, `agent_token_percent`, `fiat_exchange_fee_sell`, `fiat_exchange_fee_buy`, `withdrawal_amount_day_limit`, `fiat_withdrawal_manual_timer`, `swap_usd_daily_limit`, `swap_min_fiat_wallet_transaction_in_usd`) VALUES
(1, 0, 'BitcoinBot', 'https://cabinet.bitcoinbot.pro', 'en', 'btc,bitcoin,0,10,10,10\neth,ethereum,0,320,320,320\nltc,litecoin,0,1010,1010,1010', 0.2, 0.015, 43200, 20, 0.03, 30, 0.2, 5, 1, 5, 1, 1, 'nikitaradi@gmail.com,yusufgbot@gmail.com', 0, 100, 'yusufgbot@gmail.com,nikitaradi@gmail.com', 10, 10, 5, 0.01, 0.05, 0.8, 0.9, 1.056, 0, 0, NULL, 1592987973, NULL, 10, 5, 0.1, 10000, 10, 2, 2, 100, 24, 100000, 20);
");
    }

    protected function truncate() {
        $res = Db::query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='narfex' AND TABLE_NAME != 'phinxlog'");
        while ($row = $res->fetch_assoc()) {
            if ($row['TABLE_NAME'] !== 'phinxlog') {
                Db::query("TRUNCATE TABLE " . $row['TABLE_NAME']);
            }
        }
        $res->free_result();
    }
}
