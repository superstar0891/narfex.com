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
use Admin\layout\Toast;
use Db\Db;
use Db\Model\ModelSet;
use Db\Transaction;
use Models\Logs\AssignRolePermissionLog;
use Models\Logs\CrudRoleLog;
use Models\UserPermissionModel;
use Models\UserRoleModel;
use Modules\UserLogModule;

class Roles extends PageContainer {

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

    /** @var ModelSet */
    private $all_permissions;

    public function registerActions() {
        $this->createAddAction();
        $this->createEditAction();
        $this->createDeleteAction();

        $permissions = [];
        $this->all_permissions = UserPermissionModel::select();

        foreach ($this->all_permissions as $permission_model) {
            /** @var UserPermissionModel $permission_model */
            $permissions[$permission_model->name] = $permission_model->name;
        }

        $this->edit_form = $this->createForm($permissions);
        $this->add_form = $this->createForm($permissions);

        $this->table = $this
            ->createManagedTable(UserRoleModel::class, ['Id', 'Name', 'Permissions', 'Actions'])
            ->setDataMapper(function (ModelSet $roles)  {
                return $roles->map(function (UserRoleModel $role) {
                    $permission_as_array = [];
                    $permission_as_string = '';
                    if ($role->permissions) {
                        foreach ($role->permissionsAsArray() as $permission_name) {
                            if (false === boolval($permission_name)) {
                                continue;
                            }
                            /** @var UserPermissionModel $permission */
                            $permission = $this->getPermissionByName($permission_name);

                            if (is_null($permission)) {
                                continue;
                            }
                            $permission_as_array[] =  "$permission->name ($permission->id)";
                        }
                        $permission_as_string = implode(', ', $permission_as_array);
                    }
                    return [
                        $role->id,
                        $role->role_name,
                        $permission_as_string,
                        ActionSheet::withItems(
                            ActionSheetItem::withParams('Edit')->onClick($this->edit_action->use([
                                'id' => $role->id,
                            ])),
                            ActionSheetItem::withParams('Delete')->onClick($this->delete_action->use([
                                'id' => $role->id,
                            ]))
                        ),
                    ];
                });
            })
            ->setOrderBy(['id' => 'DESC']);

    }

    private function createForm(array $permissions) {
        return $this->createFormManager()
            ->setItems(function ($params) use ($permissions) {
                $id = array_get_val($params, 'id');

                $default_permissions = [];
                $role_name = '';
                if (!is_null($id)) {
                    $role = UserRoleModel::get($id);
                    $role_name = $role->role_name;
                    $default_permissions = $role->permissionsAsArray();
                }

                return [
                    Input::withParams('role_name', 'Enter role name', $role_name)
                        ->setRequired(true),
                    Select::withParams('permissions', 'Choose permissions ', $permissions, $default_permissions)
                        ->setMultiple(true)
                        ->setRequired(true),
                ];
            })
            ->onSubmit(function (ActionRequest $request) {
                $params = $request->getParams();
                $id = array_get_val($params, 'id', null);
                $filters = [
                    'role_name' => ['required'],
                    'permissions' => ['required', 'json'],
                ];

                $values = $request->getValues($filters);

                try {
                    Transaction::wrap(function () use ($request, $params, $values, $id) {
                        $role = $id ? UserRoleModel::get($id) : new UserRoleModel();
                        $permissions = array_filter($values['permissions'], function ($role) {
                            return boolval($role);
                        });
                        if (!is_null($id)) {
                            $this->addLogs($role, $permissions);
                        }
                        $role->role_name = $values['role_name'];
                        $role->permissions = implode(',', $permissions);
                        $role->save();
                        try {
                            $role->save();
                        } catch (\Exception $e) {
                            Db::rollbackTransaction();
                            return [
                                $this->showToast($e->getMessage()),
                            ];
                        }

                        if ($values['role_name'] != $role->role_name) {
                            UserLogModule::addLog(
                                CrudRoleLog::UPDATE_ROLE_ACTION,
                                new CrudRoleLog([
                                    'role_id' => $role->id,
                                    'role_name' => $values['role_name'],
                                    'old_role_name' => $role->role_name,
                                ]),
                                true,
                                $this->getAdmin()
                            );
                        }
                    });
                } catch (\Exception $e) {
                    return [
                        $this->showToast($e->getMessage(), Toast::TYPE_ERROR),
                    ];
                }

                return [
                    $this->closeModal(),
                    $this->table->getReloadAction($params, $values)
                ];
            });
    }

    public function build() {
        $button = Button::withParams('Add role')
            ->onClick($this->add_action);

        $this->layout->push(Block::withParams('Actions', $button));
        $this->layout->push(Block::withParams('Roles', $this->table->build()));
    }

    private function getDiffValues(array $old_items, array $new_items) {
        $added = array_filter($new_items, function ($item) use ($old_items) {
            return !in_array($item, $old_items, true);
        });
        $deleted = array_filter($old_items, function ($item) use ($new_items) {
            return boolval($item) ? !in_array($item, $new_items, true) : false;
        });

        return [$added, $deleted];
    }

    private function addLogs(UserRoleModel $role,  array $new_permissions) {
        [$added_permissions, $deleted_permissions] = $this->getDiffValues(
            $role->permissionsAsArray(),
            $new_permissions
        );

        foreach ($added_permissions as $permission_name) {
            UserLogModule::addLog(
                AssignRolePermissionLog::ASSIGN_ROLE_PERMISSION_ACTION,
                new AssignRolePermissionLog([
                    'permission_id' => $this->getPermissionByName($permission_name)->id,
                    'permission_name' => $permission_name,
                    'role_id' => $role->id,
                    'role_name' => $role->role_name,
                ]),
                true,
                $this->getAdmin()
        );
        }

        foreach ($deleted_permissions as $permission_name) {
            UserLogModule::addLog(
                AssignRolePermissionLog::REMOVE_ROLE_PERMISSION_ACTION,
                new AssignRolePermissionLog([
                    'permission_id' => $this->getPermissionByName($permission_name)->id,
                    'permission_name' => $permission_name,
                    'role_id' => $role->id,
                    'role_name' => $role->role_name,
                ]),
                true,
                $this->getAdmin()
            );
        }
    }

    public function getPermissionByName(string $permission_name): ?UserPermissionModel {
        $result = null;

        foreach ($this->all_permissions as $permission) {
            /** @var UserPermissionModel $role */
            if ($permission_name == $permission->name) {
                $result = $permission;
                break;
            }
        }

        return $result;
    }

    private function createEditAction() {
        $this->edit_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal('Edit role', $this->edit_form->setParams($request->getParams())->build());
        });
    }

    private function createAddAction() {
        $this->add_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal('Add role', $this->add_form->setParams($request->getParams())->build());
        });
    }

    private function createDeleteAction() {
        $this->delete_action = $this->createAction(function (ActionRequest $request) {
            /* @var UserRoleModel $item */
            $item = UserRoleModel::get($request->getParam('id'));
            $item->delete(true);

            return [
                $this->showToast('Role deleted'),
                $this->table->getReloadAction($request->getParams(), $request->getValues())
            ];
        })->setConfirm(true, 'Delete Role', true);
    }

}
