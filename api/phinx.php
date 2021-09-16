<?php

use Core\App;

ini_set('memory_limit', '9048M');

require_once 'include.php';

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/Database/Migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/Database/Seeds'
    ],
    'environments' => [
            'default_database' => '*** CHOOSE AN ENVIRONMENT ***',
            'default_migration_table' => 'phinxlog',
            'development' =>
                [
                    'adapter' => 'mysql',
                    'host' => 'narfex-mysql',
                    'name' => 'narfex',
                    'user' => 'narfex',
                    'pass' => 'narfexPass',
                    'port' => 3306,
                    'charset' => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                ],
            'testing' =>
                [
                    'adapter' => 'mysql',
                    'host' => 'tests-narfex-mysql',
                    'name' => 'narfex',
                    'user' => 'narfex',
                    'pass' => 'narfexPass',
                    'port' => 3306,
                    'charset' => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                ],
            'staging' =>
                [
                    'adapter' => 'mysql',
                    'host' => KERNEL_CONFIG['db']['host'] ?? null,
                    'unix_socket' => null,
                    'name' => KERNEL_CONFIG['db']['name'] ?? null,
                    'user' => KERNEL_CONFIG['db']['user']['name'] ?? null,
                    'pass' => KERNEL_CONFIG['db']['user']['password'] ?? null,
                    'port' => KERNEL_CONFIG['db']['port'] ?? null,
                    'charset' => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                ],

             'goglev' =>
                [
                    'adapter' => 'mysql',
                    'host' => 'db',
                    'name' => 'bb3',
                    'user' => 'root',
                    'pass' => 'password',
                    'port' => 3306,
                    'charset' => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                ],
            'production' =>
                [
                    'adapter' => 'mysql',
                    'host' => KERNEL_CONFIG['db_prod']['host'] ?? null,
                    'unix_socket' => null,
                    'name' => KERNEL_CONFIG['db_prod']['name'] ?? null,
                    'user' => KERNEL_CONFIG['db_prod']['user']['name'] ?? null,
                    'pass' => KERNEL_CONFIG['db_prod']['user']['password'] ?? null,
                    'port' => KERNEL_CONFIG['db_prod']['port'] ?? null,
                    'charset' => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                ],
    ],
];
