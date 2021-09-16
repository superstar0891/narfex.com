<?php

namespace Db\Exception;

use Core\App;
use Db\Db;
use Throwable;

class InvalidSelectQueryException extends InvalidQueryException {
    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        if (!App::isProduction()) {
            $message = Db::conn()->error;
        }
        parent::__construct($message, $code, $previous);
    }
}
