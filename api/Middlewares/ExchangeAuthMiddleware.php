<?php

namespace Middlewares;

use Core\Middleware\MiddlewareInterface;
use Db\Where;
use Exception;
use Middlewares\Exception\AuthRequiredException;
use Middlewares\Exception\InvalidCredentialsException;
use Models\AppTokenModel;
use Models\UserModel;

class ExchangeAuthMiddleware implements MiddlewareInterface {
    public function process(&$request) {
        $public_key = null;
        $private_key = null;

        if (isset($_SERVER['HTTP_X_PUBLIC_KEY']) && isset($_SERVER['HTTP_X_SECRET_KEY'])) {
            $public_key = trim($_SERVER['HTTP_X_PUBLIC_KEY']);
            $secret_key = trim($_SERVER['HTTP_X_SECRET_KEY']);
        } else {
            $public_key = $request['data']->get('public_key', []);
            $secret_key = $request['data']->get('secret_key', []);
        }

        if ($public_key && $secret_key) {
            try {
                /* @var \Models\AppTokenModel $token */
                $token = AppTokenModel::select(Where::and()
                    ->set('public_key', Where::OperatorEq, $public_key)
                    ->set('token', Where::OperatorEq, $secret_key)
                    ->set('type', Where::OperatorEq, AppTokenModel::TYPE_EXCHANGE)
                )->first();
                $user = UserModel::get($token->owner_id);
            } catch (Exception $e) {
                throw new InvalidCredentialsException();
            }

            if ($token->allow_ips) {
                $ips = explode(',', $token->allow_ips);
                if (!in_array(ipAddress(), $ips, true)) {
                    throw new AuthRequiredException();
                }
            }

            $request['user'] = $user;
            $request['exchange_permissions'] = explode(',', $token->permissions);
        } else {
            $auth = new AuthTokenMiddleware();
            $auth->process($request);
        }
    }
}
