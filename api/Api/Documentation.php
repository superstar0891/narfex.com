<?php

namespace Api\Documentation;

use Core\Response\JsonResponse;
use Db\Where;
use Models\PageModel;
use Modules\DocumentationModule;
use Modules\MethodInfoModule;
use Modules\PagesModule;

class Documentation {
    public static function schema() {
        global $api_routes, $kernel;

        $result = DocumentationModule::getDefaultSchema($kernel->getRouter(), $api_routes);
        JsonResponse::ok($result);
    }

    public static function documentationRetrieve($request) {
        global $api_routes, $kernel;
        /* @var $description */
        extract($request['params']);
        $schema = DocumentationModule::getSchema($kernel->getRouter(), $api_routes, (bool) $description);

        $pages = PageModel::select(Where::equal('is_api_page', 1));
        $static_pages = PagesModule::getStaticPagesList($pages, getLang());
        $welcome_pages = PageModel::select(Where::and()
            ->set('is_api_page', Where::OperatorEq, 1)
            ->set('url', Where::OperatorEq, PageModel::WELCOME_PAGE_URL)
        );

        if ($welcome_pages->isEmpty()) {
            $welcome_page = current($static_pages);
        } else {
            $welcome_page = current(PagesModule::getStaticPagesList($welcome_pages, getLang(), true));
        }

        $result = [
            'schema' => $schema,
            'welcome_page' => $welcome_page,
            'static_pages' => $static_pages
        ];

        JsonResponse::ok($result);
    }

    public static function methodInfoRetrieve($request) {
        global $api_routes, $kernel;
        /** @var string $key */
        extract($request['params']);

        $method_info = DocumentationModule::getMethodInfo($key, getLang());
        $result = DocumentationModule::prepareMethodInfoData($method_info, $kernel->getRouter(), $api_routes);

        if (empty($result)) {
            JsonResponse::pageNotFoundError();
        }
        JsonResponse::ok($result);
    }

    public static function saveMethodInfo($request) {
        global $api_routes, $kernel;
        /* @var string $key
         * @var mixed $description
         * @var mixed $short_description
         * @var mixed $result
         * @var mixed $result_example
         * @var mixed $param_descriptions
         */
        extract($request['params']);
        $method_info = MethodInfoModule::storeOrUpdateMethodInfo(
            $key,
            $short_description,
            $description,
            $result,
            $result_example,
            $param_descriptions
        );

        $result = DocumentationModule::prepareMethodInfoData($method_info, $kernel->getRouter(), $api_routes);

        if (empty($result)) {
            $method_info->delete();
            JsonResponse::pageNotFoundError();
        }

        JsonResponse::ok($result);
    }
}
