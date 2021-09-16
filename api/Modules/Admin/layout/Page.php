<?php


namespace Admin\layout;


use Api\Admin\Admin;
use Engine\Request;
use Serializers\AdminSerializer;

class Page extends Layout {
    protected $module;
    protected $layout;

    public static function open(string $module): self {
        $page = new Page();
        $page->module = $module;
        $class_name = "\Admin\modules\\" . $module;
        /* @var \Admin\helpers\PageContainer $inst */
        $inst = new $class_name();
        $inst->setAdmin(Request::getUser());
        $inst->registerActions();
        $inst->build();

        $page->layout = $inst->layout->build();
        return $page;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::action(Admin::ACTION_SHOW_PAGE, [
            'page' => $this->module,
            'layout' => $this->layout
        ]);
    }
}
