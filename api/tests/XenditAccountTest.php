<?php


namespace Tests;


use Core\App;
use Core\Services\Merchant\XenditService;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Models\XenditWalletModel;
use PHPUnit\Framework\TestCase;

class XenditAccountTest extends TestCase {
    public function testCreate() {
        $xendit_wallet = XenditService::createVirtualAccount('BNI');
        $this->assertEquals('BNI', $xendit_wallet->bank_code);
        $this->assertNotEquals(null, $xendit_wallet->id);

        return $xendit_wallet;
    }

    /**
     * @depends testCreate
     * @param XenditWalletModel $xendit_wallet
     * @return \Db\Model\Model|XenditWalletModel
     * @throws \Db\Exception\DbAdapterException
     * @throws \Db\Exception\InvalidSelectQueryException
     * @throws \Db\Exception\InvalidWhereOperatorException
     * @throws \Db\Model\Exception\ModelNotFoundException
     * @throws \Db\Model\Exception\TableNameUndefinedException
     */
    public function testSetActive(XenditWalletModel $xendit_wallet) {
        $http = new Client();
        $req = $http->post(KERNEL_CONFIG['base_uri']. '/fiat_wallet/xendit/virtual_account/webhook', [
            RequestOptions::JSON => [
                'external_id' => $xendit_wallet->id,
                'merchant_code' => '0772',
                'name' => 'NARFEX',
                'bank_code' => $xendit_wallet->bank_code,
                'account_number' => '087668909755681',
                'id' => 'afha1259192',
                'status' => 'ACTIVE',
                'is_closed' => false
            ]
        ]);

        $this->assertEquals(200, $req->getStatusCode());
        /**
         * @var XenditWalletModel $xendit_wallet_model
         */
        $xendit_wallet_model = XenditWalletModel::get($xendit_wallet->id);
        $this->assertEquals('active', $xendit_wallet_model->status);

        return $xendit_wallet_model;
    }

    /**
     * @depends testSetActive
     * @param XenditWalletModel $xendit_wallet
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
    public function testAssign(XenditWalletModel $xendit_wallet) {
        $user = Seeds::createUser();
        $xendit_wallet_model = XenditService::assignVirtualAccount($user->id, $xendit_wallet->bank_code);
        $this->assertEquals($user->id, $xendit_wallet_model->user_id);
        $this->assertEquals('active', $xendit_wallet_model->status);
    }
}
