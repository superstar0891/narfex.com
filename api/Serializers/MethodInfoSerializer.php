<?php

namespace Serializers;

use Models\MethodInfoModel;

class MethodInfoSerializer {
    public static function detail(MethodInfoModel $method_nfo): array {
        return [
            'key' => $method_nfo->method_key,
            'lang' => $method_nfo->lang,
            'short_description' =>  $method_nfo->short_description,
            'description' =>  json_decode($method_nfo->description, true),
            'result' =>  json_decode($method_nfo->result, true),
            'result_example' =>  json_decode($method_nfo->result_example, true),
            'param_descriptions' => json_decode($method_nfo->param_descriptions, true),
        ];
    }
}
