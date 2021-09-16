<?php


namespace Middlewares;


use Core\App;
use Core\Middleware\MiddlewareInterface;
use Core\Response\JsonResponse;
use Engine\Debugger\Traceback;

class QiwiMiddleware implements MiddlewareInterface {

    public function process(&$request) {
        if (App::isProduction()) {
            $available_ips = KERNEL_CONFIG['merchant']['qiwi']['available_ips'];
            $ip = ipAddress();
            $correct = false;

            foreach ($available_ips as $range) {
                $correct = checkIpInRange($ip, $range);
                if ($correct) {
                    break;
                }
            }

            if (!$correct) {
                JsonResponse::error('Invalid IP');
            }
        }
    }
}
