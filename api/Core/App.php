<?php


namespace Core;

use Engine\Kernel;

class App {
    public static function isDebugIp(): bool {
        static $ips = [];
        if (empty($ips)) {
            $ips = array_map('trim', explode(',', KERNEL_CONFIG['available_debug_ips']));
        }
        return in_array(ipAddress(), $ips, true);
    }

    public static function isProduction(): bool {
        return KERNEL_CONFIG['debug'] === false;
    }

    public static function isBitcoinovnet(): bool {
        if (App::isDevelopment() && isset($_SERVER['HTTP_X_PLATFORM']) && $_SERVER['HTTP_X_PLATFORM'] === 'bitcoinovnet') {
            $_SERVER['HTTP_HOST'] = 'bitcoinov.net';
        }
        return strpos($_SERVER['HTTP_HOST'], 'bitcoinov.net') !== false;
    }

    public static function isDevelopment(): bool {
        return !self::isProduction();
    }

    public static function isTestEnvironment(): bool {
        return Kernel::isTestEnvironment();
    }

    public static function isLocalEnvironment(): bool {
        return KERNEL_CONFIG['is_local'];
    }

    public static function isFloodControlEnabled() {
        if (self::isProduction()) {
            return true;
        }

        return isset($_SERVER['HTTP_X_FLOOD_CONTROL_ENABLED']) ? (bool) $_SERVER['HTTP_X_FLOOD_CONTROL_ENABLED'] : false;
    }
}
