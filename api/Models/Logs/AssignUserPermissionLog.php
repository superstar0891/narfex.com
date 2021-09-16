<?php

namespace Models\Logs;

class AssignUserPermissionLog extends LogHelper {
    const ASSIGN_PERMISSION_ACTION = 'assign_permission',
        REMOVE_PERMISSION_ACTION = 'remove_permission';

    /** @var int */
    private $permission_id;
    /** @var string */
    private $permission_name ;
    /** @var int */
    private $user_id;

    public static $fields = [
        'permission_id',
        'permission_name',
        'user_id',
    ];

    public function __construct(array $extra) {
        parent::__construct($extra);
        $this->setPermissionId($extra['permission_id'])
            ->setPermissionName($extra['permission_name'])
            ->setUserId($extra['user_id']);
    }

    public function tableColumn(): string {
        return sprintf(
            'To User id: %s, permission: %s (%s)',
            $this->getUserId(),
            $this->getPermissionName(),
            $this->getPermissionId()
        );
    }

    public function getPermissionId(): int {
        return $this->permission_id;
    }

    public function setPermissionId($permission_id): AssignUserPermissionLog {
        $this->permission_id = $permission_id;
        return $this;
    }

    public function getUserId(): int {
        return $this->user_id;
    }

    public function setUserId($user_id): AssignUserPermissionLog {
        $this->user_id = $user_id;
        return $this;
    }

    public function getPermissionName(): string {
        return $this->permission_name;
    }

    public function setPermissionName(string $permission_name): AssignUserPermissionLog {
        $this->permission_name = $permission_name;
        return $this;
    }

}