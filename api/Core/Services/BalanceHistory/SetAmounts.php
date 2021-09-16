<?php


namespace Core\Services\BalanceHistory;


trait SetAmounts {
    public function setFromAmount(float $amount) {
        $this->from_amount = $amount;

        return $this;
    }

    public function setToAmount(float $amount) {
        $this->to_amount = $amount;

        return $this;
    }
}
