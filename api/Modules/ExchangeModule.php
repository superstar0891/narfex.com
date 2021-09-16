<?php

namespace Modules;

use ClickHouse\ClickHouse;
use Core\Services\Redis\RedisAdapter;
use Db\Model\ModelSet;
use Db\Where;
use Models\ExMarketModel;
use Models\ExOrderModel;
use Models\WalletModel;
use Serializers\ExchangeSerializer;

class ExchangeModule {

    const ACTION_SELL = 'sell';
    const ACTION_BUY = 'buy';

    const TYPE_LIMIT = 'limit';
    const TYPE_MARKET = 'market';

    const STATUS_WORKING = 'working';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';

    const REDIS_CHANNEL_ORDERS = 'exchange_orders';
    const REDIS_CHANNEL_CANCEL = 'exchange_cancel_order';

    public static function openOrders(int $user_id, string $market = null): ModelSet {
        $where = Where::and()
            ->set('user_id', Where::OperatorEq, $user_id)
            ->set('status', Where::OperatorEq, ExchangeModule::STATUS_WORKING)
            ->set('type', Where::OperatorEq, ExchangeModule::TYPE_LIMIT);

        if ($market !== null) {
            list($primary, $secondary) = explode('/', $market);
            $where->set('primary_coin', Where::OperatorEq, $primary);
            $where->set('secondary_coin', Where::OperatorEq, $secondary);
        }

        return ExOrderModel::select($where);
    }

    public static function lastOrders($user_id, $page): array {
        //$time = strtotime('-24 hours');

        $builder = ExOrderModel::queryBuilder()
            ->columns([])
            ->where(Where::and()
                ->set('user_id', Where::OperatorEq, $user_id)
                ->set('status', Where::OperatorIN, ['completed', 'cancelled', 'failed'])
            )
            ->orderBy(['id' => 'DESC']);

        $res = $builder->paginate($page);

        return [
            'items' => $res->getItems(),
            'next_from' => $res->getNext(),
        ];
    }

    public static function depth(string $market): array {
        list($primary, $secondary) = explode('/', $market);
        $orders = ExOrderModel::select(Where::and()
            ->set('status', Where::OperatorEq, 'working')
            ->set('primary_coin', Where::OperatorEq, $primary)
            ->set('secondary_coin', Where::OperatorEq, $secondary)
        );
        $bids = [];
        $asks = [];

        /* @var \Models\ExOrderModel $order */
        foreach ($orders as $order) {
            if ($order->action === 'sell') {
                $asks[] = ExchangeSerializer::orderListItem($order);
            } else if ($order->action === 'buy') {
                $bids[] = ExchangeSerializer::orderListItem($order);
            }
        }

        return compact('asks', 'bids');
    }

    public static function trades(string $market): array {
        $rows = ClickHouse::shared()->query("SELECT price, amount, action_time FROM exchange_orders WHERE market = '{$market}' ORDER by action_time DESC LIMIT 0,30");
        return array_map('Serializers\ExchangeSerializer::tradeListItem', $rows);
    }

