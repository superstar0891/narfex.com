<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class ActionSheetItem extends Layout {

    const TYPE_DEFAULT = 'default';
    const TYPE_DESTRUCTIVE = 'destructive';

    private $title;
    private $type = self::TYPE_DEFAULT;

    /* @var Action $onClick */
    private $onClick = null;

    public static function withParams(string $title, string $type = self::TYPE_DEFAULT): ActionSheetItem {
        $instance = new ActionSheetItem();
        $instance->setTitle($title);
        $instance->setType($type);
        return $instance;
    }

    public function setTitle(string $title): ActionSheetItem {
        $this->title = $title;
        return $this;
    }

    public function setType(string $type): ActionSheetItem {
        $this->type = $type;
        return $this;
    }

    public function onClick(Action $action): ActionSheetItem {
        $this->onClick = $action;
        return $this;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::actionSheetItem($this->title, $this->type, [
            'action' => $this->onClick ? $this->onClick->serialize() : null,
        ]);
    }
}
