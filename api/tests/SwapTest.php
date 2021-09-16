<?php

namespace Tests;

use Core\Exceptions\Exchange\AmountTooSmallException;
use Core\Exceptions\Exchange\DailyTransactionsLimitException;
use Core\Exceptions\Exchange\InsufficientFundsException;
use Core\Services\Exchange\Exchange;
use Models\BalanceModel;
use Modules\BalanceModule;
use Modules\FiatWalletModule;
use Modules\WalletModule;
use PHPUnit\Framework\TestCase;

class SwapTest extends TestCase {
    use ResetDatabase;

    public function testSwap() {
        $amount = 1000;
        $start_balance_amount = 100000;
        $start_wallet_amount = 100;
        $user = Seeds::createUserAndBalanceAndWallet($start_balance_amount, $start_wallet_amount);
        $swap_service = new Exchange(CURRENCY_USD, CURRENCY_BTC, $amount, 'fiat', $user);
        $swap = $swap_service->execute();

        $rate = FiatWalletModule::getRate(
            CURRENCY_USD,
            CURRENCY_BTC,
            true,
            true,
            FiatWalletModule::FEE_DIRECTION_UP
        );

        $fiat_amount = $amount;
        $amount = $amount / $rate;
        $fee = settings()->getFiatExchangeFee(true);
        $fee = $fiat_amount * $fee / 100;

        $reloaded_balance = BalanceModule::getBalanceOrCreate($user->id, CURRENCY_USD, BalanceModel::CATEGORY_FIAT);
        $reloaded_wallet = WalletModule::getWallet($user->id, CURRENCY_BTC);

        $this->assertEquals($start_balance_amount - $fiat_amount, $reloaded_balance->amount);
        $this->assertEquals(round($start_wallet_amount + $amount, 8), $reloaded_wallet->amount);
        $this->assertEquals($fee, $swap->getHistoryItem()->fee);
        $this->assertEquals($fiat_amount, $swap->getHistoryItem()->from_amount);
        $this->assertEquals($amount, $swap->getHistoryItem()->to_amount);
    }

    public function testTooManySwapsInDay() {
        $user = Seeds::createUserAndBalanceAndWallet(10000000, 100);
        $amount = settings()->swap_usd_daily_limit - 100;
        $swap_service = new Exchange(CURRENCY_USD, CURRENCY_BTC, $amount, 'fiat', $user);
        $swap_service->execute();

        try {
            $swap_service->execute();
            $this->assertTrue(false);
        } catch (DailyTransactionsLimitException $e) {
            $this->assertTrue(true);
        }
    }

    public function testAmountTooSmall() {
        $user = Seeds::createUserAndBalanceAndWallet(1000, 100);
        $swap_service = new Exchange(CURRENCY_USD, CURRENCY_BTC, settings()->swap_min_fiat_wallet_transaction_in_usd - 5, 'fiat', $user);
        try {
            $swap_service->execute();
            $this->assertTrue(false);
        } catch (AmountTooSmallException $e) {
            $this->assertTrue(true);
        }
    }

    public function testInsufficientFunds() {
        $user = Seeds::createUserAndBalanceAndWallet(1000, 100);
        $swap_service = new Exchange(CURRENCY_USD, CURRENCY_BTC, 1200, 'fiat', $user);
        try {
            $swap_service->execute();
            $this->assertTrue(false);
        } catch (InsufficientFundsException $e) {
            $this->assertTrue(true);
        }
    }
}
