<?php

namespace Core\Response;

class ContinueProcessingResponse extends JsonResponse {
    public static function response($response = null, array $headers = []) {
        static::setHeaders();

        foreach ($headers as $key => $value) {
            header($key . ': ' . $value);
        }

        header('Client-Version: ' . KERNEL_CONFIG['client_version']);

        $processed_response = static::process($response);

        echo $processed_response;

        session_write_close();
        fastcgi_finish_request();
    }
}