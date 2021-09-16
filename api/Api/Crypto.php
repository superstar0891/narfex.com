<?php

namespace Api\Crypto;

use Core\Blockchain\BlockchainNotify;
use Core\Response\JsonResponse;

class Crypto {
    public static function notify($request) {
        /* @var string $currency
         * @var string $txid
         */
        extract($request['params']);

        $inst = new BlockchainNotify($currency, $txid);
        $inst->process();

        JsonResponse::ok();
    }

    public static function blockUpdate($request) {
        /* @var string $currency */
        extract($request['params']);

        $inst = new BlockchainNotify($currency);
        $inst->blockChainNotify();

        JsonResponse::ok();
    }
}
