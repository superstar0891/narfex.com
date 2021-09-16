<?php

namespace Admin\helpers;

use Admin\layout\Layout;
use Admin\layout\Tab;
use Admin\layout\Tabs;
use Opis\Closure\SerializableClosure;

class TabsManager {
    /* @var PageContainer $container */
    private $container;

    private $action;
    private $tabs = [];
    private $active_tab = null;

    public function __construct(PageContainer $container) {
        $this->container = $container;

        $self = $this;
        $this->action = $container->createAction(function (ActionRequest $request) use ($self, $container) {
            $tab_id = $request->getParam('tab_id');
            $params = $request->getParams();
            $tab = $self->getTabs()[$tab_id];
            unset($params['tab_id']);
            $tab->setParams($params);
            return $container->setTabContent($tab);
        });
    }

    public function getTabs(): array {
        return $this->tabs;
    }

    public function setParams($params) {
        $this->tabs = array_map(function (Tab $tab) use ($params) {
            return $tab->setParams($params);
        }, $this->tabs);
    }

    public function setTabs(Layout ...$tabs) {

        /* @var Tab $tab */
        foreach ($tabs as $tab) {
            $wrapper = new SerializableClosure($tab->getRenderer());
            $reflector = $wrapper->getReflector();
            $id = md5($tab->getTitle() . $reflector->getCode());
            $tab->setId($id)->setAction($this->action);
            $this->tabs[$id] = $tab;

            if ($this->active_tab === null) {
                $this->active_tab = $id;
            }
        }

        return $this;
    }

    public function build(): Layout {
        $this->tabs[$this->active_tab]->callRenderer($this->container);

        return Tabs::withItems(...array_values($this->tabs));
    }
}
