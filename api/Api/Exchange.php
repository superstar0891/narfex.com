<?php

namespace Api\Exchange;

use Api\Errors;
use Core\Response\JsonResponse;
use Core\Services\Redis\RedisAdapter;
use Db\Transaction;
use Db\Where;
use Models\BalanceModel;
use Models\ExMarketModel;
use Models\ExOrderModel;
use Models\WalletModel;
use Modules\BalanceModule;
use Modules\ExchangeModule;
use Modules\UserModule;
use Modules\WalletModule;
use Serializers\BalanceSerializers;
use Serializers\ErrorSerializer;
use Serializers\ExchangeSerializer;

class Exchange {
    public static function retrieve($request) {
        /* @var string $market
         */
        extract($request['params']);

        $market_model = ExchangeModule::getMarket($market);
        if (!$market_model) {
            JsonResponse::errorMessage('market_not_found');
        }

        $user = getUser($request);

        $depth = ExchangeModule::depth($market);
        $trades = ExchangeModule::trades($market);

        $result = [
            'depth' => $depth,
            'trades' => $trades,
            'ticker' => ExchangeModule::ticker($market),
            'fee' => (double) settings()->exchange_commision,
            'market' => ExchangeSerializer::marketListItem($market_model),
        ];

        if ($user !== null) {
            $open_orders = ExchangeModule::openOrders($user->id)
                ->map('Serializers\ExchangeSerializer::orderListItem');

            $last_orders = ExchangeModule::lastOrders($user->id, 0);

            $balances = BalanceModule::getBalances($user->id, BalanceModel::CATEGORY_EXCHANGE)
                ->map('Serializers\BalanceSerializer::listItem');

            $result['open_orders'] = $open_orders;
            $result['last_orders'] = [
                'items' => $last_orders['items']->map('Serializers\ExchangeSerializer::orderListItem'),
                'next_from' => $last_orders['next_from'],
            ];
            $result['balances'] = $balances;
        }

        JsonResponse::ok($result);
    }

    public static function tickersRetrieve() {
        //$tickers = ExchangeModule::tickers();
        $tickers = [];

        JsonResponse::ok(compact('tickers'));
    }

    public static function tickerRetrieve($request) {
        /* @var string $market */
        extract($request['params']);

        $tickers = ExchangeModule::ticker($market);

        JsonResponse::ok([
            'ticker' => $tickers,
        ]);
    }

    public static function marketsRetrieve() {
        $markets = ExchangeModule::markets();

        $from = time() - 86400;
        $to = time();

        $charts = ExchangeModule::lightChart($from, $to);
        $result = [];
        /* @var ExMarketModel $market */
        foreach ($markets as $market) {
            $market_name = $market->getName();

            $chart = isset($charts[$market_name]) ? $charts[$market_name] : [];
            $chart = array_map(function ($row) {
                return [(int) $row['date'], (double) isset($row['avg_price']) ? $row['avg_price'] : 0];
            }, $chart);

            $result[] = [
                'market' => ExchangeSerializer::marketListItem($market),
                'ticker' => ExchangeModule::ticker($market_name),
                'chart' => $chart,
            ];
        }

        JsonResponse::ok([
            'markets' => $result,
        ]);
    }

    public static function depthRetrieve($request) {
        /* @var string $market */
        extract($request['params']);

        $order_book = ExchangeModule::depth($market);

        JsonResponse::ok(compact('order_book'));
    }

    public static function tradesRetrieve($request) {
        /* @var string $market */
        extract($request['params']);

        $trades = ExchangeModule::trades($market);

        JsonResponse::ok(compact('trades'));
    }

