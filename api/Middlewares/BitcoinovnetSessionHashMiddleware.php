<?php

namespace Middlewares;

use Core\Middleware\MiddlewareInterface;
use Core\Response\JsonResponse;

class BitcoinovnetSessionHashMiddleware implements MiddlewareInterface {
    public function process(&$request) {
        $params = getParams($request, [
            'session_hash' => [],
            'request_id' => [],
        ]);
        $hash = $params['session_hash'];

        if ($hash) {
            if (strlen($hash) !== 64) {
                JsonResponse::errorMessage('Session hash is incorrect');
            }
        }
    }
}
