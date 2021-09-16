<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Model;

/**
 * @property int user_id
 * @property string card_number
 * @property string card_owner
 * @property int validated
 * @property string photo_name
 */
class BitcoinovnetUserCardModel extends Model {
    protected static $table_name = 'bitcoinovnet_user_cards';

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'card_number' => IntField::init()->setLength(20),
            'card_owner' => CharField::init(),
            'photo_name' => CharField::init()->setNull(),
            'validated' => IntField::init()->setLength(1)->setDefault(0)
        ];
    }

    public function isValidated() {
        return (bool) $this->validated;
    }

    public function validate() {
        $this->validated = 1;
        $this->save();
    }

    public function unvalidate() {
        $this->validated = 0;
        $this->save();
    }

    public function toJson(): array{
        return [
            'id' => (int) $this->id,
            'user_id' => (int) $this->user_id,
            'card_owner' => $this->card_owner,
            'card_number' => maskCreditCard($this->card_number),
            'validated' => $this->isValidated(),
        ];
    }
}
