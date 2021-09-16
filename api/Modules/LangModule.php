<?php

namespace Modules;

use Db\Model\ModelSet;
use Db\Where;
use Models\LangsModel;

class LangModule {
    const DEFAULT_LANG = 'en';

    public static $lang = null;

    public static function load($code = self::DEFAULT_LANG) {

        $result = self::loadLang($code);

        if ($code !== self::DEFAULT_LANG) {
            $result += self::loadLang(self::DEFAULT_LANG);
        }

        self::$lang = $result;
    }

    private static function loadLang($code = self::DEFAULT_LANG): array {
        try {
            $lang = LangsModel::select(
                Where::and()
                    ->set(Where::equal('lang', $code))
                    ->set(Where::equal('type', LangsModel::BACKEND_LANG))
            );
        } catch (\Exception $e) {
            return [];
        }
        $result = [];
        foreach ($lang as $item) {
            $result[$item->name] = $item->value;
        }
        return $result;
    }

    public static function languages(): array {
        return [
            ['en', 'English'],
            ['ru', 'Русский'],
            ['ar', 'Arab'],
            ['bn', 'Bengali'],
            ['cs', 'Czech'],
            ['de', 'Deutsch'],
            ['es', 'Spanish'],
            ['fr', 'French'],
            ['hi', 'Hindi'],
            ['id', 'Indonesian'],
            ['it', 'Italian'],
            ['ja', 'Japanese'],
            ['ko', 'Korean'],
            ['ms', 'Malay'],
            ['nl', 'Dutch'],
            ['pl', 'Polish'],
            ['pt', 'Portuguese'],
            ['tr', 'Turkish'],
            ['ua', 'Ukrainian'],
            ['zh', 'Chinese'],
            ['fa', 'Farsi'],
        ];
    }

    public static function getTranslationByKey(string $key, ModelSet $langs): ?LangsModel {
        $filter = $langs->filter(function(LangsModel $model) use ($key) {
            return $model->name === $key;
        });

        if ($filter->isEmpty()) {
            return null;
        }

        return $filter->first() ?? null;
    }
}
