<?php


namespace Tests;


use Engine\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Modules\ProfileModule;
use Modules\WalletModule;
use PHPUnit\Framework\TestCase;

class GoogleAuthTest extends TestCase {
    public function testGoogleAuth() {
        $user = Seeds::createUser();
        ProfileModule::gaInit($user);
        $user->is_2fa_enabled = 1;
        $user->save();
        $app_token = ProfileModule::generateToken($user, Request::WEB_APP_ID);

        $http = new Client();
        try {
            $req = $http->put(KERNEL_CONFIG['base_uri']. '/wallet/transaction_send', [
                RequestOptions::HEADERS => [
                    'X-token' => $app_token
                ],
                RequestOptions::JSON => [
                    'wallet_id' => 1,
                    'address' => 'ashskjosjdhfasdkjfnqweoriwqer',
                    'amount' => 1,
                ]
            ]);
            $this->assertTrue(true);
        } catch (ClientException $e) {
            $this->assertEquals(400, $e->getResponse()->getStatusCode());
            $res = json_decode($e->getResponse()->getBody()->getContents());
            $this->assertEquals('bad_param', $res->code);
            $this->assertEquals('ga_code param is required', $res->message);
        }
    }
}
