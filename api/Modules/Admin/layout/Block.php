<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class Block extends Layout {
    private $title = 'Block';

    public static function withParams(string $title, Layout ...$items): Block {
        $instance = new Block();
        $instance->setTitle($title);
        $instance->addItem(...$items);
        return $instance;
    }

    public function setTitle(string $title) {
        $this->title = $title;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::block($this->title, ...$items);
    }
}
