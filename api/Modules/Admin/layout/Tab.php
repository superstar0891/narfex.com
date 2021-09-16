<?php

namespace Admin\layout;

use Admin\helpers\PageContainer;
use Serializers\AdminSerializer;

class Tab extends Layout {

    private $id;
    private $title;
    private $render_fn;

    /** @var array  */
    private $params = [];

    /* @var Action $action */
    private $action;

    public static function withParams(string $title, array $params = []): Tab {
        $inst = new Tab();
        $inst->setTitle($title);
        $inst->setParams($params);
        return $inst;
    }

    public function setTitle(string $title): Tab {
        $this->title = $title;
        return $this;
    }

    public function setParams(array $params): Tab {
        $this->params = $params;
        return $this;
    }

    public function getParams(): array {
        return $this->params;
    }

    public function setRenderer(\Closure $render_fn): Tab {
        $this->render_fn = $render_fn;
        return $this;
    }

    public function getRenderer(): \Closure {
        return $this->render_fn;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function callRenderer(PageContainer $parent): self {
        $layout = $this->render_fn->call($parent, $this->params);
        if (is_array($layout)) {
            $this->addItem(...$layout);
        } else {
            $this->addItem($layout);
        }
        return $this;
    }

    public function setId(string $id): Tab {
        $this->id = $id;
        return $this;
    }

    public function setAction(Action $action): self {
        $this->action = $action;
        return $this;
    }

    public function getId(): string {
        return $this->id;
    }

    public function serialize(array $items = []): array {
        $params = array_merge(['tab_id' => $this->id], $this->params);
        return AdminSerializer::tabsItem($this->id, $this->title, [
            'action' => $this->action->use($params)->serialize(),
        ], count($items) ? $items : null);
    }
}
