<?php

namespace Admin\helpers;

use Admin\layout\Action;
use Admin\layout\Button;
use Admin\layout\Form;
use Admin\layout\Layout;
use Opis\Closure\SerializableClosure;

class FormManager {

    private $items;
    private $params = [];
    private $submit_button = 'Submit';
    /**
     * @var Action $action
     */
    private $action;

    /* @var \Closure $on_submit */
    private $on_submit;

    /* @var PageContainer $container */
    private $container;

    public function __construct(PageContainer $container) {
        $this->container = $container;
    }

    public function getContainer() {
        return $this->container;
    }

    public function setItems(\Closure $callback): FormManager {
        $this->items = $callback;
        return $this;
    }

    public function setParams(array $params): FormManager {
        $this->params = $params;
        return $this;
    }

    public function setSubmitButtonText(string $text): FormManager {
        $this->submit_button = $text;
        return $this;
    }

    public function onSubmit(\Closure $callback, $need_ga = false): FormManager {
        $this->on_submit = $callback;

        $wrapper = new SerializableClosure($callback);
        $reflector = $wrapper->getReflector();
        $callback_id = md5($reflector->getCode());

        $self = $this;
        $this->action = $this->container->createAction(function (ActionRequest $request) use ($self) {
            $values = [];
            $params = $request->getParams();
            $raw_values = $request->getValues();
            foreach ($raw_values as $k => $v) {
                $k = str_replace('_' . $this->id(), '', $k);
                $values[$k] = $v;
            }

            try {
                return $self->getOnSubmit()->call($this, new ActionRequest($params, $values));
            } catch (\Exception $e) {
                return $self->getContainer()->showErrorToast($e->getMessage());
            }
        }, $callback_id);

        if ($need_ga) {
            $this->action = $this->action->needGa();
        }

        return $this;
    }

    public function getOnSubmit() {
        return $this->on_submit;
    }

    public function build(): Layout {

        $values = [];
        $items = [];
        if ($this->items) {
            $items = $this->items->call($this->container, $this->params);

            foreach ($items as $item) {
                $name = $item->getName();
                $dom_name = $name . '_' . $this->container->id();
                $values[$name] = $dom_name;
                $item->setName($dom_name);
            }
        }

        $button = Button::withParams($this->submit_button)
            ->onClick($this->action->use($this->params, $values));
        $items[] = $button;

        return Form::withItems(...$items);
    }
}
