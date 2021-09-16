<?php


namespace Tests;


use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class TestHelper {
    private static $http = null;

    public static function getHttpClient() {
        if (!self::$http) {
            self::$http = new Client();
        }

        return self::$http;
    }

    /**
     * @param string $route
     * @param array $body
     * @param string|null $auth_token
     * @param array $headers
     * @return \Psr\Http\Message\ResponseInterface
     */
    public static function post(string $route, ?string $auth_token = null, array $body = [], array $headers = []) {
        if ($auth_token) {
            $headers['X-token'] = $auth_token;
        }

        return self::getHttpClient()->post(KERNEL_CONFIG['base_uri'] . $route, [
            RequestOptions::HEADERS => $headers,
            RequestOptions::JSON => $body
        ]);
    }

    public static function get(string $route, ?string $auth_token = null, array $body = [], array $headers = []) {
        if ($auth_token) {
            $headers['X-token'] = $auth_token;
        }

        return self::getHttpClient()->get(KERNEL_CONFIG['base_uri'] . $route, [
            RequestOptions::HEADERS => $headers,
            RequestOptions::QUERY => $body
        ]);
    }
}
