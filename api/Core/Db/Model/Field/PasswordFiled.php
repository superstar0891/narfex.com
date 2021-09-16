<?php

namespace Db\Model\Field;

class PasswordFiled extends AutofillField {
    protected static $type = 'VARCHAR';

    public function fill($meta = null): string {
        return md5(md5(md5($meta) . 'eObeQi4MFUfx9UJRZllDu12xNHILXUPNy4fz3vBw'));
    }
}
