<?php

namespace Core\Services\Background;

class BackgroundAdapter {
    public static function run($file, $args) {
        $dir = '/var/www/bitcoinbot.pro/platform/bg_process';
        exec(sprintf('php %s/%s.php %s > /dev/null 2>/dev/null &', $dir, $file, implode(' ', $args)));
    }
}