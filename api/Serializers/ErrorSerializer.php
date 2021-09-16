<?php

namespace Serializers;

class ErrorSerializer {
    public static function detail(string $code, string $message = null) {
        return [
            'code' => $code,
            'message' => $message,
        ];
    }
}
