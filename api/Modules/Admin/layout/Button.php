<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class Button extends Layout {

    const TYPE_PRIMARY = 'primary';
    const TYPE_SECONDARY = 'secondary';
    const TYPE_OUTLINE = 'outline';

    const SIZE_SMALL = 'small';
    const SIZE_MIDDLE = 'middle';
    const SIZE_LARGE = 'large';

    private $title = 'Block';
    private $type = self::TYPE_PRIMARY;
    private $size = self::SIZE_MIDDLE;

    /* @var Action $onClick */
    private $onClick = null;

    public static function withParams(string $title, string $type = Button::TYPE_PRIMARY, string $size = self::SIZE_MIDDLE): Button {
        $instance = new Button();
        $instance->setTitle($title);
        $instance->setType($type);
        $instance->setSize($size);
        return $instance;
    }

    public function setTitle(string $title): Button {
        $this->title = $title;
        return $this;
    }

    public function setType(string $type): Button {
        $this->type = $type;
        return $this;
    }

    public function setSize(string $size): Button {
        $this->size = $size;
        return $this;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::button($this->title, $this->type, $this->size, [
            'action' => $this->onClick ? $this->onClick->serialize() : null,
        ]);
    }

    public function onClick(Action $action): Button {
        $this->onClick = $action;
        return $this;
    }
}
