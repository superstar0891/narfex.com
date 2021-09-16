<?php


namespace Tests;


use Engine\Request;
use Modules\ProfileModule;
use PHPUnit\Framework\TestCase;

class AuthorizationTest extends TestCase {
    use ResetDatabase;

    public function testLogout() {
        $user = Seeds::createUser('testtest', 'testtest');

        $web_token = ProfileModule::generateToken($user, Request::WEB_APP_ID);
        $mobile_token = ProfileModule::generateToken($user, Request::MOBILE_APP_ID);

        $logout_response = TestHelper::post('/profile/logout', $web_token);
        $this->assertEquals(200, $logout_response->getStatusCode());

        $dashboard_response = TestHelper::get('/dashboard', $mobile_token);
        $this->assertEquals(200, $dashboard_response->getStatusCode());
    }
}
