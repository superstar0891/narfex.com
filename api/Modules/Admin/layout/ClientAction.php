<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class ClientAction extends Layout {

    const ACTION_SHOW_TOAST = 'show_toast';
    const ACTION_CLOSE_MODAL = 'close_modal';
    const DELETED_TABLE_ROW = 'delete_table_row';
    const ACTION_SHOW_PAGE = 'show_page';
    const ACTION_SHOW_MODAL = 'show_modal';
    const ACTION_SHOW_TAB = 'show_tab';
    const ACTION_RELOAD_TABLE_ROWS = 'reload_table_rows';
    const ACTION_RELOAD_TABLE = 'reload_table';
    const ACTION_SIGN_IN = 'sign_in';

    private $name;
    private $params;

    public static function withParams(string $name, array $params = []): ClientAction {
        $instance = new ClientAction();
        $instance->setName($name);
        $instance->setParams($params);
        return $instance;
    }

    public function setName(string $name) {
        $this->name = $name;
    }

    public function setParams(array $params) {
        $this->params = $params;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::action($this->name, $this->params);
    }
}
