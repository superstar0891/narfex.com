<?php

namespace Models\Logs;

class AssignUserRoleLog extends LogHelper {
    const ASSIGN_ROLE_ACTION = 'assign_role',
        REMOVE_ROLE_ACTION = 'remove_role';

    /** @var int */
    private $role_id;
    /** @var string */
    private $role_name;
    /** @var int */
    private $user_id;

    public static $fields = [
        'role_id',
        'role_name',
        'user_id',
    ];

    public function __construct(array $extra) {
        parent::__construct($extra);
        $this->setRoleId($extra['role_id'])
            ->setRoleName($extra['role_name'])
            ->setUserId($extra['user_id']);
    }

    public function tableColumn(): string {
        return sprintf(
            'To User id: %s, role: %s (%s)',
            $this->getUserId(),
            $this->getRoleName(),
            $this->getRoleId()
        );
    }

    public function getRoleId(): int {
        return $this->role_id;
    }

    public function setRoleId($role_id): AssignUserRoleLog {
        $this->role_id = $role_id;
        return $this;
    }

    public function getUserId(): int {
        return $this->user_id;
    }

    public function setUserId($user_id): AssignUserRoleLog {
        $this->user_id = $user_id;
        return $this;
    }

    public function getRoleName(): string {
        return $this->role_name;
    }

    public function setRoleName($role_name): AssignUserRoleLog {
        $this->role_name = $role_name;
        return $this;
    }

}
