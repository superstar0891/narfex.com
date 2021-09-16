<?php

namespace Middlewares;

use Core\Middleware\MiddlewareInterface;
use Middlewares\Exception\AuthRequiredException;
use Models\UserModel;
use Models\UserPermissionModel;

class TranslatorMiddleware implements MiddlewareInterface {
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
        if (false === $user->hasPermission(UserPermissionModel::DOCS_EDITOR_PERMISSION)) {
            throw new AuthRequiredException();
        }
    }
}