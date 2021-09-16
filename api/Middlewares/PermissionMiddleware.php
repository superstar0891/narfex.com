<?php

namespace Middlewares;

use Core\Middleware\MiddlewareInterface;
use Middlewares\Exception\ForbiddenUserException;
use Modules\ProfileModule;

class PermissionMiddleware implements MiddlewareInterface {
    public function process(&$request) {
        $permissions = array_slice(func_get_args(), 1);

        $user = getUser($request);

        if ($user === null || !ProfileModule::hasPermission($user->id, $user->role, ...$permissions)) {
            throw new ForbiddenUserException();
        }
    }
}
