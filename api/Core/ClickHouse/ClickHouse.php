<?php

namespace ClickHouse;

use Core\Services\Curl\CurlAdapter;
use Db\Exception\InvalidQueryException;

class ClickHouse {

    public static $instance = null;

    private $endpoint;

    private $curl;

    public static function shared() {
        if (self::$instance === null) {
            self::$instance = new ClickHouse(
                'http://' .
                KERNEL_CONFIG['clickhouse']['user']['name'] .
                ':' .
                KERNEL_CONFIG['clickhouse']['user']['password'] .
                '@' .
                KERNEL_CONFIG['clickhouse']['host'] .
                ':' .
                KERNEL_CONFIG['clickhouse']['port'] . '/'
            );
        }
        return self::$instance;
    }

    function __construct($endpoint) {
        $this->endpoint = $endpoint;
        $this->curl = new CurlAdapter();
    }

    private function execute(string $query): ?array {
        $link = curl_init();
        curl_setopt($link, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($link, CURLOPT_POSTFIELDS, $query);

        $resp = $this->curl->execRequest($this->endpoint, $link);
        $json = json_decode($resp, true);
        if ($json === null && $resp) {
            throw new \Exception('[ClickHouse]: ' . $resp);
        } else {
            return $json;
        }
    }

    public function query($query) {
        $ret = $this->execute("{$query} FORMAT JSON");
        if (isset($ret['data'])) {
            return $ret['data'];
        }

        throw new InvalidQueryException($ret);
    }

    public function exec($query) {
        $this->execute("{$query}");
    }
}
