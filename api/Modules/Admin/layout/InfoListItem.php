<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class InfoListItem extends Layout {
    private $label;

    public static function withParams(string $label, $value): InfoListItem {
        $instance = new InfoListItem();
        $instance->setLabel($label);

        if (is_null($value) || $value === false) {
            $value = '';
        }

        if (!($value instanceof Layout)) {
            $value = Text::withParams($value);
        }

        $instance->addItem($value);

        return $instance;
    }

    public function setLabel(string $label): InfoListItem {
        $this->label = $label;
        return $this;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::listItem($this->label, ...$items);
    }
}
