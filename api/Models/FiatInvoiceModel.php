<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Model;

/**
 * @property int user_id
 * @property double amount
 * @property string currency
 */
class FiatInvoiceModel extends Model {
    protected static $table_name = 'fiat_invoices';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'amount' => DoubleField::init(),
            'currency' => CharField::init()->setLength(32),
        ];
    }
}
