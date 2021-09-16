<?php

namespace Engine\Debugger;

use Core\App;
use Exception;

define('DEBUG_COLOR_RED', "\033[1;31m");
define('DEBUG_COLOR_GREEN', "\033[1;32m");
define('DEBUG_COLOR_BLUE', "\033[1;34m");
define('DEBUG_COLOR_NC', "\033[0m");

class Traceback {
    /**
     * @param Exception $e
     *
     * @return mixed
     */
    public static function stringifyException(Exception $e) {
        $traceback = [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        foreach ($e->getTrace() as $trace) {
            $traceback[] = [
                'file' => isset($trace['file']) ? $trace['file'] : null,
                'line' => isset($trace['line']) ? $trace['line'] : null,
                'class' => isset($trace['class']) ? $trace['class'] : null,
                'function' => isset($trace['function']) ? $trace['function'] : null,
                'args' => isset($trace['args']) ? $trace['args'] : null,
            ];
        }

        return print_r($traceback, true);
    }

    /**
     * @param mixed ...$debug_data
     *
     * @return string
     */
    public static function pretty(...$debug_data): string {
        $pretty = [];
        foreach ($debug_data as $data) {
            $pretty[] = '<pre>' . print_r($data, true) . '</pre>';
        }

        return implode('<br />', $pretty);
    }


    public static function debugLog() {
//        if (App::isProduction()) {
//            return;
//        }

        $args = func_get_args();

        $out_arr = count($args) > 1 ? $args : $args[0];

        $out = DEBUG_COLOR_GREEN . 'URL: ' . $_SERVER['REQUEST_URI'] . ':' . PHP_EOL;

        $out .= DEBUG_COLOR_NC;
        $out .= self::debugPrepareArgs($out_arr);

        $out .= PHP_EOL . PHP_EOL;

        file_put_contents('/var/log/narfex/' . getSubDomain() . '_debug.log', $out, FILE_APPEND);
    }

    public static function debugPrepareArgs() {
        $arr = func_get_args();
        if (count($arr) == 1) {
            $arr = $arr[0];
        }

        ob_start();
        if ($arr === false) {
            echo("[FALSE]\n");
        } else if ($arr === '') {
            echo("[\"\"]\n");
        } else if ($arr === null) {
            echo("[NULL]\n");
        } else if (is_string($arr) || is_numeric($arr)) {
            echo(json_encode($arr . "\n"));
        } else {
            print_r($arr);
        }
        $out = ob_get_contents();
        ob_clean();
        ob_flush();

        return $out;
    }

    public static function debugRunTime($point = false) {

        if (App::isProduction()) {
            return;
        }

        static $last_time = false;

        $time = microtime(true);
        if ($point) {
            self::debugLog($point . ': ' . number_format($time - $last_time, 6) . ' ms');
        }
        $last_time = $time;
    }
}