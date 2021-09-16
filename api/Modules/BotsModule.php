<?php

namespace Modules;

class BotsModule {
    public static function indicators($bot_type) {
        $result = [];
        switch ($bot_type) {
            case 'default':
                $result[] = [
                    'name' => 'Commodity Channel Index',
                    'id' => 'cci',
                    'params' => [
                        [
                            'id' => 'buy',
                            'name' => 'Buy',
                            'default' => -100,
                            'format' => 'number',
                        ],
                        [
                            'id' => 'sell',
                            'name' => 'Sell',
                            'default' => 100,
                            'format' => 'number',
                        ],
                        [
                            'id' => 'period',
                            'name' => 'Period',
                            'default' => 20,
                            'format' => 'number',
                        ]
                    ]
                ];
                break;
            case 'trend_line':
                $result[] = [
                    'name' => 'Trend line',
                    'id' => 'cci',
                    'params' => [
                        [
                            'id' => 'sell_point_1_date',
                            'name' => 'Sell Point 1: Date',
                            'format' => 'datetime',
                        ],
                        [
                            'id' => 'sell_point_1_price',
                            'name' => 'Sell Point 1: Price',
                            'format' => 'number',
                        ],
                        [
                            'id' => 'sell_point_2_date',
                            'name' => 'Sell Point 2: Date',
                            'format' => 'datetime',
                        ],
                        [
                            'id' => 'sell_point_2_price',
                            'name' => 'Sell Point 1: Price',
                            'format' => 'number',
                        ],

                        [
                            'id' => 'buy_point_1_date',
                            'name' => 'Buy Point 1: Date',
                            'format' => 'datetime',
                        ],
                        [
                            'id' => 'buy_point_1_price',
                            'name' => 'Buy Point 1: Price',
                            'format' => 'number',
                        ],
                        [
                            'id' => 'buy_point_2_date',
                            'name' => 'Buy Point 2: Date',
                            'format' => 'datetime',
                        ],
                        [
                            'id' => 'buy_point_2_price',
                            'name' => 'Buy Point 1: Price',
                            'format' => 'number',
                        ],
                    ]
                ];
                break;
        }

        return $result;
    }

    public static function exchanges() {
        return [
            [
                'name' => 'Bitmex',
                'id' => 'bitmex',
            ]
        ];
    }

    public static function markets() {
        return [
            [
                'name' => 'XBT/USD',
                'id' => 'XBT/USD',
            ]
        ];
    }

    public static function types() {
        return [
            [
                'name' => 'Default',
                'id' => 'default',
            ],
            [
                'name' => 'Trend Line Bot',
                'id' => 'trend_line',
            ]
        ];
    }

    public static function timeFrames() {
        return [
            [
                'name' => '15m',
                'id' => '15m',
            ],
            [
                'name' => '30m',
                'id' => '30m',
            ],
            [
                'name' => '1h',
                'id' => '1h',
            ]
        ];
    }
}
