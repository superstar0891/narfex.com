<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class Clipboard extends Layout {
    /** @var string */
    private $text;

    /** @var int */
    private $length;

    public static function withParams(string $text, int $length = null): Clipboard {
        $instance = new Clipboard();
        $instance->setText($text);
        $instance->setLength($length);
        return $instance;
    }

    public function setText(string $text) {
        $this->text = $text;
    }

    public function setLength(int $length = null) {
        $this->length = $length;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::clipboard($this->text, $this->length);
    }
}
