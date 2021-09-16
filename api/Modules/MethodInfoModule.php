<?php

namespace Modules;

use Db\Where;
use Models\MethodInfoModel;

class MethodInfoModule {
    public static function storeOrUpdateMethodInfo(
        string $key,
        $short_description,
        $description,
        $result,
        $result_example,
        $param_descriptions) : MethodInfoModel {
        $lang = getLang();
        $method_info = MethodInfoModel::select(
            Where::and()
                ->set('lang', Where::OperatorEq, $lang)
                ->set('method_key', Where::OperatorEq, $key));

        if ($method_info->isEmpty()) {
            $method_info = new MethodInfoModel();
        } else {
            /** @var MethodInfoModel $method_info */
            $method_info = $method_info->first();
        }

        $method_info->lang = $lang;
        $method_info->method_key = $key;
        if (is_string($short_description)) {
            $method_info->short_description = $short_description;
        }
        $method_info->description = json_encode($description);
        $method_info->result = json_encode($result);
        $method_info->result_example = json_encode($result_example);
        $method_info->param_descriptions = json_encode($param_descriptions);
        $method_info->save();

        return $method_info;
    }

}
