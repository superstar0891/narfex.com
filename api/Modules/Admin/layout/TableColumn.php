<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class TableColumn extends Layout {

    private $sub_value = '';

    public static function withParams($value, string $sub_value = ''): TableColumn {
        $inst = new TableColumn();
        $inst->setValue($value);
        $inst->setSubValue($sub_value);
        return $inst;
    }

    public function setSubValue(string $sub_value) {
        $this->sub_value = $sub_value;
    }

    public function setValue($value) {
        if (is_array($value)) {
            $this->addItem(...$value);
            return;
        }
        if (!($value instanceof Layout)) {
            $value = Text::withParams($value);
        }
        $this->addItem($value);
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::tableColumn($items, $this->sub_value);
    }
}
