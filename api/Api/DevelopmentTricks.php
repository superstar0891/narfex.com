<?php


namespace Api\DevelopmentTricks;


use Core\Response\JsonResponse;
use Models\UserModel;
use Modules\ProfileModule;

class DevelopmentTricks {
    public static function getUserToken($request) {
        /**
         * @var $user_id
         * @var $app_id
         */
        extract($request['params']);
        $user = UserModel::get($user_id);
        $res = [
            'access_token' => ProfileModule::generateToken($user, $app_id)
        ];
        JsonResponse::ok($res);
    }
}
