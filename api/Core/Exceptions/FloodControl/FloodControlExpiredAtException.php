<?php

namespace Core\Exceptions\FloodControl;

class FloodControlExpiredAtException extends \Exception {
    private $expired_at = null;

    public function setExpiredAt(int $expired_at) {
        $this->expired_at = $expired_at;
        return $this;
    }

    public function getExpiredAt() {
        return $this->expired_at;
    }
}