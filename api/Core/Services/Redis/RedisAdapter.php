<?php

namespace Core\Services\Redis;

use Core\App;
use Redis;

class RedisAdapter {
    /**
     * @var Redis
     */
    private static $inst = null;

    public static function shared() {
        if (self::$inst === null) {
            self::$inst = new Redis();
            $host = KERNEL_CONFIG['redis']['host'];
            self::$inst->connect($host, 6379);
        }

        return self::$inst;
    }

    public function __call($method, $args) {
        return self::$inst->$method($args);
    }
}
