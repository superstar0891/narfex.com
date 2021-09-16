<?php

namespace Core\Exceptions\Withdrawal;

use Throwable;

class InsufficientFundsException extends \Exception {
    public function __construct($code = 0, Throwable $previous = null) {
        parent::__construct('insufficient funds', $code, $previous);
    }
}