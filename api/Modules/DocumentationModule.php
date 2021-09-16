<?php

namespace Modules;

use Core\App;
use Core\Middleware\DefaultMiddlewares;
use Db\Where;
use Engine\Request;
use Engine\Router\Router;
use Models\MethodInfoModel;
use Serializers\MethodInfoSerializer;

class DocumentationModule {
    public static function getDefaultSchema(Router $router, array $api_routes): array {
        $result = [];
        foreach ($api_routes as $route) {
            /* @var \Core\Route\Route $route */
            $base_path = array_slice(explode('/', $route->getBasePath()), 2);

            $pointer = &$result;
            for ($i = 1; $i < count($base_path); $i++) {
                $path = ucfirst($base_path[$i]);
                if (!isset($pointer[$path])) {
                    $pointer[$path] = [];
                }
                $pointer = &$pointer[$path];
            }

            $raw_path = substr($route->getRawPath(), 1);
            $path = preg_split("/(_|\/\%n\:)/", $raw_path);
            $path = implode('', array_map('ucfirst', $path));


            $params = $route->getParameters();
            $params_map = [];

            $url_params = array_keys($router->getParameters($route->getPath(), $route->getPath()));

            foreach ($params as $name => $filters) {
                $name_key = preg_split("/(_|\/\%n\:)/", $name);
                $name_key = implode('', array_map('ucfirst', $name_key));
                $params_map[$name_key] = [
                    'name' => $name,
                    'filters' => $filters,
                    'type' => in_array($name, $url_params, true) ? 'query' : 'body',
                ];
            }

            foreach ($route->getMiddlewares() as $middleware => $_) {
                switch ($middleware) {
                    case 'GoogleAuthMiddleware':
                        $params_map['GaCode'] = [
                            'name' => 'ga_code',
                            'filters' => ['minLen' => 6, 'maxLen' => 6],
                            'type' => 'body',
                        ];
                        break;
                }
            }

            $method_name = $path ?: 'Default';
            $method_name .= ucfirst(strtolower($route->getMethod()));

            $path = implode('/', array_slice($base_path, 1)) . $route->getRawPath();
            if (substr($path, 0, 1) === '/') {
                $path = substr($path, 1);
            }

            $pointer[$method_name] = [
                'method' => $route->getMethod(),
                'name' => $raw_path ?: '',
                'params' => $params_map,
                'path' => $path,
            ];
        }

        return $result;
    }

    public static function getSchema(
        Router $router,
        array $api_routes,
        bool $with_description = false): array {
        $user = Request::getUser();
        $is_admin = $user ? $user->isAdmin() : false;

        $result = [];
        $methods_info = $with_description ? MethodInfoModel::select()->toArray() : [];
        foreach ($api_routes as $route) {
            /* @var \Core\Route\Route $route */
            if ($route->getPath() === '') {
                continue;
            }
            if (App::isProduction() && $route->isHidden() && !$is_admin) {
                continue;
            }
            $base_path = array_slice(explode('/', $route->getPath()), 2);
            foreach ($base_path as $key => $value) {
                if (!isset($base_path[$key-1])) {
                    continue;
                }
                if (false !== strpos($value, '%n:')) {
                    unset($base_path[$key]);
                }
            }

            $base_path_parts_count = count($base_path);
            if ($base_path_parts_count > 2) {
                array_pop($base_path);
            }

            $route_unique_key = implode('-',
                array_slice(explode('/', substr($route->getPath(), 1)), 2)) .
                '-' . strtolower($route->getMethod());

            $pointer = &$result;
            for ($i = 1; $i < count($base_path); $i++) {
                $path = ucfirst($base_path[$i]);
                if (!isset($pointer[$path])) {
                    $pointer[$path] = [];
                }
                $pointer = &$pointer[$path];
            }

            $params = $route->getParameters();
            $params_map = [];

            $url_params = array_keys($router->getParameters($route->getPath(), $route->getPath()));

            foreach ($params as $name => $filters) {
                $params_map[] = [
                    'name' => $name,
                    'filters' => self::transformFilters($filters),
                    'type' => in_array($name, $url_params, true) ? 'query' : 'body',
                ];
            }

            $requirements = [];
            foreach ($route->getMiddlewares() as $middleware => $_) {
                switch ($middleware) {
                    case 'RecaptchaMiddleware':
                        $params_map[] = [
                            'name' => 'recaptcha_response',
                            'filters' => ['required' => true],
                            'type' => 'body',
                        ];
                        break;
                    case 'ExchangeAuthMiddleware':
                        $params_map[] = [
                            'name' => 'public_key',
                            'filters' => ['required'],
                            'type' => 'body',
                        ];
                        $params_map[] = [
                            'name' => 'secret_key',
                            'filters' => ['required'],
                            'type' => 'body',
                        ];
                        $params_map[] = [
                            'name' => 'X_TOKEN',
                            'filters' => ['required'],
                            'type' => 'header',
                        ];
                        break;
                    case 'AuthTokenMiddleware':
                        $params_map[] = [
                            'name' => 'X_TOKEN',
                            'filters' => ['required' => true],
                            'type' => 'header',
                        ];
                        break;
                    case 'OptionalAuthTokenMiddleware':
                        $params_map[] = [
                            'name' => 'X_TOKEN',
                            'filters' => [],
                            'type' => 'header',
                        ];
                        break;
                    case 'GoogleAuthMiddleware':
                        $params_map[] = [
                            'name' => 'ga_code',
                            'filters' => ['minLen' => 6, 'maxLen' => 6],
                            'type' => 'body',
                        ];
                        break;
                }

                $requirement_name = str_replace('Middleware', '', $middleware);
                if ($middleware === DefaultMiddlewares::CORS) {
                    continue;
                }
                $requirements[] = $requirement_name;
            }

            //hack
            if (in_array('ExchangeAuth', $requirements) && !in_array('AuthToken', $requirements)) {
                $requirements[] = 'AuthToken';
            }

            $raw_path_parts = explode('/', substr($route->getRawPath(), 1));

            foreach ($raw_path_parts as $key => $value) {
                if (false !== strpos($value, '%n:')) {
                    if ($key > 0) {
                        $raw_path_parts[$key-1] = $raw_path_parts[$key-1] . ucfirst(str_replace('%n:', '', $raw_path_parts[$key]));
                        unset($raw_path_parts[$key]);
                    } else {
                        $raw_path_parts[$key] = ucfirst(str_replace('%n:', '', $raw_path_parts[$key]));
                    }
                }
            }

            $raw_path = $raw_path_parts[count($raw_path_parts) - 1];
            $method_key = str_replace(
                ' ',
                '',
                ucwords(str_replace(['%n:', '_'], ' ', $raw_path)));
            $method_key = $method_key ?: 'Default';
            $method_name = $method_key;

            $method_key .= ucfirst(strtolower($route->getMethod()));

            $path = array_slice(explode('/', $route->getPath()), 3);

            if (empty($path)) {
                continue;
            }

            $path = implode('/', $path);
            $path = preg_replace_callback('/(%n:.[A-Za-z]+)/', function ($match) {
                preg_match('/%n:(.+)/', $match[0], $matches);
                return sprintf('{%s}', $matches[1]);
            }, $path);

            $pointer[$method_key] = [
                'path' => $path,
                'method' => $route->getMethod(),
                'name' => $method_name,
                'key' => str_replace('%n:', '', $route_unique_key),
                'params' => $params_map,
                'requirements' => $requirements,
            ];

            if ($with_description) {
                $pointer[$method_key]['description'] = self::getDescriptionByMethodKey($route_unique_key, $methods_info);
            }
        }

        return $result;
    }

