<?php

namespace Admin\layout;

use Api\Admin\Admin;
use Serializers\AdminSerializer;

class Toast extends Layout {

    const TYPE_SUCCESS = 'success';
    const TYPE_ERROR = 'error';

    private $message = 'Unknown message';
    private $type = self::TYPE_SUCCESS;

    public static function withParams(string $message, string $type = Toast::TYPE_SUCCESS): Toast {
        $instance = new Toast();
        $instance->setMessage($message);
        $instance->setType($type);
        return $instance;
    }

    public function setMessage(string $message): Toast {
        $this->message = $message;
        return $this;
    }

    public function setType(string $type): Toast {
        $this->type = $type;
        return $this;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::action(Admin::ACTION_SHOW_TOAST, AdminSerializer::toast($this->message, $this->type));
    }
}
