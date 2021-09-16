<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class Title extends Layout {
    /** @var string */
    private $title;
    /** @var int */
    private $level;

    public static function withParams(string $title, int $level = 2): self {
        $instance = new self();
        $instance->setTitle($title);
        $instance->setLevel($level);
        return $instance;
    }

    public function setTitle(string $title) {
        $this->title = $title;
    }

    public function setLevel(int $level) {
        $this->level = $level;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::title($this->title, $this->level);
    }
}
