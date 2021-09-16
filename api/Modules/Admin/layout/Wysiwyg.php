<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class Wysiwyg extends Layout {
    use ValidationParametersTrait;

    private $name;
    private $title;
    private $value;

    public static function withParams(string $name, string $title, string $value = ''): Wysiwyg {
        $inst = new Wysiwyg();
        $inst->setName($name);
        $inst->setTitle($title);
        $inst->setValue($value);
        return $inst;
    }

    public function setName(string $name): Wysiwyg {
        $this->name = $name;
        return $this;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setTitle(string $title): Wysiwyg {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function setValue(string $value): Wysiwyg {
        $this->value = $value;
        return $this;
    }

    public function getValue(): string {
        return $this->value;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::wysiwyg($this);
    }
}
