<?php

namespace Api\Pages;

use Api\Errors;
use Core\Response\JsonResponse;
use Db\Where;
use Models\PageModel;
use Modules\PagesModule;
use Serializers\ErrorSerializer;
use Serializers\PageSerializer;

class Pages {
    public static function retrieve($request) {
        /* @var string $address */
        extract($request['params']);

        $page = PageModel::select(Where::and()
            ->set('url', Where::OperatorEq, $address)
            ->set('lang', Where::OperatorEq, getLang())
        );

        if ($page->isEmpty()) {
            $page = PageModel::select(Where::equal('url', $address));

            if ($page->isEmpty()) {
                JsonResponse::pageNotFoundError();
            }

            $result = PagesModule::getStaticPageDetail($page, getLang());
        } else {
            $result = PageSerializer::detail($page->first());
        }

        JsonResponse::ok($result);
    }

    public static function editStaticPage($request) {
        /* @var string $address
         * @var string $title
         * @var string $content
         * @var string $meta_description
         * @var string $meta_keywords
         */
        extract($request['params']);

        $page = PagesModule::storeOrUpdate($address, $title, $content, getLang(), $meta_description, $meta_keywords);

        JsonResponse::ok(PageSerializer::detail($page));
    }

}
