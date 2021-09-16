<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class TableRow extends Layout {
    const STYLE_DEFAULT = 'default';
    const STYLE_DANGER = 'danger';
    const STYLE_WARNING = 'warning';
    const STYLE_SUCCESS = 'success';
    const STYLE_DESTROYED = 'destroyed';
    const STYLE_ACCENT = 'accent';

    private $style = self::STYLE_DEFAULT;

    public static function withParams(...$items): TableRow {
        $inst = new TableRow();
        foreach ($items as $item) {
            if ($item instanceof ActionSheet) {
                $inst->addItem(TableColumn::withParams($item));
            } elseif ($item instanceof TableColumn) {
                $inst->addItem($item);
            } else {
                $inst->addItem(TableColumn::withParams($item));
            }
        }
        return $inst;
    }

    public function setStyle(string $style): TableRow {
        $this->style = $style;

        return $this;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::tableRow(md5(serialize($items)), $this->style, ...$items);
    }

    public function success(): TableRow {
        return $this->setStyle(self::STYLE_SUCCESS);
    }

    public function danger(): TableRow {
        return $this->setStyle(self::STYLE_DANGER);
    }

    public function warning(): TableRow {
        return $this->setStyle(self::STYLE_WARNING);
    }

    public function destroyed(): TableRow {
        return $this->setStyle(self::STYLE_DESTROYED);
    }

    public function accent(): TableRow {
        return $this->setStyle(self::STYLE_ACCENT);
    }
}
