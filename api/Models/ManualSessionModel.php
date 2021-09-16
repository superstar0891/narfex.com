<?php

namespace Models;

use Db\Db;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Model\Model;
use Db\Where;

/**
 * @property int user_id
 * @property int session_start
 * @property int session_end
 * @property int reservations
 * @property int approved_reservation
 * @property int expired_reservation
 * @property int declined_reservation
 */
class ManualSessionModel extends Model {
    protected static $table_name = 'bitcoinovnet_manual_session';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'session_start' => IntField::init()->setLength(10),
            'session_end' => IntField::init()->setLength(10)->setNull(true),
            'reservations' => IntField::init()->setLength(10)->setDefault(0),
            'approved_reservation' => IntField::init()->setLength(10)->setDefault(0),
            'expired_reservation' => IntField::init()->setLength(10)->setDefault(0),
            'declined_reservation' => IntField::init()->setLength(10)->setDefault(0),
        ];
    }

    public function incrReservations(): bool {
        $ret = Db::add(static::getTableName(), 'reservations', 1, Where::equal('id', (int) $this->id));
        if ($ret) {
            $this->reservations += 1;
        }
        return $ret;
    }

    public function incrDeclinedReservations(): bool {
        $ret = Db::add(
            static::getTableName(),
            'declined_reservation',
            1,
            Where::equal('id',
                (int) $this->id)
        );
        if ($ret) {
            $this->declined_reservation += 1;
        }
        return $ret;
    }

    public function incrSuccessReservations(): bool {
        $ret = Db::add(
            static::getTableName(),
            'approved_reservation',
            1,
            Where::equal('id',
                (int) $this->id)
        );
        if ($ret) {
            $this->approved_reservation += 1;
        }
        return $ret;
    }

    public function incrExpiredReservations(): bool {
        $ret = Db::add(
            static::getTableName(),
            'expired_reservation',
            1,
            Where::equal('id',
                (int) $this->id)
        );
        if ($ret) {
            $this->expired_reservation += 1;
        }
        return $ret;
    }

    public static function getCurrentSession() {
        static $session = false;

        if ($session !== false) {
            return $session;
        }

        $session = ManualSessionModel::first(Where::and()
            ->set('session_start', Where::OperatorLowerEq, time())
            ->set('session_end', Where::OperatorIs, NULL)
        );

        return $session;
    }
}