    private static function transformFilters(array $filters): array {
        $result = [];
        foreach ($filters as $key => $filter) {
            if (is_int($key)) {
                $result[$filter] = true;
            } else {
                $result[$key] = $filter;
            }
        }
        return $result;
    }

    private static function getDescriptionByMethodKey($key, array $methods_info) {
        $description = '';
        foreach ($methods_info as $method_info) {
            /** @var MethodInfoModel $method_info */
            if ($method_info->method_key === $key) {
                $description = $method_info->short_description;
                if (is_null($description)) {
                    $description = $method_info->short_description;
                }
                break;
            }
        }
        return $description;
    }

    public static function prepareMethodInfoData(MethodInfoModel $method_info, Router $router, array $api_keys): array {
        $schema = self::getSchema($router, $api_keys, false);
        $result = [];

        foreach ($schema as $key => $sub_schema) {
            $sub_schema = self::mergeMethodInfoWithRoute($sub_schema, $method_info);
            if (!empty($sub_schema)) {
                $result = $sub_schema;
                break;
            }
        }
        return $result;
    }

    private static function mergeMethodInfoWithRoute(array $schema, MethodInfoModel $method_info): array {
        if (isset($schema['key'])) {
            if ($method_info->method_key === $schema['key']) {
                $method_info_detail = MethodInfoSerializer::detail($method_info);
                $param_descriptions = $method_info_detail['param_descriptions'];
                unset($method_info_detail['param_descriptions']);

                if ($param_descriptions) {
                    $schema['params'] = array_map(function ($param) use ($param_descriptions) {
                        $param['description'] = array_get_val($param_descriptions, $param['name'], '');
                        return $param;
                    }, $schema['params']);
                }
                return array_merge($schema, $method_info_detail);
            }
            return [];
        }

        foreach ($schema as $key => $sub_schema) {
            if ($sub_schema = self::mergeMethodInfoWithRoute($sub_schema, $method_info)) {
                return $sub_schema;
            }
        }

        return [];
    }

    public static function getMethodInfo($key, $lang): MethodInfoModel {
        $method_info = MethodInfoModel::select(Where::and()
            ->set('lang', Where::OperatorEq, getLang())
            ->set('method_key', Where::OperatorEq, $key)
        );

        if ($method_info->isEmpty() && $lang !== LangModule::DEFAULT_LANG) {
            $method_info = MethodInfoModel::select(Where::and()
                ->set('lang', Where::OperatorEq, LangModule::DEFAULT_LANG)
                ->set('method_key', Where::OperatorEq, $key)
            );
        }

        if ($method_info->isEmpty()) {
            $method_info = new MethodInfoModel();
            $method_info->method_key = $key;
            $method_info->lang = $lang;
            $method_info->description = '';
            $method_info->short_description = '';
            $method_info->result_example = '';
            $method_info->result = '';
            $method_info->param_descriptions = '';
        } else {
            $method_info = $method_info->first();
        }

        return $method_info;
    }
}
