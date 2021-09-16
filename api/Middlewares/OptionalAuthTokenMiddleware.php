<?php

namespace Middlewares;

use Core\Middleware\MiddlewareInterface;
use Exception;

class OptionalAuthTokenMiddleware implements MiddlewareInterface {
    public function process(&$request) {
        try {
            $auth = new AuthTokenMiddleware();
            $auth->process($request);
        } catch (Exception $e) { }
    }
}