    public static function openOrder($request) {
        /* @var string $market
         * @var double $amount
         * @var string $action
         * @var string $type
         * @var double $price
         */
        extract($request['params']);

        $user = getUser($request);

        if (UserModule::isWithdrawDisabled($user)) {
            JsonResponse::apiError(Errors::WITHDRAW_DISABLED);
        }

        list($primary, $secondary) = explode('/', $market);

//        if ($user->id != ID_AGOGLEV) {
//            JsonResponse::error(ErrorSerializer::detail(Errors::FATAL, 'Temporarily unavailable'));
//        }

        $market_info = ExMarketModel::select(Where::and()
            ->set('primary_coin', Where::OperatorEq, $primary)
            ->set('secondary_coin', Where::OperatorEq, $secondary)
        );
        if ($market_info->isEmpty()) {
            JsonResponse::errorMessage('market_not_found');
        }

        $usd_price = WalletModule::getUsdPrice($primary);
        if ($usd_price * $amount < 10) {
            $err_msg = lang('api_exchange_small_amount_err');
            $err_msg = str_replace('{amount}', round(10 / $usd_price, 8, PHP_ROUND_HALF_DOWN), $err_msg);
            JsonResponse::error(ErrorSerializer::detail(Errors::FATAL, $err_msg));
        }

        if ($usd_price * $amount > 1000000) {
            $err_msg = lang('api_exchange_large_amount_err');
            $err_msg = str_replace('{amount}', round(1000000 / $usd_price, 8, PHP_ROUND_HALF_DOWN), $err_msg);
            JsonResponse::error(ErrorSerializer::detail(Errors::FATAL, $err_msg));
        }

        $primary_balance = BalanceModule::getBalanceOrCreate($user->id, $primary, BalanceModel::CATEGORY_EXCHANGE);
        $secondary_balance = BalanceModule::getBalanceOrCreate($user->id, $secondary, BalanceModel::CATEGORY_EXCHANGE);

        if ($action === ExchangeModule::ACTION_SELL) {
            $balance_from = $primary_balance;
            $balance_to = $secondary_balance;
        } else {
            $balance_from = $secondary_balance;
            $balance_to = $primary_balance;
        }

        $fee_primary = $amount * settings()->exchange_commision / 100;
        $fee = $fee_primary;
        if ($type === ExchangeModule::TYPE_LIMIT) {
            $market_price = WalletModel::getRate($secondary, $primary, true);

            if (abs($market_price - $price) > $market_price * 0.2) {
                JsonResponse::errorMessage('exchange_no_market_price_error');
            }

            if ($action === ExchangeModule::ACTION_SELL) {
                $lock_amount = $amount;
            } else {
                $lock_amount = $amount * $price;
                $fee *= $price;
            }

            if ($balance_from->amount < $lock_amount + $fee) {
                JsonResponse::errorMessage('insufficient_funds', Errors::INSUFFICIENT_FUNDS);
            }
        } else {
            $lock_amount = 0;
            $price = 0;
        }

        if (!floodControl('exchange_order_' . $user->id, KERNEL_CONFIG['flood_control']['exchange_order'])) {
            JsonResponse::floodControlError();
        }

        $order = new ExOrderModel();
        $order->action = $action;
        $order->type = $type;
        $order->user_id = $user->id;
        $order->amount = $amount;
        $order->price = (double) $price;
        $order->primary_coin = $primary;
        $order->secondary_coin = $secondary;
        $order->status = ExchangeModule::STATUS_WORKING;
        $order->fee = $fee_primary;
        $order->avg_price = 0;

        Transaction::wrap(function () use ($balance_from, $balance_to, $lock_amount, $fee, $order) {

            if ($order->type === ExchangeModule::TYPE_LIMIT) {
                if (!$balance_from->checkAmount($lock_amount + $fee)) {
                    throw new \Exception();
                }

                if (!$balance_from->decrAmount($lock_amount + $fee)) {
                    throw new \Exception();
                }
            }

            $order->save();
        });

        RedisAdapter::shared()->publish(
            ExchangeModule::REDIS_CHANNEL_ORDERS,
            1
        );

        JsonResponse::ok([
            'balance' => BalanceSerializers::listItem($balance_from),
        ]);
    }

    public static function cancelOrder($request) {
        /* @var int $order_id
         * @var int $index
         */
        extract($request['params']);

        $user = getUser($request);

        try {
            /* @var \Models\ExOrderModel $order */
            $order = ExOrderModel::get($order_id);
        } catch (\Exception $e) {
            JsonResponse::errorMessage('order_not_found');
        }

        if ($user->id != $order->user_id) {
            JsonResponse::errorMessage('access_denied');
        }

        if ($order->status !== ExchangeModule::STATUS_WORKING) {
            JsonResponse::errorMessage('order_already_cancelled');
        }

        if ($order->type !== ExchangeModule::TYPE_LIMIT) {
            JsonResponse::errorMessage('order_type_should_be_limit');
        }

        if (!$index) {
            $index = 100;
        }

        RedisAdapter::shared()->publish(
            ExchangeModule::REDIS_CHANNEL_CANCEL,
            $order->primary_coin . '/' . $order->secondary_coin . ',' . $order->id . ',' . $order->user_id . ',' . $index
        );

        JsonResponse::ok(true);
    }

    public static function openOrders($request) {
        /* @var string $market */
        extract($request['params']);

        $user = getUser($request);

        $orders = ExchangeModule::openOrders($user->id, $market)
            ->map('Serializers\ExchangeSerializer::orderListItem');

        JsonResponse::ok(compact('orders'));
    }

    public static function ordersHistoryRetrieve($request) {
        /* @var string $market
         * @var int $start_from
         */
        extract($request['params']);

        $user = getUser($request);

        $orders = ExchangeModule::lastOrders($user->id, $start_from);

        JsonResponse::ok([
            'items' => $orders['items']->map('Serializers\ExchangeSerializer::orderListItem'),
            'next_from' => $orders['next_from'],
        ]);
    }

    public static function cancelAllOrders($request) {
        $user = getUser($request);
        $orders = ExchangeModule::openOrders($user->id);
        /* @var \Models\ExOrderModel $order */
        foreach ($orders as $order) {
            RedisAdapter::shared()->publish(
                ExchangeModule::REDIS_CHANNEL_CANCEL,
                $order->primary_coin . '/' . $order->secondary_coin . ',' . $order->id
            );
        }

        JsonResponse::ok(true);
    }

    public static function balancesRetrieve($request) {
        $user = getUser($request);
        $balances = BalanceModule::getBalances($user->id, BalanceModel::CATEGORY_EXCHANGE)
            ->map('Serializers\BalanceSerializers::listItem');

        JsonResponse::ok(compact('balances'));
    }
}
