<?php

namespace Db\Model\Field;

class LoginField extends CharField {
    protected $length = 128;

    public function value($value) {
        $value = parent::value($value);

        // ToDo: refactor old users
//        if (!preg_match("/^[A-Za-z0-9_]{2,{$this->length}}$/", $value)) {
//            throw new InvalidValueException();
//        }

        return $value;
    }
}
