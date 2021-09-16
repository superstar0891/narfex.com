<?php

namespace Cron;

use Core\Services\Curl\CurlAdapter;
use Core\Services\ExternalExchange\Binance;
use Core\Services\Redis\RedisAdapter;
use Models\WalletModel;

class GettingRateCronJob implements CronJobInterface {
    public function exec() {
        $rates = array_merge(
            $this->fetchCryptoRates(),
            $this->fetchFiatRates()
        );

        RedisAdapter::shared()->set(WalletModel::REDIS_RATES_KEY, json_encode($rates), 3600 * 3);
    }

    private function fetchFiatRates(): array {
        $crypto = ['btc', 'ltc', 'eth'];
	    $fiat = ['usd', 'rub', 'idr'];

	    $result = [];
        $curl = new CurlAdapter();
	    foreach ($crypto as $crypto_currency) {
	        foreach ($fiat as $fiat_currency) {
                $resp = $curl->fetchGet('https://api.coinbase.com/v2/prices/' . $crypto_currency . '-' . $fiat_currency . '/sell');
                $resp = json_decode($resp, true);
                if (!$resp || !isset($resp['data'])) {
                    continue;
                }

                $pair = $crypto_currency . '/' . $fiat_currency;
                $amount = (double) $resp['data']['amount'];

                RedisAdapter::shared()->set('ex_pair_rate_' . $pair, $amount, 3600 * 3);
                usleep(50);

                $result[$pair] = $amount;
            }
        }

	    return $result;
    }

    private function fetchCryptoRates(): array {
        $binance_map = [
            "ETH/BTC" => "eth/btc",
            "LTC/BTC" => "ltc/btc",
            "BTC/USDT" => "btc/usdt",
            "ETH/USDT" => "eth/usdt",
            "LTC/USDT" => "ltc/usdt",
            "LTC/ETH" => "ltc/eth",
            "BCHABC/BTC" => "bchabc/btc",
            "BCHABC/USDT" => "bchabc/usdt",
            "XRP/BTC" => "xrp/btc",
            "XRP/USDT" => "xrp/usdt",
            "XRP/ETH" => "xrp/eth",
        ];

        $binance = new Binance();
        $tickers = $binance->getTickers();

        $result = [];
        foreach ($binance_map as $binance_pair => $local_pair) {
            if (isset($tickers[$binance_pair])) {
                $price = (double) $tickers[$binance_pair]['info']['lastPrice'];
                $result[$local_pair] = $price;
                RedisAdapter::shared()->set('ex_pair_rate_' . $local_pair, $price, 3600 * 3);
            }
        }

        return $result;
    }
}
