<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class Select extends Layout {
    use ValidationParametersTrait;

    private $name;
    private $placeholder;
    private $title;
    private $value;
    private $options;
    private $multiple = false;
    private $empty_result = null;

    public static function withParams(string $name, string $placeholder, array $options = [],  $value = '', $title = ''): Select {
        $inst = new Select();
        $inst->setName($name);
        $inst->setPlaceholder($placeholder);
        $inst->setTitle($title);
        $inst->setValue($value);
        $inst->setOptions($options);
        return $inst;
    }

    public function setName(string $name): Select {
        $this->name = $name;
        return $this;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setPlaceholder(string $placeholder): Select {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function getPlaceholder(): string {
        return $this->placeholder;
    }

    public function setValue($value): Select {
        $this->value = $value;
        return $this;
    }


    public function getValue() {
        return $this->value;
    }

    public function setOptions(array $options): Select {
        $this->options = $options;
        return $this;
    }

    public function getOptions(): array {
        return $this->options;
    }

    public function serialize(array $items = []): array {
        $options = [];
        foreach ($this->options as $key => $option) {
            $options[] = [
                'label' => $option,
                'value' => $key
            ];
        }

        $this->options = $options;

        return AdminSerializer::select($this);
    }

    public function isMultiple(): bool {
        return $this->multiple;
    }

    public function setMultiple(bool $multiple): Select {
        $this->multiple = $multiple;
        return $this;
    }

    public function getEmptyResult() {
        return $this->empty_result;
    }

    public function setEmptyResult($empty_result): Select {
        $this->empty_result = $empty_result;
        return $this;
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

    public function getTitle(): string {
        return $this->title;
    }

    public function setTitle($title): Select {
        $this->title = $title;
        return $this;
    }

}
