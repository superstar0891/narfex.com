<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class Image extends Layout {
    private $content;

    public static function withParams(string $content): Image {
        $instance = new Image();
        $instance->setContent($content);
        return $instance;
    }

    public function setContent(string $content) {
        $this->content = $content;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::image($this->content);
    }
}
