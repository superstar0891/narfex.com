<?php

namespace Modules\Admin;


use Api\Admin\Admin;
use Models\UserModel;
use Serializers\AdminSerializer;

class AdminModule {
    #region Actions
    public static function deleteModelRow($model_name, $id) {
        $model_name = 'Models\\' . $model_name . 'Model';

        if (!class_exists($model_name)) {
            return false;
        }

        /* @var \Db\Model\Model $model */
        $model = $model_name::get($id);
        $model->delete();

        return true;
    }

    public static function restoreModelRow($model_name, $id) {
        $model_name = 'Models\\' . $model_name . 'Model';

        if (!class_exists($model_name)) {
            return false;
        }

        /* @var \Db\Model\Model $model */
        $model = $model_name::get($id);
        $model->restore();

        return true;
    }

    public static function page(array $params, UserModel $user): array {
        /* @var \Modules\Admin\AdminModuleInterface $inst */
        $class_name = "\Modules\Admin\\" . $params['page'];
        $inst = new $class_name();
        $inst->admin = $user;
        return $inst->page();
    }

    public static function tabs(array $params, UserModel $user): array {
        list($module) = explode('_', $params['tab']);

        $class_name = "\Modules\Admin\\" . $module;
        $inst = new $class_name();
        $inst->admin = $user;

        $method_name = array_slice(explode('_', $params['tab']), 1);
        $method_name = implode('', array_map('ucfirst', $method_name));

        $func_name = "tab" . $method_name;
        return $inst->$func_name($params);
    }

    public static function modals(array $params, UserModel $user): array {
        $params += [
            'modal' => 'not_found'
        ];

        list($module) = explode('_', $params['modal']);
        $class_name = "\Modules\Admin\\" . $module;
        $inst = new $class_name();
        $inst->admin = $user;

        $method_name = array_slice(explode('_', $params['modal']), 1);
        $method_name = implode('', array_map('ucfirst', $method_name));

        $func_name = "modal" . $method_name;
        return $inst->$func_name($params);
    }

    public static function coreActions(string $action, array $params): array {
        $result = [];
        switch ($action) {
            case 'delete':
                if (!AdminModule::deleteModelRow($params['model'], (int) $params['id'])) {
                    $result[] = self::showToast(lang('api_error'), 'error');
                } else {
                    $result[] = self::showToast('Object deleted');
                }
                break;
            case 'restore':
                if (!AdminModule::restoreModelRow($params['model'], (int) $params['id'])) {
                    $result[] = self::showToast(lang('api_error'), 'error');
                } else {
                    $result[] = self::showToast('Object restored');
                    $result_params['mark_as_restored'] = true;
                }
                break;
            default:
                $result[] = self::showToast("Unknown core action '{$action}'", 'error');
                break;
        }

        return $result;
    }

    public static function customActions(string $action, array $params, array $values, UserModel $user): array {
        list($module) = explode('_', $action);

        $class_name = "\Modules\Admin\\" . $module;
        $inst = new $class_name();
        $inst->admin = $user;

        $method_name = array_slice(explode('_', $action), 1);
        $method_name = implode('', array_map('ucfirst', $method_name));

        $func_name = "action" . $method_name;
        return $inst->$func_name($params, $values);
    }


    public static function showToast(string $message, string $type = 'success'): array {
        return AdminSerializer::action(Admin::ACTION_SHOW_TOAST, AdminSerializer::toast($message, $type));
    }

    public static function showPage(string $name): array {
        return AdminSerializer::action(Admin::ACTION_SHOW_PAGE, [
            'page' => $name,
        ]);
    }

    public static function showCustomPage(string $name) {
        return AdminSerializer::action(Admin::ACTION_SHOW_CUSTOM_PAGE, [
            'page' => $name,
        ]);
    }

    public static function showModal(string $name, array $params = [], array $values = []): array {
        $params = array_merge([
            'modal' => $name
        ], $params);
        return AdminSerializer::action(Admin::ACTION_SHOW_MODAL, $params, $values);
    }

    public static function reloadTableRows(array $rows): array {
        return AdminSerializer::action(Admin::ACTION_RELOAD_TABLE_ROWS, $rows);
    }

    public static function closeModalAction(string $id): array {
        return AdminSerializer::action(Admin::ACTION_CLOSE_MODAL, [
            'id' => $id,
        ]);
    }

    public static function reloadTableAction($id, $table) {
        return AdminSerializer::action(Admin::ACTION_RELOAD_TABLE, [
            'id' => $id,
            'layout' => $table,
        ]);
    }

    public static function updateTableFilters(array $filters): array {
        $result = [];
        foreach ($filters as $k => $v) {
            if ($v !== '__unset') {
                $result[$k] = $v;
            }
        }
        return $result;
    }

    #endregion

    public static function paging($table_id, int $total_count, int $count, array $table_params): array {
        $items = [];
        $pages = $total_count > 0 ? floor($total_count / $count) : 0;
        $cur_page = isset($table_params['page']) ? $table_params['page'] : 0;

        if ($cur_page > 0) {
            $items[] = AdminSerializer::pagingItem('Prev', [
                'action' => AdminSerializer::action($table_id . '_reload', array_merge($table_params, ['page' => $cur_page - 1]))
            ]);
        }

        $items[] = AdminSerializer::pagingItem($cur_page + 1);

        if ($cur_page < $pages) {
            $items[] = AdminSerializer::pagingItem('Next', [
                'action' => AdminSerializer::action($table_id . '_reload', array_merge($table_params, ['page' => $cur_page + 1]))
            ]);
        }

        return AdminSerializer::paging($items);
    }

    public static function tableSearch($rows): array {
        return $rows;
    }
}
