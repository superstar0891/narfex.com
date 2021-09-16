<?php

namespace Admin\layout;

class Form extends Layout {
    public static function withItems(Layout ...$items): Form {
        $inst = new Form();
        $inst->addItem(...$items);
        return $inst;
    }

    public function serialize(array $items = []): array {
        return $items;
    }
}
