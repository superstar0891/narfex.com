<?php

namespace Core\Services\Qiwi;

use \Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

class QiwiClient {

    private $base_url;
    private $http_client;

    const METHOD_GET = 'get',
        METHOD_POST = 'post',
        METHOD_PUT = 'put',
        METHOD_DELETE = 'delete';

    /**
     * @throws Exception
     */
    public function __construct() {
        $this->base_url = 'https://edge.qiwi.com';
        $this->http_client = new Client;
    }

    private function getHeaders(string $auth_token = null, array $options): array {
        if (!isset($options['headers'])) {
            $headers = [
                'Content-Type' => 'application/json',
                'accept' => 'application/json',
            ];
        } else {
            $headers = $options['headers'];
        }

        if (!is_null($auth_token)) {
            $headers['authorization'] = "Bearer {$auth_token}";
        }

        return $headers;
    }

    public function setBaseUrl(string $url): QiwiClient{
        $this->base_url = $url;
        return $this;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $params
     * @param string|null $auth_token
     * @param array $options
     * @return array
     * @throws Exception
     */
    private function method(
        string $method,
        string $url,
        array $params = [],
        string $auth_token = null,
        array $options = []): array {

        $structured = array_get_val($options, 'structured', RequestOptions::QUERY);
        try {
            $response = $this->http_client->request(
                $method,
                $this->base_url . $url,
                [
                    RequestOptions::HEADERS => $this->getHeaders($auth_token, $options),
                    $structured => $params,
                ]
            );

        } catch (ClientException $e) {
            throw new \Exception($e->getResponse()->getBody()->getContents());
        }

        $this->checkCode($response);
        return json_decode($response->getBody(), true);
    }

    /**
     * @param ResponseInterface $response
     * @throws Exception
     */
    private function checkCode(ResponseInterface $response): void {
        if ($response->getStatusCode() === 200) {
            return;
        }
        $error_msg = sprintf(
            'Qiwi error, code - %s, error %s',
            $response->getStatusCode(),
            $response->getBody()->getContents()
        );

        throw new Exception($error_msg);
    }

    /**
     * @param string $url
     * @param array $params
     * @param string|null $auth_token
     * @param array $options
     * @return array
     * @throws Exception
     */
    public function get(string $url, array $params = [], string $auth_token = null, array $options = []): array {
        return $this->method(self::METHOD_GET, $url, $params, $auth_token, $options);
    }

    /**
     * @param string $url
     * @param array $params
     * @param string|null $auth_token
     * @param array $options
     * @return array
     * @throws Exception
     */
    public function post(string $url, array $params = [], string $auth_token = null, array $options = []): array {
        return $this->method(self::METHOD_POST, $url, $params, $auth_token, $options);
    }

    /**
     * @param string $url
     * @param array $params
     * @param string|null $auth_token
     * @param array $options
     * @return array
     * @throws Exception
     */
    public function put(string $url, array $params = [], string $auth_token = null, array $options = []): array {
        return $this->method(self::METHOD_PUT, $url, $params, $auth_token, $options);
    }

    /**
     * @param string $url
     * @param array $params
     * @param string|null $auth_token
     * @param array $options
     * @return array
     * @throws Exception
     */
    public function delete(string $url, array $params = [], string $auth_token = null, array $options = []): array {
        return $this->method(self::METHOD_DELETE, $url, $params, $auth_token, $options);
    }
}
