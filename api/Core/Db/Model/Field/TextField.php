<?php

namespace Db\Model\Field;

class TextField extends FieldAbstract {
    protected static $type = 'TEXT';

    protected $length = 2000;
}
