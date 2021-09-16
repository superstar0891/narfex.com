<?php

namespace Serializers;

use Models\PageModel;

class PageSerializer {
    public static function detail(PageModel $page): array {
        $content = json_decode($page->content, true);
        if (is_null($content)) {
            $content = $page->content;
        }
        return [
            'title' => $page->title,
            'content' => $content,
            'meta_description' => $page->meta_description,
            'meta_keyword' => $page->meta_keyword,
        ];
    }

    public static function moreDetail(PageModel $page): array {
        return [
            'url' => $page->url,
            'lang' => $page->lang,
            'title' => $page->title,
            'content' => $page->content,
            'meta_description' => $page->meta_description,
            'meta_keyword' => $page->meta_keyword,
        ];
    }

    public static function listItem(PageModel $page) {
        return [
            'url' => $page->url,
            'title' => $page->title,
        ];
    }
}
