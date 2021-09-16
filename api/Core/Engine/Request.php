<?php

namespace Engine;

use Core\App;
use Models\UserModel;

class Request {
    const ADMIN_APP_ID = 10;
    const MOBILE_APP_ID = 9;
    const WEB_APP_ID = 8;
    const AVAILABLE_APP_IDS = [self::ADMIN_APP_ID, self::MOBILE_APP_ID, self::WEB_APP_ID];

    /** @var Request|null */
    private static $instance = null;
    /** @var array */
    private static $request = [];
    private static $app_id = null;

    private function __construct() {
    }

    public static function getUser(): ?UserModel {
        $request = self::$request;
        return isset($request['user']) ? $request['user'] : null;
    }

    public static function getApplicationId(): int {
        if (!self::$app_id) {
            if (!isset($_SERVER['HTTP_X_APP_ID']) || !in_array($_SERVER['HTTP_X_APP_ID'], self::AVAILABLE_APP_IDS)) {
                self::$app_id = self::WEB_APP_ID;
            } else {
                self::$app_id = $_SERVER['HTTP_X_APP_ID'];
            }
        }

        return self::$app_id;
    }

    public static function isMobileApplication() {
        return self::getApplicationId() === self::MOBILE_APP_ID;
    }

    public static function isWebApplication() {
        return self::getApplicationId() === self::WEB_APP_ID;
    }

    public static function isAdminApplication() {
        return self::getApplicationId() === self::ADMIN_APP_ID;
    }

    public static function shared(array $request = []): Request {
        if (static::$instance === null) {
            static::$instance = new static();
            static::$request = $request;
        }

        return static::$instance;
    }

    private function __clone() {
    }

    private function __wakeup() {
    }
}
