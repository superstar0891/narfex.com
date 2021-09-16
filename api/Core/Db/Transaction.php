<?php

namespace Db;

use Closure;
use Exception;

class Transaction {

    /* @param Closure $func
     * @return mixed | void
     * @throws
    */
    public static function wrap(Closure $func) {
        $start_transaction = !Db::isTransaction();

        if ($start_transaction) {
            Db::transactionBegin();
        }

        try {
            $ret = $func();
        } catch (Exception $e) {
            if ($start_transaction) {
                Db::rollbackTransaction();
            }
            throw $e;
        }

        if ($start_transaction) {
            Db::commitTransaction();
        }

        return $ret;
    }
}
