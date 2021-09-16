<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class ActionSheet extends Layout {

    private $title = '';

    public static function withItems(Layout ...$actions): ActionSheet {
        $instance = new ActionSheet();
        $instance->addItem(...$actions);
        return $instance;
    }

    public function setTitle(string $title) {
        $this->title = $title;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::actionSheet($this->title, $items);
    }
}
