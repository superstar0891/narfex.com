<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Model;

/**
 * @property int id
 * @property int user_id
 * @property int add_date
 * @property int join_count
 * @property int view_count
 * @property string name
 * @property int deposits_count
 */
class InviteLinkModel extends Model {
    protected static $table_name = 'invite_links';

    protected static $fields = [];

    protected static $alphabet_encoding_symbols = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'add_date' => IntField::init(),
            'join_count' => IntField::init()->setDefault(0),
            'view_count' => IntField::init()->setDefault(0),
            'deposits_count' => IntField::init()->setDefault(0),
            'name' => CharField::init()->setLength(128)->setNull(true),
        ];
    }

    public function encode(): string {
        $len = strlen(self::$alphabet_encoding_symbols);
        $link = '';
        $val = $this->id;
        while ($val >= 1) {
            $link .= self::$alphabet_encoding_symbols[$val % $len];
            $val /= $len;
        }
        return $link;
    }

    public static function decode(string $link): int {
        $len = strlen(self::$alphabet_encoding_symbols);
        $val = 0;

        $pow = 1;
        foreach (str_split($link) as $symbol) {
            $pos = strpos(self::$alphabet_encoding_symbols, $symbol);
            $val += $pos * $pow;
            $pow *= $len;
        }

        return $val;
    }
}
