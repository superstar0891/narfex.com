<?php

namespace Admin\helpers;

use Admin\layout\Action;
use Admin\layout\ClientAction;
use Admin\layout\DataManagementInterface;
use Admin\layout\Layout;
use Admin\layout\Page;
use Admin\layout\Tab;
use Admin\layout\Table;
use Admin\layout\Toast;
use Admin\layout\Wrapper;
use Api\Errors;
use Core\Response\JsonResponse;
use Db\Where;
use Models\UserModel;
use Opis\Closure\SerializableClosure;
use Serializers\ErrorSerializer;

abstract class PageContainer {

    /** @var array  */
    public static $permission_list = [];

    /* @var LayoutBuilder $layout */
    public $layout;

    /* @var []Action $actions */
    private $actions = [];

    /* @var \Models\UserModel $admin */
    private $admin;

    public function __construct() {
        $this->layout = new LayoutBuilder();
    }

    public function registerActions() {
        $reflection = new \ReflectionClass(static::class);
        $traits = $reflection->getTraits();
        foreach ($traits as $trait) {

            $method = lcfirst($trait->getShortName()) . 'RegisterActions';
            if ($trait->hasMethod($method)) {
                $this->$method();
            }
        }
    }

    public function setAdmin(UserModel $admin): self {
        $this->admin = $admin;
        return $this;
    }

    public function getAdmin(): UserModel {
        return $this->admin;
    }

    public function id(): string {
        $exp = explode('\\', get_class($this));
        return end($exp);
    }

    /* @param \Closure | string $callback
     * @param string $id
     * @return Action
     */
    public function createAction($callback, $id = '') {
        if (is_string($callback)) {
            $callback = \Closure::fromCallable($callback);
        }

        $wrapper = new SerializableClosure($callback);
        $reflector = $wrapper->getReflector();
        $id = $this->id() . '_' . md5($reflector->getCode()) . '_' . $id;

        if (isset($this->actions[$id])) {
            return $this->actions[$id];
        }

        $action = new Action($id);
        $action->handle($callback);
        $this->actions[$id] = $action;
        return $action;
    }

    public function createDataManager(
        string $model,
        DataManagementInterface $layout,
        Where $where = null,
        bool $exclude_deleted = true): DataManager {
        return new DataManager($model, $this, $layout, $where, null, $exclude_deleted);
    }

    public function createManagedTable(
        string $model,
        array $headers,
        Where $where = null,
        bool $exclude_deleted = true): DataManager {
        return $this->createDataManager(
            $model,
            Table::withHeaders($headers),
            $where,
            $exclude_deleted
        );
    }

    public function createFormManager(): FormManager {
        return new FormManager($this);
    }

    public function createTabsManager(): TabsManager {
        return new TabsManager($this);
    }

    public function build() {
        throw new \Exception('invoke method not implemented');
    }

    public function buildAction(string $action, array $params, array $values) {
        if (!isset($this->actions[$action])) {
            JsonResponse::error(ErrorSerializer::detail(Errors::FATAL, 'Action not found'));
        }

        $ret = $this->actions[$action]->invoke($this, new ActionRequest($params, $values));
        if (is_array($ret)) {
            foreach ($ret as $item) {
                $this->layout->push($item);
            }
        } else {
            $this->layout->push($ret);
        }
    }

    public function showToast(string $message, string $type = Toast::TYPE_SUCCESS) {
        return Toast::withParams($message, $type);
    }

    public function showErrorToast(string $message) {
        return $this->showToast($message, Toast::TYPE_ERROR);
    }

    public function openPage(string $page) {
        return Page::open($page);
    }

    public function showModal(string $title, Layout ...$content) {
        $builder = new LayoutBuilder;
        $builder->push(Wrapper::withParams($title, ...$content));

        return ClientAction::withParams(ClientAction::ACTION_SHOW_MODAL, [
            'layout' => $builder->build(),
        ]);
    }

    public function closeModal() {
        return ClientAction::withParams(ClientAction::ACTION_CLOSE_MODAL, []);
    }

    public function setTabContent(Tab $tab) {
        $tab->callRenderer($this);

        $builder = new LayoutBuilder;
        foreach ($tab->getItems() as $item) {
            $builder->push($item);
        }

        return ClientAction::withParams(ClientAction::ACTION_SHOW_TAB, [
            'id' => $tab->getId(),
            'layout' => $builder->build(),
        ]);
    }
}
