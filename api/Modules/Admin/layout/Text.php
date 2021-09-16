<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class Text extends Layout {
    private $text;

    public static function withParams(string $text): Text {
        $instance = new Text();
        $instance->setText($text);
        return $instance;
    }

    public function setText(string $text) {
        $this->text = $text;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::text($this->text);
    }
}
