<?php

namespace Core\Services\Sms;

use Core\Services\Curl\CurlAdapter;
use Core\Services\Redis\RedisAdapter;

class Sms {
    private $twl_base_url = 'https://api.authy.com/protected/json/phones/verification';
    private $cache_smskey_tpl = 'bb_sendsms_{id}';
    private $twl_auth_token = 'yBcLwNkHzr038RsvMUeKWa3QfG5Eh6NG';

    private function checkTrigger($trigger) {
        $redis = RedisAdapter::shared();

        // check trigger
        if ($trigger > 0) {
            // user, act once per 30 sec
            $user_id = $trigger;
            $key = str_replace('{id}', $user_id, $this->cache_smskey_tpl);
            $now = time();

            if ($redis->exists($key)) {
                return false;
            } else {
                $redis->setEx($key, 30, $now);
                return true;
            }
        } elseif ($trigger == 0) {
            // superuser, act every time
            return true;
        }

        return false;
    }

    public function sendCode($trigger, $phone_code, $phone_number) {
        if ($this->checkTrigger($trigger)) {
            if ($phone_code && $phone_number) {
                $curl = new CurlAdapter();
                $response = $curl->fetchPost($this->twl_base_url . '/start', [
                    'via' => 'sms',
                    'country_code' => $phone_code,
                    'phone_number' => $phone_number,
                    'code_length' => 4,
                ], [
                    'X-Authy-API-Key: ' . $this->twl_auth_token,
                ]);

                return (bool)json_decode($response, true)['success'];
            }
        }

        return false;
    }

    public function checkCode($phone_code, $phone_number, $code): bool {
        if ($phone_code && $phone_number && $code) {
            $curl = new CurlAdapter();
            $response = $curl->fetchGet($this->twl_base_url . '/check', [
                'country_code' => $phone_code,
                'phone_number' => $phone_number,
                'verification_code' => $code,
            ], [
                'X-Authy-API-Key: ' . $this->twl_auth_token,
            ]);

            return (bool)json_decode($response, true)['success'];
        }

        return false;
    }
}
