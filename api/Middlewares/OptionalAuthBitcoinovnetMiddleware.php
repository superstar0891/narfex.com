<?php

namespace Middlewares;

use Core\Middleware\MiddlewareInterface;
use Exception;

class OptionalAuthBitcoinovnetMiddleware implements MiddlewareInterface {
    public function process(&$request) {
        try {
            $auth = new BitcoinovnetAuthMiddleware();
            $auth->process($request);
        } catch (Exception $e) { }
    }
}
