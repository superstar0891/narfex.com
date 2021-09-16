<?php

namespace Admin\layout;

use Admin\helpers\ActionRequest;
use Core\App;
use Exceptions\InvalidKeyException;
use Api\Admin\Admin;
use Models\UserModel;
use Serializers\AdminSerializer;

class Action extends Layout {
    private $id;

    /* @var \Closure $handler */
    private $handler = null;
    private $roles = [];

    private $params = [];
    private $values = [];

    private $need_ga = false;

    private $confirm_title = '';
    private $confirm = false;
    private $confirm_destructive = false;

    public function __construct(string $id) {
        $this->id = $id;
    }

    public function handle(\Closure $fn): Action {
        $this->handler = $fn;
        return $this;
    }

    public function use(array $params = [], array $values = []): Action {
        $inst = clone $this;
        $inst->setParams($params);
        $inst->setValues($values);
        return $inst;
    }

    public function setParam(string $name, $value): Action {
        $this->params[$name] = $value;
        return $this;
    }

    public function setParams(array $params): Action {
        foreach ($params as $k => $v) {
            $this->setParam($k, $v);
        }
        return $this;
    }

    public function setValue(string $name): Action {
        $this->values[$name] = $name;
        return $this;
    }

    public function setValues(array $values): Action {
        foreach ($values as $name) {
            $this->setValue($name);
        }
        return $this;
    }

    public function needGa() {
        $this->need_ga = true;
        return $this;
    }

    public function setConfirm(bool $need, string $title, bool $destructive = false): Action {
        $this->confirm = $need;
        $this->confirm_title = $title;
        $this->confirm_destructive = $destructive;
        return $this;
    }

    public function setRole($role): self {
        $this->roles[] = $role;

        return $this;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::action($this->id, $this->params, $this->values, [
            'need_ga_code' => $this->need_ga,
            'confirm' => $this->confirm,
            'confirm_title' => $this->confirm_title,
            'confirm_destructive' => $this->confirm_destructive,
        ]);
    }

    /* @param mixed $parent
     * @param ActionRequest $request
     * @return array | Layout
     * @throws
     */
    public function invoke($parent, ActionRequest $request) {
        if (App::isProduction()) {
            if ($toast_need_ga = $this->getToastIfNeedGa($request)) {
                return $toast_need_ga;
            }
        }

        /* @var UserModel $admin */
        $admin = $parent->getAdmin();

        if ($this->roles) {
            if (!$admin->hasRoles($this->roles)) {
                throw new \Exception('Access denied');
            }
        }

        return $this->handler->call($parent, $request);
    }

    public function getToastIfNeedGa(ActionRequest $request): ?Toast {
        if ($this->need_ga) {
            try {
                $ga_code = $request->getParam('ga_code');
            } catch (InvalidKeyException $e) {
                return (new Toast())->setType(Toast::TYPE_ERROR)->setMessage('GA code is not set');
            }

            $ga_admin_hash = KERNEL_CONFIG['admin_ga_hash'];
            if (!checkGoogleAuth($ga_code, $ga_admin_hash)) {
                return (new Toast())->setType(Toast::TYPE_ERROR)->setMessage('Incorrect GA code');
            }
        }

        return null;
    }
}
