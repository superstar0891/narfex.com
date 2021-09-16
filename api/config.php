<?php

define('ID_AGOGLEV', 50);
define('ID_DBORODIN', 1983);
define('ID_NRADIONOV', 161);
define('ID_AFROLOV', 7);
define('ID_UGHAIRAT', 9);
define('ID_AOSIPOV', 3368);
define('ID_REPRESENTATIVE', 2307);

define('NEW_AGENTS_TS', 1570524493);
define('NEW_DEPOSIT_BALANCES_TS', 1570524493);

define('CURRENCY_FNDR', 'fndr');
define('CURRENCY_BTC', 'btc');
define('CURRENCY_ETH', 'eth');
define('CURRENCY_LTC', 'ltc');

define('CURRENCY_IDR', 'idr');
define('CURRENCY_USD', 'usd');
define('CURRENCY_EUR', 'eur');
define('CURRENCY_RUB', 'rub');

define('PLATFORM_FINDIRI', 'findiri');
define('PLATFORM_BITCOINOVNET', 'bitcoinovnet');

require 'Core/Services/DynamicConfig/DynamicConfig.php';

use Core\Services\DynamicConfig\DynamicConfig;

class Symbols {
    const BTCUSD = 'btc/usd';
}

$KERNEL_CONFIG = [

    'client_version' => 1,

    // Autoloader instructions
    'autoloader' => [
        'prefixes' => [
            // Core prefixes
            'Db' => '/Core/Db',
            'ClickHouse' => '/Core/ClickHouse',
            'Engine' => '/Core/Engine',
            'Core' => '/Core',
            'Cron' => '/Core/Cron',
            'Libs' => '/Core/Libs',
            'Blockchain' => '/Core/Blockchain',
            'Admin' => '/Modules/Admin',
            'Tests' => '/Tests',

            // App prefixes
            'Migrations' => '/Database/Migrations',
            'ClickHouseMigrations' => '/ClickHouse/Migrations',
            'Models' => '/Models',
            'Modules' => '/Modules',
            'Api' => '/Api',
            'Middlewares' => '/Middlewares',
            'Serializers' => '/Serializers',
            'Exceptions' => '/Exceptions',
        ],
    ],

    // Cross-Origin Resource Sharing instructions
    'cors' => [
        'allow' => [
            'origin' => ['*'],
            'method' => [
                'GET',
                'POST',
                'PUT',
                'DELETE',
            ],
        ],
    ],

    // App configs
    'model' => [
        // hard/soft
        'delete' => 'soft',
    ],

    // Debugger
    'debug' => getenv('APP_DEBUG') === 'true',
    'is_local' => false,

    // DB Connection Credentials
    'db' => [
        'host' => getenv('DB_HOST') ?: null,
        'socket' => DynamicConfig::shared()->getKey('DB_SOCKET'),
        'name' => DynamicConfig::shared()->getKey('DB_NAME'),
        'user' => [
            'name' => DynamicConfig::shared()->getKey('DB_USERNAME'),
            'password' => DynamicConfig::shared()->getKey('DB_PASSWORD'),
        ],
        'port' => 3306,
    ],

    'test_db' => [
        'host' => 'tests-narfex-mysql',
        'name' => 'narfex',
        'user' => [
            'name' => 'narfex',
            'password' => 'narfexPass',
        ],
        'port' => 3306,
    ],

    'redis' => [
        'host' => DynamicConfig::shared()->getKey('REDIS_HOST') ?: 'localhost'
    ],

    'redis_tests' => [
        'host' => 'tests-narfex-redis'
    ],

    'clickhouse' => [
        'host' => DynamicConfig::shared()->getKey('CLICKHOUSE_HOST'),
        'port' => 8123,
        'user' => [
            'name' => DynamicConfig::shared()->getKey('CLICKHOUSE_USERNAME'),
            'password' => DynamicConfig::shared()->getKey('CLICKHOUSE_PASS'),
        ],
    ],

    'pool' => [
      'plan_id' => 48,
    ],

    'upload_dir' => '/var/www/bitcoinbot.pro/media',

    'static_host' => 'https://static.findiri.com',
    'host' => 'https://findiri.com',

    'flood_control' => [
        'investment_profit_withdrawal' => [
            '15s' => 1,
            'day' => 5,
        ],
        'open_deposit' => [
            '15s' => 1,
            'day' => 5,
        ],
        'transaction' => [
            '15s' => 1,
            'day' => 5,
        ],
        'transfer' => [
            '15s' => 1,
            'day' => 5,
        ],
        'balance_withdraw' => [
            '15s' => 1,
            'day' => 10,
        ],
        'balance_deposit' => [
            '15s' => 1,
            'day' => 10,
        ],
        'agent_invite_total' => [
            'day' => 10,
        ],
        'agent_invite' => [ // limits per agent
            'day' => 1,
        ],
        'invite_link_view' => [
            'day' => 1,
        ],
        'registrations' => [
            '1h' => 3,
        ],
        'bitcoinovnet_reservation' => [
            '1m' => 2,
            'day' => 20,
        ],
        'bitcoinovnet_withdrawal' => [
            '1m' => 1,
            'day' => 5,
        ],
        'bitcoinovnet_update_rate' => [
            '1m' => 60,
        ],
        'bitcoinovnet_new_review' => [
            '1m' => 2,
            'day' => 5,
        ],
        'change_email' => [
            '1m' => 1,
            'day' => 10
        ],
        'reset_password' => [
            '1m' => 1,
            'day' => 5
        ],
        'sign_up' => [
            '1m' => 1,
            'day' => 5
        ],
        'exchange_order' => [
            '2s' => 1,
        ],
        'register_mobile_code' => [
            '15s' => 3,
            'day' => 5
        ],
        'fiat_exchange' => [
            '15s' => 1,
            'day' => 50,
        ],
        'fiat_withdrawal' => [
            '1m' => 1,
            'day' => 5,
        ]
    ],

    'blockchain_proxy' => [
        'host' => DynamicConfig::shared()->getKey('BLOCKCHAIN_PROXY_HOST'),
        'secret' => DynamicConfig::shared()->getKey('BLOCKCHAIN_PROXY_SECRET'),
    ],

    'listeners' => [
        'eth' => [
            'enabled' => true,
            'gas_limit' => 90000,
        ],
        'btc' => [
            'enabled' => true,
            'wallet_name' => 'findiri_users',
            'bitcoinovnet_wallet_name' => 'bitcoinovnet_wallet',
        ],
        'ltc' => [
            'enabled' => true,
        ]
    ],

    'merchant' => [
        'adv_cash' => [
            'email' => 'support@bitcoinbot.pro',
            'name' => 'BitcoinBot',
            'secret' => 'hFzee%2aALe!&v@rdVW&',
        ],
        'xendit' => [
            'user_id' => '5dd378e93e19b63825c35aab',
            'secret_api_key' => DynamicConfig::shared()->getKey('XENDIT_API_KEY') ?: 'xnd_development_GyuIeAIb5t7EqnK7flVTfS67eUezuw39uVMJFKHmDKb3WcorGx6p1wjzy2Ew6',
            'production_available_ips' => ['52.41.247.32', '52.11.161.195'],
            'development_available_ips' => ['52.89.130.89']
        ],
        'qiwi' => [
            'available_ips' => ['79.142.16.0/20', '195.189.100.0/22', '91.232.230.0/23', '91.213.51.0/24'],
        ]
    ],

    'fiat' => [
        'currencies' => ['usd', 'eur', 'rub', 'idr'],
        'fee' => 0.1,
        'invoice_fee' => [
            'usd' => [
                'min' => 60,
                'percent' => 1,
            ],
            'eur' => [
                'min' => 60,
                'percent' => 1,
            ],
            'idr' => [
                'min' => 10000,
                'percent' => 1,
            ],
            'rub' => [
                'min' => 4000,
                'percent' => 1,
            ],
        ],
        'xendit_fee' => [
            'idr' => [
                'min' => 10000,
                'percent' => 1,
            ]
        ],
        'refill_limits' => [
            'usd' => [
                'min' => 100,
                'max' => 50000,
            ],
            'eur' => [
                'min' => 100,
                'max' => 50000,
            ],
            'idr' => [
                'min' => 350000,
                'max' => 200000000,
            ],
            'rub' => [
                'min' => 5000,
                'max' => 150000,
            ],
        ]
    ],

    'wallet' => [
        'withdraw_limits' => [
            'btc' => [
                'min' => 0.001,
                'fee' => 0.0004,
            ],
            'eth' => [
                'min' => 0.02,
                'fee' => 0.01,
            ],
            'ltc' => [
                'min' => 0.002,
                'fee' => 0.001,
            ]
        ]
    ],

    'cron_secret' => DynamicConfig::shared()->getKey('CRON_SECRET'),

    'eth_root_address' => DynamicConfig::shared()->getKey('ETH_ROOT_ADDRESS'),

    'sumsub' => [
        'url' => DynamicConfig::shared()->getKey('SUMSUB_URL'),
        'client_id' => DynamicConfig::shared()->getKey('SUMSUB_CLIENT_ID'),
        'username' => DynamicConfig::shared()->getKey('SUMSUB_USERNAME'),
        'password' => DynamicConfig::shared()->getKey('SUMSUB_PASSWORD'),
        'secret_key' => DynamicConfig::shared()->getKey('SUMSUB_SECRET_KEY'),
    ],

    'telegram' => [
        'withdrawals' => [
            'bot_token' => '1158492492:AAHJuZh5jY2jFguv9nElDLGpaxAI6YqhQnE',
            'chat_id' => '-1001315109028',
        ],
        'cards' => [
            'bot_token' => '1158492492:AAHJuZh5jY2jFguv9nElDLGpaxAI6YqhQnE',
            'chat_id' => '-1001153052549',
        ],
        'bitcoinovnet' => [
            'bot_token' => '1158492492:AAHJuZh5jY2jFguv9nElDLGpaxAI6YqhQnE',
            'chat_id' => '-1001342905249',
        ],
        'bitcoinovnet_manual_operator' => [
            'bot_token' => '1081714569:AAHbLhg9iR60Tixj1zSmQXTaO5Fg3aopicI',
            'chat_id' => '-1001166792403',
        ],
        'base_url' => 'https://api.telegram.org/bot',
    ],

    'queue' => [
        'host' => DynamicConfig::shared()->getKey('QUEUE_HOST'),
        'secret' => DynamicConfig::shared()->getKey('QUEUE_SECRET')
    ],

//    'hedging' => [
//        'currencies' => ['btc', 'eth'],
//        'addresses' => [
//            'btc' => DynamicConfig::shared()->getKey('HEDGING_BTC_ADDRESS'),
//            'eth' => DynamicConfig::shared()->getKey('HEDGING_ETH_ADDRESS'),
//        ],
//        'bitmex' => [
//            'key' => DynamicConfig::shared()->getKey('HEDGING_BITMEX_KEY'),
//            'secret' => DynamicConfig::shared()->getKey('HEDGING_BITMEX_SECRET'),
//        ]
//    ],


    // ToDo: Пересоздать ключи после тестирования
    'hedging' => [
        'currencies' => ['btc', 'eth'],
        'addresses' => [
            'btc' => '3BMEX1RfxWpXU3RWbS3WQ9UubFzqJnsU8C',
            'eth' => '0xe2838019309234fd621913656dd38c262c477ddf',
        ],
        'bitmex' => [
            'key' => DynamicConfig::shared()->getKey('HEDGING_BITMEX_KEY'),
            'secret' => DynamicConfig::shared()->getKey('HEDGING_BITMEX_SECRET'),
        ]
    ],

    'blockchain_confirmations' => [
        'btc' => 2,
        'eth' => 8,
        'ltc' => 2,
    ],

    'captcha' => [
        'site' => DynamicConfig::shared()->getKey('RECAPTCHA_SITE_KEY'),
        'secret' => DynamicConfig::shared()->getKey('RECAPTCHA_SECRET_KEY')
    ],

    'base_uri' => 'http://localhost:8080/api/v1',

    'tests' => [
        'payments' => []
    ],

    'admin_withdraw' => [
        'secret' => DynamicConfig::shared()->getKey('ADMIN_WITHDRAW_SECRET'),
        'whitelist' => [
            'btc' => DynamicConfig::shared()->getKey('ADMIN_WITHDRAW_WHITE_LIST_BTC'),
            'eth' => DynamicConfig::shared()->getKey('ADMIN_WITHDRAW_WHITE_LIST_ETH'),
            'ltc' => DynamicConfig::shared()->getKey('ADMIN_WITHDRAW_WHITE_LIST_LTC'),
        ]
    ],

    'crypto' => [
        'key' => DynamicConfig::shared()->getKey('OPENSSL_PASSWORD') ?: 'bf92753d773461dae641c830e4794494df3769922d7fdfe09d9e290826b274f0',
        'method' => 'aes-128-cbc',
        'iv' => DynamicConfig::shared()->getKey('OPENSSL_IV') ?: '7w!z%C*F-JaNdRgU',
    ],

    'admin_ga_hash' => DynamicConfig::shared()->getKey('ADMIN_GA') ?: 'IAWYP7QJOHYHJFQF',

    'available_debug_ips' => DynamicConfig::shared()->getKey('AVAILABLE_DEBUG_IPS') ?: '182.253.75.224',

    'bitcoinovnet_hedging' => [
        'bitmex' => [
            'short' => [
                'key' => DynamicConfig::shared()->getKey('BITCOINOVNET_HEDGING_BITMEX_SHORT_KEY') ?: 'h033zydX1s9yfcgEokG85pAW',
                'secret' => DynamicConfig::shared()->getKey('BITCOINOVNET_HEDGING_BITMEX_SHORT_SECRET') ?: 'tQOwqtloois9mDHlsGvZNVSL5zlyF7Pi1OUm1I-7uN2dp1WB',
            ],
            'long' => [
                'key' => DynamicConfig::shared()->getKey('BITCOINOVNET_HEDGING_BITMEX_LONG_KEY') ?: 'UwbA0y_Vfetyupx-qT318cSp',
                'secret' => DynamicConfig::shared()->getKey('BITCOINOVNET_HEDGING_BITMEX_LONG_SECRET') ?: 'oDhKSoG-dTiouOjch9jdpEsM4R9XD0Hpv7cNwCg61s28vCLv',
            ],
        ],
    ],

    'best_change' => [
        'available_ips' => [
            '85.119.149.155',
            '85.119.149.169',
            '84.16.232.211',
        ]
    ]
];
