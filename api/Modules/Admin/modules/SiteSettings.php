<?php


namespace Admin\modules;


use Admin\helpers\ActionRequest;
use Admin\helpers\FormManager;
use Admin\helpers\PageContainer;
use Admin\helpers\TabsManager;
use Admin\layout\Action;
use Admin\layout\Block;
use Admin\layout\Button;
use Admin\layout\InfoList;
use Admin\layout\InfoListItem;
use Admin\layout\Input;
use Admin\layout\Tab;
use Models\SiteSettingsModel;

class SiteSettings extends PageContainer {
    /** @var Action */
    private $change_action;

    /** @var FormManager $change_form */
    private $change_form;

    /** @var TabsManager */
    private $tabs;

    public function registerActions() {
        $fields = $this->getFields();
        $settings = SiteSettingsModel::get(1);

        $this->change_form = $this->createFormManager()
            ->setItems(function ($params) use ($fields, $settings) {
                $type = array_get_val($params, 'type');
                $items = [];
                $settings_by_type = SiteSettingsModel::SETTINGS_BY_TYPE;
                foreach ($fields as $field) {
                    if ($type != 'all' && in_array($field, $settings_by_type[$type])) {
                        $items[] = Input::withParams($field, '', $settings->{$field}, '', $field);
                    } elseif ($type == 'all') {
                        $items[] = Input::withParams($field, '', $settings->{$field}, '', $field);
                    }
                }
                return $items;
            });

        $tabs = $this->generateTabs();
        $this->tabs = $this->createTabsManager()->setTabs(...$tabs);

        $this->change_form->onSubmit(function(ActionRequest $request){
            $settings = SiteSettingsModel::get(1);
            foreach ($request->getValues() as $key => $value) {
                $settings->{$key} = $value;
            }
            $settings->save();
            return [
                $this->closeModal(),
                $this->openPage('SiteSettings'),
                $this->showToast('Settings has been saved.')
            ];
        }, true);

        $this->change_action = $this->changeAction();
    }

    private function changeAction(): Action {
        return $this->createAction(function(ActionRequest $request) {
            return [
                $this->showModal('Change settings',
                    $this->change_form
                        ->setParams($request->getParams())
                        ->build()
                ),
            ];
        });
    }

    private function generateTabs(): array {
        $tabs = [
            Tab::withParams('All')->setRenderer(function () {
                return $this->tabContent('all', []);
            })
        ];
        foreach (SiteSettingsModel::SETTINGS_BY_TYPE as $type => $fields) {
            $tabs[] = Tab::withParams($type)->setRenderer(function () use ($type, $fields) {
                return $this->tabContent($type, $fields);
            });
        }

        return $tabs;
    }

    private function tabContent(string $type = 'all', array $fields = []): array {
        $button = Button::withParams('Change', Button::TYPE_PRIMARY, Button::SIZE_LARGE)
            ->onClick($this->change_action->setParams(['type' => $type]));
        return [
            $button,
            $this->createList($type, $fields)
        ];
    }

    public function build() {
        $this->layout->push(Block::withParams('Settings', $this->tabs->build()));
    }

    private function createList(string $type, array $fields) {
        if ($type == 'all') {
            $fields = $this->getFields();
        }

        $settings = SiteSettingsModel::get(1);
        $items = [];

        foreach ($fields as $field) {
            array_push($items, InfoListItem::withParams((string) $field, (string) $settings->{$field}));
        }

        return InfoList::withItems(...$items);
    }

    private function getFields(): array {
        $fields = SiteSettingsModel::getFields();
        $fields = array_keys($fields);

        $fields = array_filter($fields, function($item){
            return !in_array($item, ['created_at_timestamp', 'updated_at_timestamp', 'deleted_at']);
        });

        return $fields;
    }
}
