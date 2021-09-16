<?php

namespace Middlewares;

use Core\App;
use Core\Middleware\MiddlewareInterface;
use Core\Response\JsonResponse;

class BestChangeMiddleware implements MiddlewareInterface {
    public function process(&$request) {
        if (App::isProduction()) {
            $available_ips = KERNEL_CONFIG['best_change']['available_ips'];
            if (!in_array(ipAddress(), $available_ips)) {
                JsonResponse::error('Invalid IP');
            }
        }
    }
}
