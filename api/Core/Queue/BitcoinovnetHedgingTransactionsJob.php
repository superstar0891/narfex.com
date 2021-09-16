<?php

namespace Core\Queue;

use Db\Transaction;
use Models\HedgingTransactionModel;
use Modules\HedgingExchangeModule;

class BitcoinovnetHedgingTransactionsJob implements ShouldQueue {
    public $tries = 3;

    public $hedging_transaction_id;

    public $account;

    public function __construct(string $hedging_transaction_id, string $account) {
        $this->hedging_transaction_id = $hedging_transaction_id;
        $this->account = $account;
    }

    public function handle() {
        Transaction::wrap(function () {
            $transaction = HedgingTransactionModel::get($this->hedging_transaction_id);
            $conf = KERNEL_CONFIG['bitcoinovnet_hedging'][$transaction->exchange][$this->account];
            $exchange = HedgingExchangeModule::getExchange($transaction->exchange, $conf['key'], $conf['secret']);

            if ($transaction->currency !== CURRENCY_USD) {
                return;
            }

            $reduce_only = ($transaction->isShort() && $transaction->isBuy()) ||
                ($transaction->isLong() && $transaction->isSell());

            sleep(2);
            $order = $exchange->openMarketOrder(\Symbols::BTCUSD, $transaction->type, $transaction->amount, $reduce_only);

            if (isset($order)) {
                if ($order['status'] !== 'closed' && $order['status'] !== 'canceled') {
                    throw new \Exception('Incorrect order status. ' . json_encode($order));
                }

                if ($order['status'] === 'canceled') {
                    if (strpos(strtolower($order['info']['text']), 'reduceonly') === false) {
                        throw new \Exception('Incorrect canceled order. ' . json_encode($order));
                    }
                }

                $transaction->rate = (float) $order['price'];
                $transaction->text = $order['info']['text'];
                $transaction->save();
            }
        });
    }
}