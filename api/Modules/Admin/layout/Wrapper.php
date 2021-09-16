<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class Wrapper extends Layout {

    private $title;

    public static function withParams(string $title, Layout ...$content): Wrapper {
        $inst = new Wrapper();
        $inst->setTitle($title);
        $inst->addItem(...$content);
        return $inst;
    }

    public function setTitle(string $title): Wrapper {
        $this->title = $title;
        return $this;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::wrapper(md5($this->title), $this->title, ...$items);
    }
}
