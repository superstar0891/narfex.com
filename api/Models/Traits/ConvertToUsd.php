<?php

namespace Models\Traits;

use Core\App;
use Models\WalletModel;
use Modules\FiatWalletModule;

/**
 * Trait ConvertToUsd
 * @property string currency
 */
trait ConvertToUsd {
    protected $usd_rate;

    public function toUSD(): float {
        if ($this->usd_rate) {
            return $this->usd_rate;
        }

        if (strtolower($this->currency) == 'usd' || strtolower($this->currency) == 'usdt') {
            return 1;
        }

        if (strtolower($this->currency) == CURRENCY_FNDR) {
            return settings()->token_price;
        }

        $key_base_to_usd = strtolower($this->currency) . '_usd';
        $key_base_to_btc = strtolower($this->currency) . '_btc';
        $key_btc_to_usd = 'btc_usd';

        $keys = WalletModel::$symbol_rate_keys_stage;

        if (array_key_exists($key_base_to_usd, $keys)) {
            $rate = WalletModel::getRateFromCache($key_base_to_usd);
            $this->usd_rate = $rate;
            return $rate;
        } elseif (array_key_exists($key_base_to_btc, $keys)) {
            $base_to_btc_rate = WalletModel::getRateFromCache($key_base_to_btc);
            $btc_to_usd_rate = WalletModel::getRateFromCache($key_btc_to_usd);
            $rate = $base_to_btc_rate * $btc_to_usd_rate;
            $this->usd_rate = $rate;
            return $rate;
        } else {
            $currencies = WalletModel::availableCurrencies([], false);
            if ($currencies[$this->currency]['type'] === 'fiat') {
                $btc_to_base_rate = FiatWalletModule::getRate(CURRENCY_BTC, $this->currency, false);
                if (!$btc_to_base_rate) {
                    throw new \Exception('Can not get rate for currency ' . $this->currency);
                }
                $btc_to_usd_rate = WalletModel::getRateFromCache($key_btc_to_usd);
                $rate = $btc_to_usd_rate / $btc_to_base_rate;
                $this->usd_rate = $rate;
                return $rate;
            } else {
                throw new \Exception('Bad currency: ' . $this->currency);
            }
        }
    }
}
