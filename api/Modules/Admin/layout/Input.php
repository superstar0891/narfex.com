<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class Input extends Layout {
    use ValidationParametersTrait;

    private $name;
    private $placeholder;
    private $value = '';
    private $indicator = '';
    private $multi_line = false;
    private $title = '';

    public static function withParams(string $name, string $placeholder, string $value = '', string $indicator = '', string $title = ''): Input {
        $inst = new Input();
        $inst->setName($name);
        $inst->setPlaceholder($placeholder);
        $inst->setValue($value);
        $inst->setIndicator($indicator);
        $inst->setTitle($title);
        return $inst;
    }

    public function setTitle(string $title): Input {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    public function setName(string $name): Input {
        $this->name = $name;
        return $this;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getPlaceholder(): string {
        return $this->placeholder;
    }

    public function setPlaceholder(string $placeholder): Input {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function getValue(): string {
        return $this->value;
    }

    public function setValue(string $value): Input {
        $this->value = $value;
        return $this;
    }

    public function setIndicator(string $indicator): Input {
        $this->indicator = $indicator;
        return $this;
    }

    public function getIndicator(): string {
        return $this->indicator;
    }

    public function setMultiLine(bool $multi_line): Input{
        $this->multi_line = $multi_line;
        return $this;
    }

    public function getMultiLine(): bool {
        return $this->multi_line;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::input($this);
    }
}
