<?php

require_once 'include.php';

$used_keys = file_get_contents('keys.txt');
$used_keys = explode(',', $used_keys);

$untranslated_keys = [];

$langs = \Models\LangTestModel::queryBuilder()
    ->groupBy('lang')
    ->columns(['lang'], true)
    ->select();

$available_languages = array_map(function($item){
    return $item['lang'];
}, $langs);

//foreach ($available_languages as $language) {
//    $language_items = \Models\LangTestModel::select(\Db\Where::equal('lang', $language));
//    foreach ($language_items as $item) {
//        /** @var \Models\LangTestModel $item */
//        if (in_array($item->name, $used_keys)) {
//            $lang_model = new \Models\LangsModel();
//            $lang_model->key = $item->name;
//            $lang_model->value = $item->value;
//            $lang_model->lang = $language;
//            $lang_model->save();
//        }
//    }
//}

$translations = \Models\LangTestModel::select();
$translated_keys = $translations->column('name');
$translated_keys = array_unique($translated_keys);

foreach ($used_keys as $key) {
    if (!in_array($key, $translated_keys)) {
        $translate = new \Models\LangsModel();
        $translate->name = $key;
        $translate->lang = 'en';
        $translate->value = 'Need to translate';
        $translate->save();
    }
}
var_dump($untranslated_keys);
