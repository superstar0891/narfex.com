<?php

namespace Middlewares;

use Core\App;
use Core\Middleware\MiddlewareInterface;
use Db\Where;
use Exception;
use Middlewares\Exception\AuthRequiredException;
use Middlewares\Exception\ForbiddenUserException;
use Middlewares\Exception\InvalidCredentialsException;
use Models\AppTokenModel;
use Models\UserModel;

class BitcoinovnetAuthMiddleware implements MiddlewareInterface {
    public function process(&$request) {
        if (!App::isBitcoinovnet()) {
            throw new ForbiddenUserException();
        }

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

        $request['user'] = $user;
    }
}
