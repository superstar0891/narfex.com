<?php

namespace Models\Logs;

class CrudPermissionLog extends LogHelper {
    const ADD_PERMISSION_ACTION = 'add_permission',
        DELETE_PERMISSION_ACTION = 'delete_permission',
        UPDATE_PERMISSION_ACTION = 'update_permission';

    /** @var int */
    private $permission_id;
    /** @var string */
    private $permission_name;
    /** @var string|null */
    private $old_permission_name = null;

    public static $fields = [
        'permission_id',
        'permission_name',
        'old_permission_name',
    ];

    public function __construct(array $extra) {
        parent::__construct($extra);
        $this->setPermissionId($extra['permission_id'])
            ->setPermissionName($extra['permission_name']);
        if (isset($extra['old_permission_name'])) {
            $this->setOldPermissionName($extra['old_permission_name']);
        }
    }

    public function tableColumn(): string {
        if (is_null($this->getOldPermissionName())) {
            return sprintf('Permission: %s (%s)', $this->getPermissionName(), $this->getPermissionId());
        }

        return sprintf(
            'Permission: %s (%s) update, Old name: %s',
            $this->getPermissionName(),
            $this->getPermissionId(),
            $this->getOldPermissionName()
        );
    }

    public function getPermissionId(): int {
        return $this->permission_id;
    }

    public function setPermissionId($permission_id): CrudPermissionLog {
        $this->permission_id = $permission_id;
        return $this;
    }

    public function getPermissionName(): string {
        return $this->permission_name;
    }

    public function setPermissionName(string $permission_name): CrudPermissionLog {
        $this->permission_name = $permission_name;
        return $this;
    }

    public function getOldPermissionName(): ?string {
        return $this->old_permission_name;
    }

    public function setOldPermissionName(?string $old_permission_name): CrudPermissionLog {
        $this->old_permission_name = $old_permission_name;
        return $this;
    }

}
