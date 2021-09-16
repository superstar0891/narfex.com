<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class Group extends Layout {
    public static function withItems(Layout ...$items): Group {
        $instance = new Group();
        $instance->addItem(...$items);
        return $instance;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::group(...$items);
    }
}
