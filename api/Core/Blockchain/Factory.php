<?php

namespace Core\Blockchain;

use Blockchain\Platforms\Btc;
use Blockchain\Platforms\PlatformInterface;
use Core\Middleware\Exception\NotFoundException;

class Factory {
    public static function getInstance($currency): PlatformInterface {
        static $cache = [];

        if (isset($cache[$currency])) {
            return $cache[$currency];
        }

        $class_name = "Blockchain\Platforms\\" . ucfirst($currency);

        if (!class_exists($class_name)) {
            throw new NotFoundException($currency);
        }

        return $cache[$currency] = new $class_name();
    }

    public static function getBtcInstance(?string $wallet_name = null): PlatformInterface {
        return new Btc($wallet_name);
    }

    public static function getBtcBitcoinovnetInstance(): PlatformInterface {
        return self::getBtcInstance(KERNEL_CONFIG['listeners']['btc']['bitcoinovnet_wallet_name']);
    }
}
