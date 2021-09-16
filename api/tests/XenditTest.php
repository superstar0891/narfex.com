<?php


namespace Tests;

use Core\Services\Refill\RefillService;
use Core\Services\Withdrawal\WithdrawalService;
use Core\Services\Merchant\XenditService;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Models\BalanceModel;
use Models\RefillModel;
use Models\UserBalanceHistoryModel;
use Models\WithdrawalModel;
use Models\XenditWalletModel;
use Modules\BalanceModule;
use Modules\FeeModule;
use Modules\FiatWalletModule;
use PHPUnit\Framework\TestCase;

class XenditTest extends TestCase {
    use ResetDatabase;

    public function testRefill() {
        $user = Seeds::createUser();
        $balance = BalanceModule::getBalanceOrCreate($user->id, CURRENCY_IDR, BalanceModel::CATEGORY_FIAT);

        $refill_service = new RefillService();
        $amount = 100000;
        $bank_code = 'BNI';
        $provider = 'xendit';
        $external_id = 'adhasjdfasdfsdaf';
        $refill_service->setCurrency(CURRENCY_IDR)
            ->setAmount($amount)
            ->setBalance($balance)
            ->setBankCode($bank_code)
            ->setProvider($provider)
            ->setExternalId($external_id)
            ->setUser($user)
            ->execute();

        $fee = FeeModule::getFee($amount, CURRENCY_IDR);
        $reloaded_balance = BalanceModule::getBalanceOrCreate($user->id, CURRENCY_IDR, BalanceModel::CATEGORY_FIAT);

        /** @var RefillModel $refill */
        $refill = RefillModel::first();
        $this->assertEquals($bank_code, $refill->bank_code);
        $this->assertEquals($provider, $refill->provider);
        $this->assertEquals($amount, $refill->amount + $refill->fee);
        $this->assertEquals($fee, $refill->fee);
        $this->assertEquals($amount - $fee, $reloaded_balance->amount);
    }

    /**
     * @depends testRefill
     * @throws \Core\Services\Xendit\XenditException
     * @throws \Db\Exception\DbAdapterException
     * @throws \Db\Exception\InvalidInsertQueryException
     * @throws \Db\Exception\InvalidSelectQueryException
     * @throws \Db\Exception\InvalidUpdateQueryException
     * @throws \Db\Exception\InvalidWhereOperatorException
     * @throws \Db\Model\Exception\ModelNotFoundException
     * @throws \Db\Model\Exception\ModelUndefinedFieldsException
     * @throws \Db\Model\Exception\TableNameUndefinedException
     * @throws \Db\Model\Exception\UndefinedValueException
     */
    public function testRefillByHttp() {
        $user = Seeds::createUser();
        $xendit_wallet = XenditService::createVirtualAccount('BNI');
        $xendit_wallet->status = XenditWalletModel::STATUS_ACTIVE;
        $xendit_wallet->save();
        $xendit_wallet = XenditService::assignVirtualAccount($user->id, 'BNI');
        $http = new Client();
        $amount = 100000;
        $fee = FeeModule::getFee($amount, CURRENCY_IDR);
        $req = $http->post(KERNEL_CONFIG['base_uri']. '/fiat_wallet/xendit/refill/webhook', [
            RequestOptions::JSON => [
                'payment_id' => rand(000000,9999999),
                'external_id' => $xendit_wallet->id,
                'owner_id' => 12345,
                'amount' => $amount,
                'bank_code' => $xendit_wallet->bank_code,
                'account_number' => $xendit_wallet->account_number,
                'id' => rand(000000,9999999),
                'transaction_timestamp' => time()
            ]
        ]);

        $this->assertEquals(200, $req->getStatusCode());

        $balance = BalanceModule::getBalanceOrCreate($user->id, CURRENCY_IDR, BalanceModel::CATEGORY_FIAT);
        $this->assertEquals($amount - $fee, $balance->amount);
    }

