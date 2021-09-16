<?php

namespace Models\Logs;

class AssignRolePermissionLog extends LogHelper {
    const ASSIGN_ROLE_PERMISSION_ACTION = 'assign_role_permission',
        REMOVE_ROLE_PERMISSION_ACTION = 'remove_role_permission';

    /** @var int */
    private $permission_id;
    /** @var string */
    private $permission_name ;
    /** @var int */
    private $role_id;
    /** @var string */
    private $role_name;

    public static $fields = [
        'permission_id',
        'permission_name',
        'role_id',
        'role_name',
    ];

    public function __construct(array $extra) {
        parent::__construct($extra);
        $this->setPermissionId($extra['permission_id'])
            ->setPermissionName($extra['permission_name'])
            ->setRoleId($extra['role_id'])
            ->setRoleName($extra['role_name']);
    }

    public function tableColumn(): string {
        return sprintf(
            'To Role: %s (%s), permission: %s (%s)',
            $this->getRoleName(),
            $this->getRoleId(),
            $this->getPermissionName(),
            $this->getPermissionId()
        );
    }

    public function getPermissionId(): int {
        return $this->permission_id;
    }

    public function setPermissionId($permission_id): AssignRolePermissionLog {
        $this->permission_id = $permission_id;
        return $this;
    }

    public function getRoleId(): int {
        return $this->role_id;
    }

    public function setRoleId($role_id): AssignRolePermissionLog {
        $this->role_id = $role_id;
        return $this;
    }

    public function getPermissionName(): string {
        return $this->permission_name;
    }

    public function setPermissionName(string $permission_name): AssignRolePermissionLog {
        $this->permission_name = $permission_name;
        return $this;
    }

    public function getRoleName(): string {
        return $this->role_name;
    }

    public function setRoleName(string $role_name): AssignRolePermissionLog{
        $this->role_name = $role_name;
        return $this;
    }
    
}