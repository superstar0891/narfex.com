<?php

namespace Api\Profit;

use Core\Response\JsonResponse;
use Db\Where;
use Models\ProfitModel;
use Serializers\ProfitSerializer;

class Profit {
    public static function retrieveList($request) {
        /* @var int $offset */
        extract($request['params']);

        $user = getUser($request);

        $profits_builder = ProfitModel::queryBuilder()
                              ->columns([])
                              ->where(Where::equal('user_id', $user->id))
                              ->orderBy(['id' => 'DESC'])
                              ->limit(25)
                              ->offset($offset)
                              ->select();
        $profits = ProfitModel::rowsToSet($profits_builder);

        $profits_result = [];
        foreach ($profits as $profit) {
            $profits_result[] = ProfitSerializer::listItem($profit);
        }

        JsonResponse::ok($profits_result);
    }
}
