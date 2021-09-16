<?php

namespace Api\ExchangeChart;

use Core\Response\JsonResponse;
use Models\ExMarketModel;
use Modules\ExchangeModule;

class ExchangeChart {
    public static function configRetrieve() {
        $result = [
            'supportedResolutions' => [ "1", "15", "30", "60", "120", '240', "1D", "2D", "3D", "1W", "3W", "1M", '6M' ],
            'supported_resolutions' => ['1', '5', '15', '30', '60', '120', '240', '1D', '1W', '1M'],
            'supports_group_request' => false,
            'supports_marks' => false,
            'supports_search' => true,
            'supports_timescale_marks' => false,
        ];

        JsonResponse::ok($result);
    }

    public static function symbolInfoRetrieve() {

        $markets = ExMarketModel::select();
        $symbols = [];
        $description = [];
        /* @var ExMarketModel $market */
        foreach ($markets as $market) {
            $symbols[] = $market->primary_coin . '/' . $market->secondary_coin;

            /* ToDo: implement descriptions */
            $description[] = $market->primary_coin . '/' . $market->secondary_coin;
        }

        /* @var string $group */
        $result = [
            'symbols' => $symbols,
            'description' => $description,
            'min_price_move' => 0.1,
            'supported_resolutions' => ['1', '5', '15', '30', '60', '120', '240', '1D', '1W', '1M'],
        ];

        JsonResponse::ok($result);
    }

    public static function symbolsRetrieve($request) {
        /* @var string $symbol */
            extract($request['params']);

        $result = [
            'name' => $symbol,
            'description' => 'Bitcoin',
            'min_price_move' => 0.1,
            'type' => 'bitcoin',
            'has_intraday' => true,
            'supported_resolutions' => ['1', '5', '15', '30', '60', '120', '240', '1D', '1W', '1M'],
        ];

        JsonResponse::ok($result);
    }

    public static function historyRetrieve($request) {
        /* @var string $symbol
         * @var int $from
         * @var int $to
         * @var string $resolution
         */
        extract($request['params']);

        date_default_timezone_set('UTC');

        $symbol = str_replace(':', '/', $symbol);

        $resolution_map = [
            '1' => 1,
            '5' => 5,
            '15' => 15,
            '30' => 30,
            '60' => 60,
            '120' => 120,
            '2H' => 120,
            '240' => 120 * 2,
            'D' => 86400,
            '1D' => 86400,
            '1W' => 86400  * 7,
            'W' => 86400  * 7,
            '1M' => 86400 * 30,
            'M' => 86400 * 30
        ];

        $rows = ExchangeModule::chart($symbol, $resolution_map[$resolution], $from, $to);

        $close = [];
        $open = [];
        $high = [];
        $low = [];
        $volume = [];
        $times = [];

        foreach ($rows as $row) {
            $times[] = $row['date'];
            $close[] = isset($row['close']) ? $row['close'] : null;
            $open[] = isset($row['open']) ? $row['open'] : null;
            $high[] = isset($row['high']) ? $row['high'] : null;
            $low[] = isset($row['low']) ? $row['low'] : null;
            $volume[] = $row['volume'];
        }

        if (empty($times)) {
            $result = [
                's' => 'no_data',
            ];
        } else {
            $result = [
                's' => 'ok',
                't' => $times,
                'c' => $close,
                'o' => $open,
                'h' => $high,
                'l' => $low,
                'v' => $volume,
                'nextTime' => end($times),
            ];
        }

        JsonResponse::ok($result);
    }
}
