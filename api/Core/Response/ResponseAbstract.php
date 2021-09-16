<?php

namespace Core\Response;

use Api\Errors;
use Serializers\ErrorSerializer;

abstract class ResponseAbstract {
    const AUTH_TOKEN_HEADER = 'Auth-Token';

    abstract protected static function setHeaders();

    abstract protected static function process($response);

    public static function response($response = null, array $headers = []) {
        static::setHeaders();

        foreach ($headers as $key => $value) {
            header($key . ': ' . $value);
        }

        header('Client-Version: ' . KERNEL_CONFIG['client_version']);

        $processed_response = static::process($response);

        exit($processed_response);
    }

    public static function ok($response = null, array $headers = []) {
        http_response_code(200);
        static::response($response, $headers);
    }

    public static function error($response = null, $code = 400) {
        http_response_code($code);
        static::response($response);
    }

    public static function accessDeniedError() {
        static::error(
            ErrorSerializer::detail(
                Errors::FATAL,
                lang('access_denied')
            ),
            403
        );
    }

    public static function pageNotFoundError() {
        static::error(
            ErrorSerializer::detail(
                Errors::FATAL,
                lang('error_404_desc')
            ),
            404
        );
    }

    public static function floodControlError(string $code = Errors::FATAL) {
        static::errorMessage('api_flood_control_err', $code);
    }

    public static function apiError(string $code = Errors::FATAL) {
        static::errorMessage('api_error', $code);
    }

    public static function errorMessage(string $message, string $code = Errors::FATAL, bool $need_translated = true) {
        $msg = $message;
        if ($need_translated) {
            $msg = lang($message);
        }
        static::error(ErrorSerializer::detail($code, $msg));
    }
}
