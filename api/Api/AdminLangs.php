<?php


namespace Api\AdminLangs;

use Core\Response\JsonResponse;
use Core\Services\Redis\RedisAdapter;
use Db\Transaction;
use Db\Where;
use Models\LangsModel;
use Modules\LangModule;

class AdminLangs {
    public static function get($request) {
        /**
         * @var string $lang
         * @var string $type
         * @var string $name
         */
        extract($request['params']);

        $where = Where::and()
            ->set(Where::equal('lang', $lang))
            ->set(Where::equal('type', $type));

        if ($name) {
            $where->set(Where::equal('name', $name));
        }

        $existing_keys = LangsModel::queryBuilder()
            ->where(
                Where::and()
                    ->set(Where::equal('type', $type))
                    ->set('deleted_at', Where::OperatorIs, null)
            )
            ->orderBy(['id' => 'DESC'])
            ->select();

        $existing_keys = LangsModel::rowsToSet($existing_keys)->column('name');
        $existing_keys = array_unique($existing_keys);

        $langs = LangsModel::queryBuilder()
            ->where($where)
            ->select();
        $langs = LangsModel::rowsToSet($langs);

        $english_langs = LangsModel::select(
            Where::and()
                ->set(Where::equal('lang', 'en'))
                ->set(Where::equal('type', $type))
                ->set(Where::in('name', $existing_keys))
        );

        $response = [];

        foreach ($existing_keys as $key) {
            $en_translation = LangModule::getTranslationByKey($key, $english_langs);
            $translation = LangModule::getTranslationByKey($key, $langs);

            $response[] = [
                'name' => $key,
                'en_value' => $en_translation ? $en_translation->value : null,
                'value' => $translation ? $translation->value : null,
                'id' => $translation ? $translation->id : null,
                'type' => $type,
                'created_at' => $translation ? (int) $translation->created_at_timestamp : null,
                'updated_at' => $translation ? (int) $translation->updated_at_timestamp : null,
            ];
        }

        JsonResponse::ok($response);
    }

    public static function save($request) {
        /**
         * @var array $items
         */
        extract($request['params']);

        $bad_keys = [];

        Transaction::wrap(function() use ($items, &$bad_keys) {

            $langs = [];
            foreach ($items as $item) {
                if (!isset($item['name']) || !isset($item['value']) || !isset($item['lang']) || !isset($item['type'])) {
                    $bad_keys[] = [
                        'name' => $item['name'] ?? null,
                        'type' => $item['type'] ?? null,
                        'lang' => $item['lang'] ?? null,
                    ];
                    continue;
                }

                /** @var LangsModel $model */
                $model = LangsModel::first(
                    Where::and()
                        ->set(Where::equal('name', $item['name']))
                        ->set(Where::equal('lang', $item['lang']))
                        ->set(Where::equal('type', $item['type'])), false
                );

                $langs[$item['lang']] = true;

                if (!$model) {
                    $model = new LangsModel();
                    $model->type = $item['type'];
                    $model->name = $item['name'];
                    $model->lang = $item['lang'];
                }
                $model->value = $item['value'];
                $model->save();
            }

            foreach ($langs as $lang_name => $_) {
                RedisAdapter::shared()->del('lang_cache_' . $lang_name);
            }
        });

        if ($bad_keys) {
            JsonResponse::ok(['unsuccessful_items' => $bad_keys]);
        }

        JsonResponse::ok();
    }

    public static function delete($request) {
        /**
         * @var string $name
         * @var string $type
         */
        extract($request['params']);

        $langs = LangsModel::select(
            Where::and()
                ->set(Where::equal('name', $name))
                ->set(Where::equal('type', $type))
        );

        if ($langs->isEmpty()) {
            JsonResponse::apiError();
        }

        foreach ($langs as $lang) {
            /** @var LangsModel $lang */
            $lang->delete(true);
        }

        JsonResponse::ok();
    }
}
