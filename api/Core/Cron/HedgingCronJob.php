<?php

namespace Cron;

use Core\Services\ExternalExchange\Bitmex;
use Core\Services\Hedging\Hedging;
use Core\Services\Redis\RedisAdapter;
use Core\Services\Telegram\SendService;
use Db\Transaction;
use Db\Where;
use Models\ExternalExchangePositionModel;

class HedgingCronJob implements CronJobInterface {
    public function exec() {
        foreach (Hedging::AVAIL_CURRENCIES as $currency) {
            try {
                $this->process($currency);
            } catch (\Exception $e) {
                echo strtoupper($currency) . ': ' . $e->getMessage();
            }
        }
    }

    private function process(string $currency) {
        Transaction::wrap(function () use ($currency) {
            $rows = ExternalExchangePositionModel::select(
                Where::and()
                    ->set(Where::equal('status', ExternalExchangePositionModel::STATUS_IN_QUEUE))
                    ->set(Where::equal('currency', $currency))
            );

            if ($rows->isEmpty()) {
                throw new \Exception('Nothing to do');
            }

            $total_amount = 0;
            foreach ($rows as $row) {
                /* @var ExternalExchangePositionModel $row */

                $total_amount += $row->amount;
            }

            $symbol = Hedging::getBitmexSymbol($currency);
            $credentials = KERNEL_CONFIG['hedging']['bitmex'];
            try {
                $bitmex = new Bitmex(
                    $credentials['key'],
                    $credentials['secret']
                );
                $last_price = $bitmex->getLastPrice($symbol);

                $usd_amount = ceil($last_price * $total_amount);
                $usd_balance = ($bitmex->getBalance() * $last_price);

                if ($usd_balance - $usd_amount < 0) {
                    throw new \Exception('Exchange funds error');
                }

                $order = $bitmex->openMarketOrder($symbol, Bitmex::SIDE_BUY, $usd_amount);
            } catch (\Exception $e) {
                $error = RedisAdapter::shared()->get('bitmex_open_order_error');
                $new_error = md5(preg_replace("/[0-9]/", "", $e->getMessage()));
                if ($error != $new_error) {
                    RedisAdapter::shared()->set('bitmex_open_order_error', $new_error, 1800);
                    $send_service = new SendService();
                    $send_service->sendMessage('#hedging_cron_job ' . PHP_EOL . $e->getMessage());
                }
                throw new \Exception('Cant place order');
            }

            foreach ($rows as $row) {
                /* @var ExternalExchangePositionModel $row */

                $row->real_rate = (float) $order['price'];
                $row->rate = $last_price;
                $row->status = ExternalExchangePositionModel::STATUS_PENDING;
                $row->save();
            }
        });
    }
}
