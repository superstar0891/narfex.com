<?php

namespace Admin\layout;

abstract class Layout {
    /* @var []Layout $items */
    private $items = [];

    public function serialize(array $items = []): array {
        throw new \Exception('Layout::serialize method not implemented');
    }

    public function addItem(Layout ...$items) {
        $this->items = array_merge($this->items, $items);
    }

    /* @return []Layout */
    public function getItems() {
        return $this->items;
    }
}
