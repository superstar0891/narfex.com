<?php


namespace Core\Exceptions\Exchange;


class AmountTooSmallException extends ExchangeException {
    private $min_amount;

    public function __construct(string $min_amount) {
        $this->min_amount = $min_amount;
        parent::__construct();
    }

    public function getTranslatedMessage() {
        return $this->min_amount;
    }
}
