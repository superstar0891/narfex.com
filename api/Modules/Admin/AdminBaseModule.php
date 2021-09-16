<?php

namespace Modules\Admin;

/**
 * @property \Models\UserModel admin
 */
abstract class AdminBaseModule implements AdminModuleInterface {

    const TABLE_PER_PAGE = 50;

    private $admin;

    public function id(): string {
        $exp = explode('\\', get_class($this));
        return end($exp);
    }

    public static function getName(): string {
        return 'Unknown';
    }

    public function page(): array {
        throw new \Exception('Method page is not implemented');
    }

    public function prefix($name): string {
        return $this->id() . '_' . $name;
    }

    public function actionTableReload(array $params, array $values = []): array {
        if (!isset($params['filters'])) {
            $params['filters'] = [];
        }

        $params['filters'] = array_merge($params['filters'], $values);

        return [
            AdminModule::reloadTableAction($this->prefix('table'), $this->getTable($params))
        ];
    }

    public function getTable(array $params = []): array {
        return [];
    }
}
