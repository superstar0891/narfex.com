<?php

namespace Api\Cron;

use Core\App;
use Core\Command\Cron;
use Core\Response\JsonResponse;

function exec($request) {
    /* @var string $job
     * @var string $secret
     */
    extract($request['params']);

    if (App::isProduction() && $secret !== KERNEL_CONFIG['cron_secret']) {
        JsonResponse::errorMessage('access_denied');
    }

    $cron = new Cron($job);
    $cron->exec();

    JsonResponse::ok();
}
