<?php

namespace Db\Model\Field;

use Db\Model\Field\Exception\InvalidValueException;

class IntField extends FieldAbstract {
    protected static $type = 'INT';

    protected $length = 11;

    public function value($value) {
        $value = parent::value($value);

        // hack for old rows
        if (!$value && $this->is_null) {
            return null;
        }

        if (!is_numeric($value)) {
            throw new InvalidValueException();
        }

        return (int) $value;
    }

    public function setUnsigned() {
        $this->is_unsigned = true;
        return $this;
    }
}
