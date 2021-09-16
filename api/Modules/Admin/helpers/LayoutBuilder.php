<?php

namespace Admin\helpers;

use Admin\layout\Layout;

class LayoutBuilder {
    /* @var []Layout $items */
    private $items = [];

    public function __construct() {

    }

    public function push(Layout $layout): self {
        $this->items[] = $layout;
        return $this;
    }

    private function processItems($items): array {
        $result = [];
        /* @var Layout $item */
        foreach ($items as &$item) {
            $result[] = $item->serialize($this->processItems($item->getItems()));
        }

        return $result;
    }

    public function build() {
        return $this->processItems($this->items);
    }
}
