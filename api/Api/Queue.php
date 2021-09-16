<?php

namespace Api\Queue;

use Api\Errors;
use Core\Response\JsonResponse;
use Core\Services\Queue\Queue as QueueService;
use Serializers\ErrorSerializer;

class Queue {
    public static function invoke($request) {
        /* @var string $secret
         * @var string $type
         * @var array $params
         */
        extract($request['params']);

        if ($secret !== KERNEL_CONFIG['queue']['secret']) {
            JsonResponse::apiError();
        }

        try {
            switch ($type) {
                case QueueService::BITMEX_LONG:
                    break;
            }
        } catch (\Exception $e) {
            JsonResponse::error($e->getMessage());
        }

        JsonResponse::ok();
    }
}
