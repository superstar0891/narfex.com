<?php

namespace Middlewares;

use Core\Middleware\MiddlewareInterface;
use Middlewares\Exception\AuthRequiredException;
use Models\UserModel;

class AdminMiddleware implements MiddlewareInterface {
    public function process(&$request) {
        if (!isset($request['user'])) {
            $auth = new AuthTokenMiddleware();
            $auth->process($request);
        }

        if (!isset($request['user'])) {
            throw new AuthRequiredException();
        }

        /** @var UserModel $user */
        $user = $request['user'];
        if (!$user->isAdmin() && (!$user->hasAdminAccess() && !in_array($user->id, [ID_AGOGLEV, ID_DBORODIN]))) {
            throw new AuthRequiredException();
        }
    }
}
