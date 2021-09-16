<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class Tabs extends Layout {
    public static function withItems(Layout ...$items): Tabs {
        $inst = new Tabs();
        $inst->addItem(...$items);
        return $inst;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::tabs(...$items);
    }
}
