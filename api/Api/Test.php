<?php

namespace Api\Test;

use Core\Blockchain\Factory;
use Core\Command\DefaultManager;
use Core\Command\Exception\InvalidParamException;
use Core\Response\JsonResponse;
use Engine\Debugger\Traceback;

class Test {
    public static function mainPage() {
        JsonResponse::ok([
            'timestamp' => time(),
            'v' => 1,
        ]);
    }

    public static function blockchain($r) {
        /* @var int $currency */
        extract($r['params']);

        JsonResponse::ok(Factory::getInstance($currency)->getBlockchainInfo());
    }

    public static function retrieve($r) {
        /* @var int $user_id */
        extract($r['params']);

        exit;
    }

    public static function command($request) {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        set_time_limit(0);
        ini_set('memory_limit','200M');

        echo 'Running...'.PHP_EOL;

        $user = getUser($request);

        if ($user->id != 50) {
            JsonResponse::errorMessage('access');
        }

        try {
            DefaultManager::command($_REQUEST);
            exit('OKE');
        } catch (InvalidParamException $e) {
            $response = "Param `{$e->getMessage()}` is` required";
        } catch (\Exception $e) {
            $response = Traceback::stringifyException($e);
        }

        echo $response . "\n";
        exit();
    }
}
