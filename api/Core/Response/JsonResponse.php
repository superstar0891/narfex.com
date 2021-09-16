<?php

namespace Core\Response;

class JsonResponse extends ResponseAbstract {
    protected static function setHeaders() {
        header('Content-Type: application/json; charset=UTF-8');
    }

    protected static function process($response = null) {
        if ($response === null) {
            $response = '{}';
        } elseif (is_array($response)) {
            $response = json_encode($response);
        } elseif (is_string($response) || is_numeric($response) || is_bool($response)) {
            $response = json_encode([
                'response' => $response,
            ]);
        }

        return $response;
    }
}