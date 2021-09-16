<?php

namespace Models\Logs;

class CrudRoleLog extends LogHelper {
    const ADD_ROLE_ACTION = 'add_role',
        DELETE_ROLE_ACTION = 'delete_role',
        UPDATE_ROLE_ACTION = 'update_role';

    /** @var int */
    private $role_id;
    /** @var string */
    private $role_name;
    /** @var string|null */
    private $old_role_name = null;

    public static $fields = [
        'role_id',
        'role_name',
        'old_role_name',
    ];

    public function __construct(array $extra) {
        parent::__construct($extra);
        $this->setRoleId($extra['role_id'])
            ->setRoleName($extra['role_name']);
        if (isset($extra['old_role_name'])) {
            $this->setOldRoleName($extra['old_role_name']);
        }
    }

    public function tableColumn(): string {
        if (is_null($this->getOldRoleName())) {
            return sprintf('Role: %s (%s)', $this->getRoleName(), $this->getRoleId());
        }

        return sprintf(
            'Role: %s (%s) update, Old name: %s',
            $this->getRoleName(),
            $this->getRoleId(),
            $this->getOldRoleName()
        );
    }

    public function getRoleId(): int {
        return $this->role_id;
    }

    public function setRoleId($role_id): CrudRoleLog {
        $this->role_id = $role_id;
        return $this;
    }

    public function getRoleName(): string {
        return $this->role_name;
    }

    public function setRoleName($role_name): CrudRoleLog {
        $this->role_name = $role_name;
        return $this;
    }

    public function getOldRoleName(): ?string {
        return $this->old_role_name;
    }

    public function setOldRoleName(?string $old_role_name): CrudRoleLog {
        $this->old_role_name = $old_role_name;
        return $this;
    }

}
