<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class Json extends Layout {
    private $payload;

    public static function withPayload($payload): Json {
        $instance = new Json();
        $instance->setPayload($payload);
        return $instance;
    }

    public function setPayload($payload) {
        $this->payload = $payload;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::json(json_encode($this->payload));
    }
}
