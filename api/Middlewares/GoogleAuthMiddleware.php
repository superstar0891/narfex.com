<?php

namespace Middlewares;

use Api\Errors;
use Core\Middleware\MiddlewareInterface;
use Core\Response\JsonResponse;
use Db\Where;
use Models\UserModel;
use Modules\ProfileModule;
use Serializers\ErrorSerializer;

class GoogleAuthMiddleware implements MiddlewareInterface {
    public function process(&$request) {
        $user = getUser($request);
        if (!$user) {
            $login = getParam($request, 'login', ['required']);
            $user = UserModel::select(
                Where::and()
                    ->set( Where::or()
                        ->set('login', Where::OperatorEq, $login)
                        ->set('email', Where::OperatorEq, $login))
                    ->set('platform', Where::OperatorEq, PLATFORM_BITCOINOVNET)
            );
            if ($user->isEmpty()) {
                JsonResponse::error(ErrorSerializer::detail('ga_auth_code_incorrect', 'User not found'));
            }
            $user = $user->first();
        }

        if ($user->is2FaEnabled()) {
            $params = getParams($request, [
                'ga_code' => ['required', 'maxLen' => 6, 'minLen' => 6],
            ]);
            $ga_code = $params['ga_code'];
            $request['ga_code'] = $ga_code;
            if (!ProfileModule::checkGoogleAuthCode($ga_code, $user)) {
                JsonResponse::errorMessage('api_google_code_incorrect', Errors::GA_INCORRECT);
            }
            $request['ga_checked'] = true;
        }
    }
}
