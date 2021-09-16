<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class Time extends Layout {
    private $time;

    public static function withParams(int $time = null): Time {
        $instance = new Time();
        $instance->setTime($time);
        return $instance;
    }

    public function setTime(int $time = null) {
        $this->time = $time;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::time($this->time);
    }
}