    /**
     * @return WithdrawalModel
     * @throws \Core\Exceptions\Wallet\Withdrawal\WithdrawalMinAmountException
     * @throws \Core\Exceptions\Withdrawal\BalanceNotFoundException
     * @throws \Core\Exceptions\Withdrawal\InsufficientFundsException
     */
    public function testWithdrawal() {
        $account_holder_name = 'Ivan Ivanov';
        $account_number = 125125681;
        $amount = 100000;

        $user = Seeds::createUser();
        $balance = BalanceModule::getBalanceOrCreate($user->id, CURRENCY_IDR, BalanceModel::CATEGORY_FIAT);
        $balance->incrAmount(110000);

        $withdrawal_service = new WithdrawalService();
        $withdrawal_service
            ->setUser($user)
            ->setAccountHolderName($account_holder_name)
            ->setAccountNumber($account_number)
            ->setCurrency(CURRENCY_IDR)
            ->setBankCode('BNI')
            ->setProvider('xendit')
            ->setAmount($amount)
            ->setEmail('ivanivanov@ivan.com')
            ->execute();

        /** @var WithdrawalModel $withdrawal */
        $withdrawal = WithdrawalModel::first();
        $reloaded_balance = BalanceModule::getBalanceOrCreate($user->id, CURRENCY_IDR, BalanceModel::CATEGORY_FIAT);

        $this->assertEquals(0, $reloaded_balance->amount);
        $this->assertEquals($account_number, $withdrawal->account_number);
        $this->assertEquals($account_holder_name, $withdrawal->account_holder_name);
        $this->assertEquals(CURRENCY_IDR, $withdrawal->currency);
        $this->assertEquals($amount, $withdrawal->amount);

        return $withdrawal;
    }

    /**
     * @depends testWithdrawal
     * @param WithdrawalModel $withdrawal
     * @throws \Db\Exception\DbAdapterException
     * @throws \Db\Exception\InvalidSelectQueryException
     * @throws \Db\Exception\InvalidWhereOperatorException
     * @throws \Db\Model\Exception\ModelNotFoundException
     * @throws \Db\Model\Exception\TableNameUndefinedException
     * @throws \Exception
     */
    public function testWithdrawalWebhook(WithdrawalModel $withdrawal) {
        $account_holder_name = 'Ivan Ivanov';
        $account_number = 125125681;
        $amount = 100000;

        $user = Seeds::createUser();
        $balance = BalanceModule::getBalanceOrCreate($user->id, CURRENCY_IDR, BalanceModel::CATEGORY_FIAT);
        $balance->incrAmount(110000);

        $withdrawal_service = new WithdrawalService();
        $withdrawal = $withdrawal_service
            ->setUser($user)
            ->setAccountHolderName($account_holder_name)
            ->setAccountNumber($account_number)
            ->setCurrency(CURRENCY_IDR)
            ->setBankCode('BNI')
            ->setProvider('xendit')
            ->setAmount($amount)
            ->setEmail('ivanivanov@ivan.com')
            ->execute();

        FiatWalletModule::approveWithdrawal($withdrawal, $user);

        $http = new Client();
        $req = $http->post(KERNEL_CONFIG['base_uri']. '/fiat_wallet/xendit/disbursements/webhook', [
            RequestOptions::JSON => [
                'external_id' => $withdrawal->id,
                'amount' => $withdrawal->amount,
                'bank_code' => $withdrawal->bank_code,
                'status' => UserBalanceHistoryModel::STATUSES_MAP[UserBalanceHistoryModel::STATUS_COMPLETED],
                'id' => rand(0000000, 9999999)
            ]
        ]);

        $this->assertEquals(200, $req->getStatusCode());
        /** @var WithdrawalModel $reloaded_withdrawal */
        $reloaded_withdrawal = WithdrawalModel::get($withdrawal->id);
        $balance = BalanceModule::getBalanceOrCreate($withdrawal->user_id, CURRENCY_IDR, BalanceModel::CATEGORY_FIAT);
        $this->assertEquals(0, $balance->amount, 'Amount is incorrect');
        $this->assertEquals(0, $balance->lock_amount, 'Lock amount is incorrect');
        $this->assertEquals(UserBalanceHistoryModel::STATUS_COMPLETED, $reloaded_withdrawal->status);
    }
}
