<?php

namespace Core\Services\Queue;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class Queue {

    const BITMEX_LONG = 'bitmex_long';

    private $httpClient;

    /**
     * @var Queue
     */
    private static $inst = null;

    public static function shared() {
        if (self::$inst === null) {
            self::$inst = new Queue();
        }

        return self::$inst;
    }

    public function __construct() {
        $this->httpClient = new Client;
    }

    public function pushJob(string $type, array $params): bool {
        return $this->create('job', [
            'type' => $type,
            'params' => $params,
        ]);
    }

    private function create(string $queue, array $data): bool {
        $host = KERNEL_CONFIG['queue']['host'];

        $body = [
            'type' => $queue,
            'data' => $data,
            'options' => [
                'attempts' => 3,
                'priority' => 'high',
            ]
        ];

        $request = $this->httpClient->post("http://{$host}:3876/job", [
            RequestOptions::JSON => $body,
            RequestOptions::TIMEOUT => 5,
        ]);

        return $request->getStatusCode() == 200;
    }
}
