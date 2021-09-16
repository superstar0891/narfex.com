<?php

date_default_timezone_set('UTC');

use Api\Errors;
use Core\App;
use Core\Exceptions\FloodControl\FloodControlException;
use Core\Exceptions\FloodControl\FloodControlExpiredAtException;
use Core\Middleware\Exception\CORSForbiddenMethodException;
use Core\Middleware\Exception\CORSForbiddenOriginException;
use Core\Response\HttpResponse;
use Core\Response\JsonResponse;
use Db\Db;
use Db\Exception\DbAdapterException;
use Engine\Debugger\Traceback;
use Engine\Parser\Exception\InvalidParamException;
use Engine\Router\Exception\RouteNotFoundException;
use Middlewares\Exception\AuthRequiredException;
use Middlewares\Exception\ForbiddenUserException;
use Middlewares\Exception\InvalidCredentialsException;
use Middlewares\Middlewares;
use Serializers\ErrorSerializer;
use \Middlewares\Exception\InvalidAdminTokenException;

require_once 'include.php';

// Include routes
require_once $root . '/Api/Routes.php';
$kernel->registerRoutes($api_routes);

// Register middlewares
$kernel->registerMiddleware(Middlewares::AuthToken)
    ->registerMiddleware(Middlewares::Permission)
    ->registerMiddleware(Middlewares::GoogleAuth)
    ->registerMiddleware(Middlewares::ExchangeAuth)
    ->registerMiddleware(Middlewares::AdminMiddleware)
    ->registerMiddleware(Middlewares::TranslatorMiddleware)
    ->registerMiddleware(Middlewares::OptionalAuthToken)
    ->registerMiddleware(Middlewares::SUMSUB_MIDDLEWARE)
    ->registerMiddleware(Middlewares::RECAPTCHA_MIDDLEWARE)
    ->registerMiddleware(Middlewares::XENDIT_MIDDLEWARE)
    ->registerMiddleware(Middlewares::QIWI_MIDDLEWARE)
    ->registerMiddleware(Middlewares::BitcoinovnetSessionHash)
    ->registerMiddleware(Middlewares::BitcoinovnetAuth)
    ->registerMiddleware(Middlewares::BEST_CHANGE)
    ->registerMiddleware(Middlewares::OPTIONAL_AUTH_BITCOINOVNET);

// Process request and handle exceptions
try {
    $kernel->request();
} catch (CORSForbiddenOriginException $e) {
    HttpResponse::error('403: Forbidden: Forbidden origin', 403);
} catch (CORSForbiddenMethodException $e) {
    HttpResponse::error('403: Forbidden: Forbidden method', 403);
} catch (ForbiddenUserException $e) {
    HttpResponse::error('403: Forbidden: Can not access resource', 403);
}  catch (InvalidCredentialsException $e) {
    HttpResponse::error('403: Forbidden: Invalid credentials', 403);
} catch (InvalidAdminTokenException $e) {
    HttpResponse::error('403: Forbidden: Invalid admin token', 403);
} catch (RouteNotFoundException $e) {
    HttpResponse::error('404: Not found', 404);
} catch (AuthRequiredException $e) {
    HttpResponse::error('403: Forbidden: Auth required', 403);
} catch (InvalidParamException $e) {
    JsonResponse::error(ErrorSerializer::detail(Errors::PARAM, $e->getMessage()));
} catch (DbAdapterException $e) {
    if (KERNEL_CONFIG['debug'] || App::isDebugIp()) {
        $pretty = Traceback::pretty(Db::getError(), Traceback::stringifyException($e));
        HttpResponse::error($pretty);
    } else {
        HttpResponse::error();
    }
} catch (FloodControlException $e) {
    JsonResponse::floodControlError();
} catch (FloodControlExpiredAtException $e) {
    JsonResponse::error([
        'code' => 'flood_control',
        'params' => [
            'expired_at' => $e->getExpiredAt()
        ]
    ]);
} catch (Exception $e) {
    if (KERNEL_CONFIG['debug'] || App::isDebugIp()) {
        $pretty = Traceback::pretty(Traceback::stringifyException($e));
        JsonResponse::error($pretty);
    } else {
        HttpResponse::error();
    }
} catch (Throwable $e) {
    if (App::isProduction() && !App::isDebugIp()) {
        HttpResponse::error();
    }
    $trace_stack = [];
    foreach ($e->getTrace() as $trace) {
        $trace_stack[] = [
            'file' => $trace['file'] ?? null,
            'line' => $trace['line'] ?? null,
            'code' => $trace['code'] ?? null,
            'function' => $trace['function'] ?? null,
            'class' => $trace['class'] ?? null,
            'args' => $trace['args'] ?? null,
        ];
    }
    JsonResponse::error([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'code' => $e->getCode(),
        'trace' => $trace_stack
    ]);
}

exit();
