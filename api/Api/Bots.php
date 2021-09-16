<?php

namespace Api\Bots;

use Api\Errors;
use Core\Response\JsonResponse;
use Core\Services\Redis\RedisAdapter;
use Db\Where;
use Models\BotExchangeAccountModel;
use Models\BotModel;
use Models\BotTradeModel;
use Modules\BotsModule;
use Serializers\ErrorSerializer;

function createBot($request) {
    /* @var string $name */
    extract($request['params']);

    $user = getUser($request);

    $bot = new BotModel();
    $bot->name = $name;
    $bot->user_id = $user->id;
    $bot->save();

    JsonResponse::ok($bot->toJson());
}

function addExchange($request) {
    /* @var string $name
     * @var string $exchange
     * @var string $key
     * @var string $secret
     */
    extract($request['params']);

    $user = getUser($request);

    $e = new BotExchangeAccountModel();
    $e->user_id = $user->id;
    $e->name = $name;
    $e->exchange = $exchange;
    $e->api_key = $key;
    $e->api_secret = $secret;
    $e->save();

    JsonResponse::ok($e->toJson());
}


function retrieve($request) {
    $user = getUser($request);

    $bot = BotModel::select(Where::equal('user_id', $user->id));
    JsonResponse::ok($bot->toJson());
}

function botRetrieve($request) {
    /* @var int $bot_id */
    extract($request['params']);

    $user = getUser($request);

    /* @var BotModel $bot */
    $bot = BotModel::get($bot_id);

    if ($bot->user_id != $user->id) {
        JsonResponse::accessDeniedError();
    }

    $history = BotTradeModel::queryBuilder()
        ->columns([])
        ->where(Where::equal('bot_id', $bot->id))
        ->limit(20)
        ->orderBy(['id' => 'DESC'])
        ->select();
    $history = BotTradeModel::rowsToSet($history);

    $result = [
        'bot' => $bot->toJson(),
        'indicators' => BotsModule::indicators($bot->type),
        'exchanges' => BotsModule::exchanges(),
        'symbols' => BotsModule::markets(),
        'bot_types' => BotsModule::types(),
        'time_frames' => BotsModule::timeFrames(),
        'history' => $history->toJson(),
    ];

    JsonResponse::ok($result);
}

function optionsRetrieve($request) {
    /* @var int $type */
    extract($request['params']);

    $result = [
        'indicators' => BotsModule::indicators($type),
        'exchanges' => BotsModule::exchanges(),
        'symbols' => BotsModule::markets(),
        'time_frames' => BotsModule::timeFrames(),
    ];

    JsonResponse::ok($result);
}

function setBotStatus($request) {
    /* @var int $bot_id
     * @var string $status
     */
    extract($request['params']);

    $user = getUser($request);

    /* @var \Models\BotModel $bot */
    $bot = BotModel::get($bot_id);

    if ($bot->user_id != $user->id) {
        JsonResponse::accessDeniedError();
    }


    if ($status === 'activated') {
        if (!$bot->trade_amount || !$bot->max_trade_amount || !$bot->symbol || !$bot->exchange || !$bot->indicators || !$bot->time_frame) {
            JsonResponse::apiError();
        }
    }

    $bot->status = $status;
    $bot->save();


    JsonResponse::ok($bot->toJson());
}

function editBot($request) {
    /* @var int $bot_id
     * @var string $name
     * @var string $time_frame
     * @var string $symbol
     * @var double $trade_amount
     * @var double $max_trade_amount
     * @var double $take_profit
     * @var string $exchange_key
     * @var string $exchange_secret
     * @var string $exchange
     * @var array $indicators
     * @var double $leverage
     * @var string $status
     */
    extract($request['params']);

    $user = getUser($request);

    /* @var \Models\BotModel $bot */
    $bot = BotModel::get($bot_id);

    if ($bot->user_id != $user->id) {
        JsonResponse::accessDeniedError();
    }

    $indicators_exist = BotsModule::indicators($bot->type);
    $indicators_exist_map = [];
    foreach ($indicators_exist as $item) {
        $params = [];
        foreach ($item['params'] as $param) {
            $params[$param['id']] = $param;
        }

        $item['params'] = $params;
        $indicators_exist_map[$item['id']] = $item;
    }

    $indicators_filtered = [];
    foreach ($indicators as $indicator) {
        $name = $indicator['name'];
        if (!isset($indicators_exist_map[$name])) {
            JsonResponse::errorMessage('Unknown indicator: ' . $name, Errors::FATAL, false);
        }

        $conf = $indicators_exist_map[$name];
        $prepared = [
            'name' => $name,
            'params' => []
        ];
        foreach ($indicator['params'] as $param => $value) {
            if (!isset($conf['params'][$param])) {
                JsonResponse::errorMessage('Unknown indicator parameter: ' . $param, Errors::FATAL, false);
            }

            $prepared['params'][$param] = (double) $value;
        }

        $prepared['params'] = json_encode($prepared['params']);
        $indicators_filtered[] = $prepared;
    }

    $bot->name = $name;
    $bot->trade_amount = $trade_amount;
    $bot->max_trade_amount = $max_trade_amount;
    $bot->take_profit = $take_profit;
    $bot->symbol = $symbol;
    $bot->time_frame = $time_frame;
    if ($exchange && $exchange_key && $exchange_secret) {
        $bot->exchange = json_encode([
            'name' => $exchange,
            'key' => $exchange_key,
            'secret' => $exchange_secret
        ]);
    }
    $bot->indicators = json_encode($indicators_filtered);
    $bot->leverage = $leverage;

    if ($bot->status !== 'activated' && $status === 'activated') {
        $bot->start_date = time();
    }
    $bot->status = $status;

    $bot->save();

    RedisAdapter::shared()->publish('bot_updated', $bot->id);

    JsonResponse::ok($bot->toJson());
}
