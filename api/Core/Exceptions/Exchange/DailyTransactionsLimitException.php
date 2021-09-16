<?php


namespace Core\Exceptions\Exchange;


class DailyTransactionsLimitException extends ExchangeException {
    private $limit;

    public function __construct(string $limit) {
        $this->limit = $limit;
        parent::__construct();
    }

    public function getTranslatedMessage() {
        return $this->limit;
    }
}
