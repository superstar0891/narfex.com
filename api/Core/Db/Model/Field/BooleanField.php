<?php

namespace Db\Model\Field;

use Db\Model\Field\Exception\InvalidValueException;

class BooleanField extends FieldAbstract {
    protected static $type = 'INT';

    protected $length = 1;

    public function value($value) {
        $value = parent::value($value);

        if (!in_array($value, [0, 1, true, false, 'true', 'false'])) {
            throw new InvalidValueException('Value must be boolean');
        }

        switch ($value) {
            case 'true':
                $value = true;
            break;
            case 'false':
                $value = false;
            break;
        }

        return (int) boolval($value);
    }

    final public function setLength(int $length): FieldAbstract {
        return $this;
    }
}
