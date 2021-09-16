<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Model;

/**
 * @property int user_id
 * @property int agent_id
 * @property float percent
 * @property string promo_code
 * @property int swap_count
 */
class AgentPromoCodeModel extends Model {

    protected static $table_name = 'agent_promo_codes';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'agent_id' => IdField::init(),
            'swap_count' => IntField::init(),
            'percent' => DoubleField::init(),
            'promo_code' => CharField::init()->setLength(64),
        ];
    }

    public function toJson(): array {
        return [
            'percent' => (double) $this->percent,
            'promo_code' => $this->promo_code,
            'swap_count' => (int) $this->swap_count,
        ];
    }
}
