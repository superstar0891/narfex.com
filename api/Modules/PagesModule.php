<?php

namespace Modules;

use Db\Model\ModelSet;
use Db\Where;
use Models\PageModel;
use Serializers\PageSerializer;

class PagesModule {
    public static function prepareStaticPages(ModelSet $pages, string $defaultLang = 'en'): array {
        $result = [];

        foreach ($pages as $page) {
            /** @var PageModel $page */
            if (!isset($result[$page->url])) {
                $result[$page->url] = PageSerializer::moreDetail($page);
            } else {
                $currentLang = $result[$page->url]['lang'];

                if ($currentLang === $defaultLang) {
                    continue;
                }

                if ($currentLang === 'en') {
                    continue;
                }

                if (in_array($page->lang, [$defaultLang, 'en'], true)) {
                    $result[$page->url] = PageSerializer::moreDetail($page);
                }
            }
        }

        return $result;
    }

    public static function getStaticPageDetail(ModelSet $pages, string $defaultLang = 'en') {
        $pageList = PagesModule::prepareStaticPages($pages, $defaultLang);

        $result = array_map(function(array $page_details) {
            $page = new PageModel();
            $page->title = $page_details['title'];
            $page->content = $page_details['content'];
            $page->meta_description = $page_details['meta_description'];
            $page->meta_keyword = $page_details['meta_keyword'];
            return PageSerializer::detail($page);
        }, $pageList);

        return current($result);
    }

    public static function getStaticPagesList(ModelSet $pages, string $defaultLang = 'en', $full = false): array {
        $staticPages = self::prepareStaticPages($pages, $defaultLang);

        $result = [];
        foreach ($staticPages as $page) {
            if ($full) {
                $page['content'] = json_decode($page['content'], true);
                $result[] = $page;
                continue;
            }
            $result[] = [
                'url' => $page['url'],
                'title' => $page['title'],
            ];
        }

        return $result;
    }

    public static function storeOrUpdate(
        string $address,
        string $title,
        $content,
        string $lang = 'en',
        $meta_description = null,
        $meta_keywords = null): PageModel {
        $page = PageModel::select(Where::and()
            ->set('url', Where::OperatorEq, $address)
            ->set('lang', Where::OperatorEq, getLang())
        );

        if ($page->isEmpty()) {
            $page = new PageModel();
        } else {
            $page = $page->first();
        }

        /** @var PageModel $page */
        $page->url = $address;
        $page->lang = $lang;
        $page->title = $title;
        $page->content = json_encode($content);
        $page->header = 0;
        $page->category = 'global';

        if (is_string($meta_description)) {
            $page->meta_description = $meta_description;
        }
        if (is_string($meta_keywords)) {
            $page->meta_keyword = $meta_keywords;
        }

        $page->save();

        return $page;
    }

}