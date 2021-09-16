<?php

namespace Db\Model\Field;

class SecretKeyField extends AutofillField {
    protected static $type = 'VARCHAR';

    public function fill($meta = null): string {
        return md5(md5(md5($meta)));
    }
}
