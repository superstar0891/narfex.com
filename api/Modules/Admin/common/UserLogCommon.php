<?php

namespace Admin\common;

use Admin\layout\Input;
use Db\Where;

class UserLogCommon {
    public static function dataFiltering(array $filters, Where $where): Where {
        if (isset($filters['user_id'])) {
            $user_id = positive($filters['user_id']);
            if ($user_id > 0) {
                $where->set('user_id', Where::OperatorEq, $user_id);
            }
        }
        if (isset($filters['date_from'])) {
            $date = \DateTime::createFromFormat('d/m/Y', trim($filters['date_from']));
            if ($date) {
                $date->setTime(0,0);
                $where->set('created_at_timestamp', Where::OperatorGreaterEq, $date->getTimestamp());
            }
        }
        if (isset($filters['action'])) {
            $where->set('action', Where::OperatorLike, '%' . $filters['action'] . '%');
        }
        if (isset($filters['date_to'])) {
            $date = \DateTime::createFromFormat('d/m/Y', trim($filters['date_to']));
            if ($date) {
                $date->setTime(0,0);
                $where->set('created_at_timestamp', Where::OperatorLowerEq, $date->modify('+1 days')->getTimestamp());
            }
        }
        return $where;
    }

    public static function searchForm() {
        return [
            Input::withParams('user_id', 'User id'),
            Input::withParams('action', 'Action'),
            Input::withParams('date_from', 'Date from d/m/Y'),
            Input::withParams('date_to', 'Date to d/m/Y'),
        ];
    }
}