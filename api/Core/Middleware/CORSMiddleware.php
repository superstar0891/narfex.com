<?php

namespace Core\Middleware;

use Core\Middleware\Exception\CORSForbiddenMethodException;
use Core\Middleware\Exception\CORSForbiddenOriginException;

class CORSMiddleware implements MiddlewareInterface {
    /**
     * @param $request
     *
     * @throws CORSForbiddenMethodException
     * @throws CORSForbiddenOriginException
     */
    public function process(&$request) {
        $allow_origins = KERNEL_CONFIG['cors']['allow']['origin'];
        $allow_methods = KERNEL_CONFIG['cors']['allow']['method'];

        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $origin = $_SERVER['HTTP_ORIGIN'];

            if (!in_array($origin, $allow_origins) && !in_array('*', $allow_origins)) {
                throw new CORSForbiddenOriginException();
            }

            $allow_headers = [
                'Origin',
                'X-Requested-With',
                'Content-Type',
                'X-Token',
                'Accept',
                'X-Beta',
                'X-App-Id',
                'X-Flood-Control-Enabled',
                'X-Admin-Token',
                'X-Platform',
            ];

            header("Access-Control-Allow-Origin: {$origin}");
            header('Access-Control-Allow-Headers: ' . implode(', ', $allow_headers));
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Expose-Headers: Auth-Token');
            header('Access-Control-Max-Age: 86400');
        }

        $method = $_SERVER['REQUEST_METHOD'];
        if ($method !== 'OPTIONS' && !in_array($method, $allow_methods)) {
            throw new CORSForbiddenMethodException();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Allow-Methods: ' . implode(', ', $allow_methods));

            exit();
        }
    }
}
