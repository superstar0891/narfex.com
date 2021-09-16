<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class DropDown extends Layout {
    use ValidationParametersTrait;

    private $name;
    private $placeholder;
    private $value;
    private $options;

    public static function withParams(string $name, string $placeholder, array $options = [], string $value = ''): DropDown {
        $inst = new DropDown();
        $inst->setName($name);
        $inst->setPlaceholder($placeholder);
        $inst->setValue($value);
        $inst->setOptions($options);
        return $inst;
    }

    public function setName(string $name): DropDown {
        $this->name = $name;
        return $this;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setPlaceholder(string $placeholder): DropDown {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function getPlaceholder(): string {
        return $this->placeholder;
    }

    public function setValue(string $value): DropDown {
        $this->value = $value;
        return $this;
    }

    public function getValue(): string {
        return $this->value;
    }

    public function setOptions(array $options): DropDown {
        $this->options = $options;
        return $this;
    }

    public function getOptions(): array {
        return $this->options;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::dropDown($this);
    }

    public function prepareOptions(): array {
        return array_map(function ($row) {
            if (isset($row['label'])) {
                return $row;
            } else {
                return ['label' => $row[1], 'value' => $row[0]];
            }
        }, $this->getOptions());
    }

}
