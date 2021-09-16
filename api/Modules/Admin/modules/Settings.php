<?php

namespace Admin\modules;

use Admin\helpers\ActionRequest;
use Admin\helpers\DataManager;
use Admin\helpers\FormManager;
use Admin\helpers\PageContainer;
use Admin\layout\Action;
use Admin\layout\ActionSheet;
use Admin\layout\ActionSheetItem;
use Admin\layout\Block;
use Admin\layout\Button;
use Admin\layout\Input;
use Admin\layout\Select;
use Db\Model\ModelSet;
use Db\Where;
use Exceptions\InvalidKeyException;
use Models\SettingsModel;

class Settings extends PageContainer {
    /** @var Action */
    private $settings_action;

    /** @var Action */
    private $delete_setting_action;

    /** @var FormManager $edit_form */
    private $settings_form;

    /** @var DataManager */
    private $table;

    public function registerActions() {
        $this->table = $this->createManagedTable(
            SettingsModel::class,
            ['name', 'value', 'group', 'description', 'actions']
        )
            ->setDataMapper(function (ModelSet $settings) {
                return $settings->map(function (SettingsModel $setting) {
                    return [
                        $setting->name,
                        $setting->value,
                        $setting->group_name,
                        mb_strimwidth($setting->description,0, 100, '...'),
                        ActionSheet::withItems(
                            ActionSheetItem::withParams('Edit')
                                ->onClick($this->settings_action->use(['setting_id' => $setting->id])),
                            ActionSheetItem::withParams('Delete')
                                ->onClick($this->delete_setting_action->use(['setting_id' => $setting->id]))
                        )
                    ];
                });
            })
            ->setSearchForm(function () {
                $setting_groups = SettingsModel::queryBuilder()
                    ->columns(['group_name'], true)
                    ->groupBy(['group_name'])
                    ->select();

                $groups = [];
                foreach ($setting_groups as $setting_group) {
                    $groups[$setting_group['group_name']] = $setting_group['group_name'];
                }

                return [
                    Input::withParams('keywords', 'Enter name or description'),
                    Select::withParams('groups', 'Select group', $groups)->setMultiple(true),
                ];
            })
            ->setFiltering(function(array $filters, Where $where) {
                if (isset($filters['keywords'])) {
                    $keywords = $filters['keywords'];
                    $where->set("CONCAT(name, ' ', description)", Where::OperatorLike, "%$keywords%");
                }

                if (isset($filters['groups'])) {
                     $where->set(Where::in('group_name', $filters['groups']));
                }

                return $where;
            });

        $this->settings_action = $this->createAction(function (ActionRequest $request) {
            try {
                $setting_id = $request->getParam('setting_id');
                $title = 'Edit setting';
            } catch (InvalidKeyException $e) {
                $setting_id = 0;
                $title = 'Add setting';
            }

            return $this->showModal(
                $title,
                $this->settings_form->setParams(['id' => $setting_id])->build()
            );
        })->needGa();

        $this->settings_form = $this->createFormManager()
            ->setItems(function ($params) {
                $group = '';
                $value = '';
                $name = '';
                $description = '';

                if (isset($params['id']) && (int) $params['id'] !== 0) {
                    $setting = SettingsModel::get((int) $params['id']);
                    $group = $setting->group_name;
                    $value = $setting->value;
                    $name = $setting->name;
                    $description = $setting->description;
                }

                return [
                    Input::withParams('name', 'Name', $name, '', 'Name'),
                    Input::withParams('group', 'Group', $group, '', 'Group'),
                    Input::withParams('value', 'Value', $value, '', 'Value'),
                    Input::withParams(
                        'description', 'Description',
                        $description, '', 'Description'
                    ),
                ];
            })
            ->onSubmit(function (ActionRequest $request) {
                $id = (int) $request->getParam('id');

                /**
                 * @var string $name
                 * @var string $group
                 * @var string $value
                 * @var string $description
                 */
                extract($request->getValues([
                    'name' => ['required'],
                    'group' => ['required'],
                    'value' => ['required'],
                    'description' => [],
                ]));

                if ($id === 0) {
                    $toast = 'Setting added';
                    $setting = new SettingsModel();
                } else {
                    $toast = 'Setting edited';
                    $setting = SettingsModel::get((int) $id);
                }

                $setting->name = $name;
                $setting->group_name = $group;
                $setting->value = $value;
                $setting->description = $description;
                $setting->save();

                return [
                    $this->showToast($toast),
                    $this->closeModal(),
                    $this->table->getReloadAction([], []),
                ];
            });

        $this->delete_setting_action = $this->createAction(function (ActionRequest $request) {
            SettingsModel::get($request->getParam('setting_id'))->delete(true);
            return [
                $this->table->getReloadAction([], []),
                $this->showToast('Setting deleted'),
            ];
        })->setConfirm(true, 'Delete setting?')->needGa();
    }

    public function build() {
        $button = Button::withParams('Add page')
            ->onClick($this->settings_action);

        $this->layout->push(Block::withParams('Actions', $button));
        $this->layout->push(Block::withParams('Settings', $this->table->build()));
    }
}
