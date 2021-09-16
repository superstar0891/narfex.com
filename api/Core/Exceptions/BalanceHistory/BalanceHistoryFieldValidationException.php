<?php


namespace Core\Exceptions\BalanceHistory;


class BalanceHistoryFieldValidationException extends \Exception {
    public function __construct(string $field, ?array $rules = []) {
        parent::__construct("Field {$field} does not exists. Rules: " . implode(',', $rules));
    }
}
