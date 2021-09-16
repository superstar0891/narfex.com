<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class Checkbox extends Layout {
    private $name;
    private $title;
    private $value;

    public static function withParams(string $name, string $title, string $value = ''): Checkbox {
        $inst = new Checkbox();
        $inst->setName($name);
        $inst->setTitle($title);
        $inst->setValue($value);
        return $inst;
    }

    public function setName(string $name): Checkbox {
        $this->name = $name;
        return $this;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setTitle(string $title): Checkbox {
        $this->title = $title;
        return $this;
    }

    public function setValue(string $value): Checkbox {
        $this->value = $value;
        return $this;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::checkbox($this->name, $this->title, $this->value);
    }
}
