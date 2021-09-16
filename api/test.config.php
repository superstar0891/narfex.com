<?php

$KERNEL_CONFIG = [
    'db' => [
        'host' => 'tests-narfex-mysql',
        'name' => 'narfex',
        'user' => [
            'name' => 'narfex',
            'password' => 'narfexPass',
        ],
        'port' => 3306,
    ],

    'redis' => [
        'host' => 'tests-narfex-redis'
    ],

    'base_uri' => 'tests-narfex-webserver/api/v1',
];
