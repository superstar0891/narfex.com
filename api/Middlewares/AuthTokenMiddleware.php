<?php

namespace Middlewares;

use Core\App;
use Core\Middleware\MiddlewareInterface;
use Core\Response\JsonResponse;
use Core\Services\Redis\RedisAdapter;
use Db\Where;
use Exception;
use Middlewares\Exception\AuthRequiredException;
use Middlewares\Exception\InvalidCredentialsException;
use \Middlewares\Exception\InvalidAdminTokenException;
use Models\AppTokenModel;
use Models\UserModel;

class AuthTokenMiddleware implements MiddlewareInterface {
    public function process(&$request) {
//        if (App::isBitcoinovnet()) {
//            JsonResponse::accessDeniedError();
//        }

        if (!isset($_SERVER['HTTP_X_TOKEN'])) {
            throw new AuthRequiredException();
        }

        $token_str = trim($_SERVER['HTTP_X_TOKEN']);

        try {
            $token = AppTokenModel::first(Where::equal('token', $token_str));
            if (!$token) {
                throw new InvalidCredentialsException();
            }

            /** @var UserModel $user|null */
            $user = UserModel::first(Where::and()
                ->set(Where::equal('id', $token->owner_id))
                ->set(Where::equal('platform', PLATFORM_BITCOINOVNET))
            );
            if (is_null($user) || $user->isBanned()) {
                throw new InvalidCredentialsException();
            }
        } catch (Exception $e) {
            throw new InvalidCredentialsException();
        }

        if (isset($_SERVER['HTTP_X_ADMIN_TOKEN']) && $user->isAdmin()) {
            $token = substr(trim($_SERVER['HTTP_X_ADMIN_TOKEN']), 0, 32);
            $user_id = (int) RedisAdapter::shared()->get('admin_tmp_token_' . $token);
            if (!$user_id || $user_id < 0) {
                throw new InvalidAdminTokenException();
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                JsonResponse::errorMessage('You don\'t have edit access');
            }

            $user = UserModel::get($user_id);
        }

        $request['user'] = $user;
    }
}
