<?php

namespace Db\Exception;

use Db\Db;
use Throwable;

class InvalidUpdateQueryException extends InvalidQueryException {
    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        if (KERNEL_CONFIG['debug']) {
            $this->message = Db::conn()->error;
        }

        parent::__construct($this->message, $code, $previous);
    }
}
