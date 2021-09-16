<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class InfoList extends Layout {
    public static function withItems(Layout ...$items): InfoList {
        $instance = new InfoList();
        $instance->addItem(...$items);
        return $instance;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::list(...$items);
    }
}
