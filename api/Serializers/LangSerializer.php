<?php


namespace Serializers;


class LangSerializer {
    public static function listItem(string $name, ?string $value) {
        return [
            'name' => $name,
            'value' => $value
        ];
    }
}