    public static function lightChart(int $from, int $to) {
        $cache_key = 'light_chart_' . date('H', $to);

        $cache = RedisAdapter::shared()->get($cache_key);
        if ($cache) {
            return json_decode($cache, true);
        }

        $where = "source = 'binance'";
        if ($from) {
            $where .= " AND action_time > toDateTime({$from})";
        }

        if ($to) {
            $where .= " AND action_time < toDateTime({$to})";
        }

//        $rows = ClickHouse::shared()->query("
//SELECT
//any(action_time) as date, toStartOfInterval(action_time, INTERVAL 1 hour) as group_date, AVG(price) as avg_price, market
//FROM exchange_orders
//WHERE {$where}
//GROUP by group_date, market
//ORDER by group_date DESC
//LIMIT 0,300
//        ");

        $rows = ClickHouse::shared()->query("
SELECT 
any(action_time) as date, toStartOfInterval(action_time, INTERVAL 1 hour) as group_date, AVG(close) as avg_price, pair
FROM candles
WHERE {$where}
GROUP by group_date, pair
ORDER by group_date DESC
LIMIT 0,300
        ");

        $rows_map = [];
        foreach ($rows as $row) {
            if (!isset($rows_map[strtotime($row['group_date'])])) {
                $rows_map[strtotime($row['group_date'])] = [];
            }
            $rows_map[strtotime($row['group_date'])][] = $row;
        }

        if (empty($rows)) {
            return [];
        }

        $result = [];
        $start_time = strtotime($rows[0]['group_date']);
        $interval = 3600;
        for ($i = 0; $i < 24; $i++) {
            if (isset($rows_map[$start_time])) {
                foreach ($rows_map[$start_time] as $row) {
                    if (!isset($result[$row['pair']])) {
                        $result[$row['pair']] = [];
                    }
                    $row['date'] = $start_time;
                    $result[$row['pair']][] = $row;
                }
            } else {
                foreach ($result as $market => $_) {
                    $result[$market][] = ['empty' => true, 'volume' => (double) 0, 'date' => $start_time];
                }
            }

            $start_time -= $interval;
        }

        RedisAdapter::shared()->set($cache_key, json_encode($result), 3600);

        return $result;
    }

    public static function chart($market, int $time_frame, int $from, int $to): array {
        $where = '';
        if ($from) {
            $where .= " AND action_time > toDateTime({$from})";
        }

        if ($to) {
            $where .= " AND action_time < toDateTime({$to})";
        }

        $rows = ClickHouse::shared()->query("
SELECT 
MAX(price) as high, MIN(price) as low, SUM(amount) as volume, any(price) as open, anyLast(price) as close, any(action_time) as date,
toStartOfInterval(action_time, INTERVAL {$time_frame} minute) as group_date, AVG(price) as avg_price
FROM (
  SELECT * FROM exchange_orders WHERE market = '{$market}' {$where} ORDER by action_time LIMIT 0,1000
) as sub
GROUP by group_date
ORDER by group_date DESC");

        $rows_map = [];
        foreach ($rows as $row) {
            $rows_map[strtotime($row['group_date'])] = $row;
        }

        if (empty($rows)) {
            return [];
        }

        $result = [];
        $start_time = strtotime($rows[0]['group_date']);
        $interval = $time_frame * 60;
        $empty_count = 0;
        for ($i = 0; $i < 1000; $i++) {
            if (isset($rows_map[$start_time])) {
                $item = $rows_map[$start_time];
                $empty_count = 0;
            } else {
                $item = ['empty' => true, 'volume' => (double) 0];
                $empty_count++;
            }
            $item['date'] = $start_time;
            $result[] = $item;

            $start_time -= $interval;
        }

        if ($empty_count > 0) {
            array_splice($result, -$empty_count);
        }

        return array_reverse($result);
    }

    public static function ticker($market): ?array {
        $day_key = date('Y-m-d');
        $ticker_raw = json_decode(RedisAdapter::shared()->get("ticker_{$day_key}_{$market}"));
        if (!$ticker_raw) {
            $ticker_raw = (object) [
                'last_price' => 0,
                'first_price' => 0,
                'volume' => 0,
                'max_price' => 0,
                'min_price' => 0,
            ];
        }

        return ExchangeSerializer::tickerListItem($market, $ticker_raw);


        $where = 'toStartOfDay(action_time) > yesterday()';
        $where .= " AND market = '{$market}'";

//        $rows = ClickHouse::shared()->query("
//SELECT
//SUM(volume) as volume,
//market,
//any(first_price) as first_price,
//max(max_price) as max_price,
//min(min_price) as min_price
//FROM (
//  SELECT any(market) as market, SUM(amount) as volume, any(price) as first_price, max(price) as max_price, min(price) as min_price, toStartOfInterval(action_time, INTERVAL 1 hour) as group_date FROM exchange_orders WHERE {$where} group by group_date ORDER by group_date ASC
//) as sub
//GROUP by market
//");

        $rows = ClickHouse::shared()->query("
SELECT 
SUM(amount) as volume,
any(price) as first_price,
max(price) as max_price,
min(price) as min_price
FROM exchange_orders
WHERE {$where}
ORDER BY any(action_time) DESC
");

        if (empty($rows)) {
            return null;
        }

        $result = [];
        foreach ($rows as $row) {
            $price24hours = $row['first_price'];

            list($primary, $secondary) = explode('/', $market);
            $price = WalletModel::getRate($secondary, $primary);

            $row['market'] = $market;
            $row['price'] = WalletModel::getRate($secondary, $primary);
            $row['percent'] = $price24hours > 0 ? ($price - $price24hours) / $price24hours * 100 : 0;
            $row['diff'] = $price - $price24hours;

            $result[] = $row;
        }

        return ExchangeSerializer::tickerListItem($result[0]);
    }

    /* @return []\Models\ExMarketModel */
    public static function markets() {
        $result = [];

        $priority = array_map(function ($currency) {
            return "'{$currency}'";
        }, [CURRENCY_FNDR, 'btc', 'eth', 'usdt', 'ltc', 'xrp', 'bchabc']);
        $priority = implode(',', $priority);

        $builder = ExMarketModel::queryBuilder()
            ->columns([])
            ->orderBy(["FIELD(primary_coin, {$priority})" => 'ASC', "FIELD(secondary_coin, {$priority})" => 'ASC'])
            ->select();

        $markets = ExMarketModel::rowsToSet($builder);
        /* @var \Models\ExMarketModel $market */
        foreach ($markets as $market) {
            $result[$market->id] = $market;
        }
        return $result;
    }

    public static function getMarket($market_name): ?ExMarketModel {
        [$primary, $secondary] = explode('/', trim($market_name));

        $markets = ExMarketModel::select(Where::and()
            ->set(Where::equal('primary_coin', $primary))
            ->set(Where::equal('secondary_coin', $secondary))
        );

        if ($markets->isEmpty()) {
            return null;
        }

        /* @var ExMarketModel $market */
        $market = $markets->first();

        return $market;
    }
}
