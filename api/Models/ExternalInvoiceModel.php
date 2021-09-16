<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Model;

/**
 * @property string invoice_id
 * @property string merchant
 */
class ExternalInvoiceModel extends Model {
    protected static $table_name = 'external_invoice';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'invoice_id' => CharField::init()->setLength(124),
            'merchant' => CharField::init()->setLength(32),
        ];
    }
}
