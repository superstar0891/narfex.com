<?php

namespace Api\Lang;

use Api\Errors;
use Core\Response\JsonResponse;
use Core\Services\Redis\RedisAdapter;
use Db\Where;
use Models\AppLangModel;
use Models\LangsModel;
use Models\SiteLangModel;
use Modules\LangModule;
use Serializers\ErrorSerializer;

class Lang {
    public static function retrieve($request) {
        /* @var string $code */
        extract($request['params']);

        $cache_key = 'lang_cache_' . $code;
        $cache = RedisAdapter::shared()->get($cache_key);

        if ($code !== 'en') {
            $en_cache = RedisAdapter::shared()->get('lang_cache_en');
        } else {
            $en_cache = $cache;
        }

        if ($cache && $code === 'en') {
            $result = json_decode($cache, true);
        } else if ($cache && $en_cache) {
            $result = json_decode($cache, true);
            $result += json_decode($en_cache, true);
        } else {
            $codes = [$code];
            if ($code !== 'en') {
                $codes[] = 'en';
            }

            $lang = LangsModel::select(
                Where::and()
                    ->set(Where::in('lang', $codes))
                    ->set(Where::equal('type', LangsModel::WEB_LANG))
            );
            $lang_map = [];

            /* @var SiteLangModel $row */
            foreach ($lang as $row) {
                if (!isset($lang_map[$row->lang])) {
                    $lang_map[$row->lang] = [];
                }

                $lang_map[$row->lang][$row->name] = $row->value;
            }

            $result = isset($lang_map[$code]) ? $lang_map[$code] : [];
            if ($code !== 'en') {
                $result += $lang_map['en'];
            }

            if (!empty($result)) {
                if ($code !== 'en') {
                    RedisAdapter::shared()->set('lang_cache_en', json_encode($lang_map['en']), 86400);
                }

                RedisAdapter::shared()->set($cache_key, json_encode($result), 86400);
            }
        }

        JsonResponse::ok([
            'translations' => $result,
            'languages' => LangModule::languages(),
        ]);
    }

    public static function appExportRetrieve() {
        $lang = AppLangModel::select();
        $lang_map = [];

        /* @var AppLangModel $row */
        foreach ($lang as $row) {
            if (!isset($lang_map[$row->lang])) {
                $lang_map[$row->lang] = [];
            }

            $lang_map[$row->lang][$row->name] = $row->value;
        }

        JsonResponse::ok($lang_map);
    }

    public static function edit($request) {
        /**
         * @var string $code
         * @var string $key
         * @var string $value
         */
        extract($request['params']);

        $lang = SiteLangModel::select(Where::and()
            ->set('lang', Where::OperatorEq, $code)
            ->set('name', Where::OperatorEq, $key)
        );

        if ($lang->isEmpty()) {
            JsonResponse::pageNotFoundError();
        }

        $lang = $lang->first();
        /**
         * @var $lang SiteLangModel
         */
        $lang->value = $value;

        RedisAdapter::shared()->del('lang_cache_' . $code);

        try {
            $lang->save();
            JsonResponse::ok();
        } catch (\Exception $e) {
            JsonResponse::error($e->getMessage());
        }
    }
}
