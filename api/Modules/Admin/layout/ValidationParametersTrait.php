<?php


namespace Admin\layout;


trait ValidationParametersTrait {
    /** @var bool */
    protected $required = false;

    /**
     * @return bool
     */
    public function isRequired(): bool {
        return $this->required;
    }

    public function setRequired(bool $required) {
        $this->required = $required;
        return $this;
    }
}
