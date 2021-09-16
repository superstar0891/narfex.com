<?php

namespace Cron;

use Db\Transaction;
use Db\Where;
use Models\BankCardModel;
use Models\BankCardOperationModel;

class BankCardsCronJob implements CronJobInterface {
    public function exec() {
        $cards = BankCardModel::select(Where::and()
            ->set('booked_by', Where::OperatorIsNot, NULL)
            ->set('book_expiration', Where::OperatorLowerEq, time())
        );

        $operations = BankCardOperationModel::select(Where::and()
            ->set(Where::in('card_id', $cards->column('id')))
            ->set(Where::equal('status', BankCardOperationModel::STATUS_WAIT_FOR_PAY))
            ->set(Where::equal('operation', BankCardOperationModel::OPERATION_BOOK))
        );

        $operations_by_cards = [];
        foreach ($operations as $operation) {
            /* @var BankCardOperationModel $operation */
            $operations_by_cards[$operation->card_id][] = $operation;
        }

        Transaction::wrap(function () use ($cards, $operations_by_cards) {
            foreach ($cards as $card) {
                /* @var BankCardModel $card */

                $operations = $operations_by_cards[$card->id] ?? [];

                foreach ($operations as $operation) {
                    /* @var BankCardOperationModel $operation */
                    $operation->status = BankCardOperationModel::STATUS_EXPIRED;
                    $operation->save();
                }

                $card->booked_by = null;
                $card->book_expiration = null;
                $card->save();
            }
        });
    }
}
