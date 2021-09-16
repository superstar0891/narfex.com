<?php


namespace Middlewares;


use Api\Errors;
use Core\App;
use Core\Middleware\MiddlewareInterface;
use Core\Response\JsonResponse;
use Serializers\ErrorSerializer;

class XenditMiddleware implements MiddlewareInterface {

    public function process(&$request) {
        if (App::isProduction()) {
            $available_ips = KERNEL_CONFIG['merchant']['xendit']['production_available_ips'];
            if (!in_array(ipAddress(), $available_ips)) {
                JsonResponse::error(ErrorSerializer::detail(Errors::FATAL, 'Incorrect Xendit IP'), 401);
            }
        }
    }
}
