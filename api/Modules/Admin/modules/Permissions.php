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
use Admin\layout\Toast;
use Db\Model\ModelSet;
use Models\UserPermissionModel;

class Permissions extends PageContainer {
    /* @var DataManager */
    private $table;

    /* @var FormManager */
    private $add_form;

    /* @var FormManager */
    private $edit_form;

    /** @var Action */
    private $add_action;

    /** @var Action */
    private $edit_action;

    /** @var Action */
    private $delete_action;

    public function registerActions() {
        $this->createAddAction();
        $this->createEditAction();
        $this->createDeleteAction();

        $this->edit_form = $this->createForm();
        $this->add_form = $this->createForm();

        $this->table = $this
            ->createManagedTable(UserPermissionModel::class, ['Id', 'Name', 'Actions'])
            ->setDataMapper(function (ModelSet $permissions)  {
                return $permissions->map(function (UserPermissionModel $permission) {
                    return [
                        $permission->id,
                        $permission->name,
                        ActionSheet::withItems(
                            ActionSheetItem::withParams('Edit')->onClick($this->edit_action->use([
                                'id' => $permission->id,
                            ])),
                            ActionSheetItem::withParams('Delete')->onClick($this->delete_action->use([
                                'id' => $permission->id,
                            ]))
                        ),
                    ];
                });
            })
            ->setOrderBy(['id' => 'DESC']);

    }

    private function createForm() {
        return $this->createFormManager()
            ->setItems(function ($params) {
                $id = array_get_val($params, 'id');

                $name = '';
                if (!is_null($id)) {
                    $permission = UserPermissionModel::get($id);
                    $name = $permission->name;
                }

                return [
                    Input::withParams('name', 'Enter permission name', $name)
                        ->setRequired(true),
                ];
            })
            ->onSubmit(function (ActionRequest $request) {
                $params = $request->getParams();
                $id = array_get_val($params, 'id', null);
                $filters = [
                    'name' => ['required'],
                ];
                $values = $request->getValues($filters);

                $permission = $id ? UserPermissionModel::get($id) : new UserPermissionModel();
                $permission->name = $values['name'];
                try {
                    $permission->save();
                } catch (\Exception $e) {
                    return [
                        $this->showToast($e->getMessage(), Toast::TYPE_ERROR),
                    ];
                }

                return [
                    $this->closeModal(),
                    $this->showToast('Permission saved'),
                    $this->table->getReloadAction($params, $values)
                ];
            });
    }

    public function build() {
        $button = Button::withParams('Add permission')
            ->onClick($this->add_action);

        $this->layout->push(Block::withParams('Actions', $button));
        $this->layout->push(Block::withParams('Permissions', $this->table->build()));
    }

    private function createEditAction() {
        $this->edit_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal('Edit permission', $this->edit_form->setParams($request->getParams())->build());
        });
    }

    private function createAddAction() {
        $this->add_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal('Add permission', $this->add_form->setParams($request->getParams())->build());
        });
    }

    private function createDeleteAction() {
        $this->delete_action = $this->createAction(function (ActionRequest $request) {
            /* @var UserPermissionModel $item */
            $item = UserPermissionModel::get($request->getParam('id'));
            $item->delete(true);

            return [
                $this->showToast('Permission deleted'),
                $this->table->getReloadAction($request->getParams(), $request->getValues())
            ];
        })->setConfirm(true, 'Delete Permission', true);
    }

}
