<?php

namespace Admin\layout;

use Serializers\AdminSerializer;

class NumberFormat extends Layout {
    /** @var float */
    private $number;
    /** @var string|null */
    private $currency;
    /** @var bool|null */
    private $hidden_currency;
    /** @var bool|null */
    private $indicator;
    /** @var string|null */
    private $display_type; //sell, buy, up, down with indicator
    /** @var bool|null */
    private $percent;
    /** @var bool|null */
    private $brackets;
    /** @var int|null */
    private $fraction_digits;
    /** @var bool|null */
    private $symbol;

    public static function withParams(
        float $number,
        string $currency = null,
        array $options = []
    ): NumberFormat {
        $instance = new NumberFormat();
        $instance->setNumber($number);
        $instance->setCurrency($currency);
        $instance->setHiddenCurrency(array_get_val($options, 'hidden_currency'));
        $instance->setIndicator(array_get_val($options, 'indicator'));
        $instance->setType(array_get_val($options, 'display_type'));
        $instance->setPercent(array_get_val($options, 'percent'));
        $instance->setBrackets(array_get_val($options, 'brackets'));
        $instance->setFractionDigits(array_get_val($options, 'fraction_digits'));
        $instance->setSymbol(array_get_val($options, 'symbol'));
        return $instance;
    }

    public function setNumber(float $number) {
        $this->number = $number;
    }

    public function setCurrency(string $currency = null) {
        $this->currency = $currency;
    }

    public function setHiddenCurrency(string $hidden_currency = null) {
        $this->hidden_currency = $hidden_currency;
    }

    public function setIndicator(bool $indicator = null) {
        $this->indicator = $indicator;
    }

    public function setType(string $display_type = null) {
        $this->display_type = $display_type;
    }

    public function setPercent(bool $percent = null) {
        $this->percent = $percent;
    }

    public function setBrackets(bool $brackets = null) {
        $this->brackets = $brackets;
    }

    public function setFractionDigits(int $fraction_digits = null) {
        $this->fraction_digits = $fraction_digits;
    }

    public function setSymbol(bool $symbol = null) {
        $this->symbol = $symbol;
    }

    public function serialize(array $items = []): array {
        return AdminSerializer::numberFormat([
            'number' => $this->number,
            'currency' => $this->currency,
            'hidden_currency' => $this->hidden_currency,
            'indicator' => $this->indicator,
            'display_type' => $this->display_type,
            'percent' => $this->percent,
            'brackets' => $this->brackets,
            'fraction_digits' => $this->fraction_digits,
            'symbol' => $this->symbol,
        ]);
    }
}
