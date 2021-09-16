<?php

namespace Models;

use Db\Model\Field\BooleanField;
use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Model;
use Db\Where;

/**
 * @property int id
 * @property string primary_coin
 * @property string secondary_coin
 * @property double min_amount
 * @property double max_amount
 * @property double maker_volume
 * @property string decimals
 * @property int is_internal
 */
class ExMarketModel extends Model {
    protected static $table_name = 'ex_markets';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'primary_coin' => CharField::init()->setLength(32),
            'secondary_coin' => CharField::init()->setLength(32),
            'min_amount' =>  DoubleField::init(),
            'max_amount' =>  DoubleField::init(),
            'maker_volume' => DoubleField::init(),
            'decimals' => CharField::init()->setLength(32),
            'is_internal' => BooleanField::init()->setDefault(0),
        ];
    }

    public function getName(): string {
        return $this->primary_coin . '/' . $this->secondary_coin;
    }

    public static function has(string $name): bool {
        [$primary, $secondary] = explode('/', $name);
        return !ExMarketModel::select(Where::and()
            ->set('primary_coin', Where::OperatorEq, $primary)
            ->set('secondary_coin', Where::OperatorEq, $secondary)
        )->isEmpty();
    }

    public function getDecimals(): array {
        [$primary, $secondary] = array_map('intval', explode(',', $this->decimals));
        return compact('primary', 'secondary');
    }
}
