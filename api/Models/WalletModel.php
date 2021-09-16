<?php

namespace Models;

use Core\App;
use Core\Services\Redis\RedisAdapter;
use Db\Db;
use Db\Model\Field\BooleanField;
use Db\Model\Field\CharField;
use Db\Model\Field\DecimalField;
use Db\Model\Field\DoubleField;
use Db\Model\Field\IdField;
use Db\Model\Field\IntField;
use Db\Transaction;
use Models\Traits\ConvertToUsd;
use Modules\FiatWalletModule;
use Db\Model\Model;
use Db\Where;
use Exception;
use Modules\WalletModule;

/**
 * @property int id
 * @property int user_id
 * @property string address
 * @property string currency
 * @property double amount
 * @property string status
 * @property double profit
 * @property bool saving_enabled
 * @property int has_history
 */
class WalletModel extends Model {
    use ConvertToUsd;

    const REDIS_RATES_KEY = 'rates_from_cron';

    protected static $table_name = 'wallets';

    private static $rates = [];

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'user_id' => IdField::init(),
            'address' => CharField::init()->setLength(256)->setNull(true),
            'currency' => CharField::init()->setLength(16),
            'amount' => DoubleField::init()->setDefault(0),
            'status' => CharField::init()->setLength(16),
            'profit' => DecimalField::init()->setDefault(0),
            'saving_enabled' => IntField::init()->setLength(1)->setDefault(0),
            'has_history' => IntField::init()->setLength(1)->setDefault(0)
        ];
    }

    public static $symbol_rate_keys_stage = [
        'eth_btc' => 'ex_pair_rate_eth/btc',
        'ltc_btc' => 'ex_pair_rate_ltc/btc',
        'btc_usdt' => 'ex_pair_rate_btc/usdt',
        'btc_eurt' => 'ex_pair_rate_btc/eurt',
        'eth_usdt' => 'ex_pair_rate_eth/usdt',
        'eth_eurt' => 'ex_pair_rate_eth/eurt',
        'ltc_eth' => 'ex_pair_rate_ltc/eth',
        'ltc_eurt' => 'ex_pair_rate_ltc/eurt',
        'ltc_usdt' => 'ex_pair_rate_ltc/usdt',
        'bchabc_btc' => 'ex_pair_rate_bchabc/btc',
        'bchabc_usdt' => 'ex_pair_rate_bchabc/usdt',
        'xrp_btc' => 'ex_pair_rate_xrp/btc',
        'xrp_usdt' => 'ex_pair_rate_xrp/usdt',
        'xrp_eth' => 'ex_pair_rate_xrp/eth',
        'nrfx_usdt' => 'ex_pair_rate_nrfx/usdt',

        // fiat
        'btc_usd' => 'ex_pair_rate_btc/usd',
        'btc_eur' => 'ex_pair_rate_btc/eur',
        'btc_rub' => 'ex_pair_rate_btc/rub',
        'btc_idr' => 'ex_pair_rate_btc/idr',
        'btc_cny' => 'ex_pair_rate_btc/cny',
        'btc_gbp' => 'ex_pair_rate_btc/gbp',

        'eth_usd' => 'ex_pair_rate_eth/usd',
        'eth_eur' => 'ex_pair_rate_eth/eur',
        'eth_rub' => 'ex_pair_rate_eth/rub',
        'eth_idr' => 'ex_pair_rate_eth/idr',
        'eth_cny' => 'ex_pair_rate_eth/cny',
        'eth_gbp' => 'ex_pair_rate_eth/gbp',

        'ltc_usd' => 'ex_pair_rate_ltc/usd',
        'ltc_eur' => 'ex_pair_rate_ltc/eur',
        'ltc_rub' => 'ex_pair_rate_ltc/rub',
        'ltc_idr' => 'ex_pair_rate_ltc/idr',
        'ltc_cny' => 'ex_pair_rate_ltc/cny',
        'ltc_gbp' => 'ex_pair_rate_ltc/gbp',
    ];

    public static function availableCurrencies(array $userCurrencies = [], $to_usd_needed = false) :array {
        return [
            'btc' => [
                'abbr' => 'btc',
                'name' => 'Bitcoin',
                'icon' => KERNEL_CONFIG['static_host'] . '/img/currencies/bitcoin.svg',
                'can_generate' => true,
                'is_exists' => in_array('btc', $userCurrencies),
                'type' => 'crypto',
                'maximum_fraction_digits' => 8,
                'color' => '#ff9900',
                'gradient' => ['#ff9900', '#ff9900'],
                'is_available' => true,
                'can_exchange' => true,
                'to_usd' => self::getUsdRate('btc', $to_usd_needed)
            ],
            'eth' => [
                'abbr' => 'eth',
                'name' => 'Ethereum',
                'icon' => KERNEL_CONFIG['static_host'] . '/img/currencies/ethereum.svg',
                'can_generate' => true,
                'is_exists' => in_array('eth', $userCurrencies),
                'type' => 'crypto',
                'maximum_fraction_digits' => 8,
                'color' => '#908EE8',
                'gradient' => ['#896ADF', '#98B1F1'],
                'is_available' => true,
                'can_exchange' => true,
                'to_usd' => self::getUsdRate('eth', $to_usd_needed)
            ],
            'ltc' => [
                'abbr' => 'ltc',
                'name' => 'Litecoin',
                'icon' => KERNEL_CONFIG['static_host'] . '/img/currencies/litecoin.svg',
                'can_generate' => true,
                'is_exists' => in_array('ltc', $userCurrencies),
                'type' => 'crypto',
                'maximum_fraction_digits' => 8,
                'color' => '#75BBE7',
                'gradient' => ['#619ABE', '#7AC4F2'],
                'is_available' => true,
                'can_exchange' => false,
                'to_usd' => self::getUsdRate('ltc', $to_usd_needed)
            ],
            'xrp' => [
                'abbr' => 'xrp',
                'name' => 'Ripple',
                'icon' => KERNEL_CONFIG['static_host'] . '/img/currencies/ripple.svg',
                'can_generate' => false,
                'is_exists' => false,
                'type' => 'crypto',
                'maximum_fraction_digits' => 8,
                'color' => '#3697E1',
                'gradient' => ['#3FBEFC', '#2C74C7'],
                'can_exchange' => false,
                'to_usd' => self::getUsdRate('xrp', $to_usd_needed)
            ],
            'bchabc' => [
                'abbr' => 'bchabc',
                'name' => 'Bitcoin Cash ABC',
                'icon' => KERNEL_CONFIG['static_host'] . '/img/currencies/bitcoincash.svg',
                'can_generate' => false,
                'is_exists' => false,
                'type' => 'crypto',
                'maximum_fraction_digits' => 8,
                'color' => '#F8AC4D',
                'gradient' => ['#F8A15D', '#F7B73B'],
                'can_exchange' => false,
                'to_usd' => self::getUsdRate('bchabc', $to_usd_needed)
            ],
            'usdt' => [
                'abbr' => 'usdt',
                'name' => 'Tether',
                'icon' => KERNEL_CONFIG['static_host'] . '/img/currencies/dollar.svg',
                'can_generate' => false,
                'is_exists' => false,
                'type' => 'crypto',
                'maximum_fraction_digits' => 8,
                'color' => '#fff',
                'gradient' => ['#5AA58C', '#5AA58C'],
                'can_exchange' => false,
                'to_usd' => self::getUsdRate('usdt', $to_usd_needed)
            ],
            'usd' => [
                'abbr' => 'usd',
                'name' => 'U.S. Dollar',
                'icon' => KERNEL_CONFIG['static_host'] . '/img/currencies/dollar.svg',
                'can_generate' => false,
                'is_exists' => false,
                'type' => 'fiat',
                'maximum_fraction_digits' => 2,
                'color' => '#6CC592',
                'gradient' => ['#62B27D', '#77D9A8'],
                'can_exchange' => true,
                'to_usd' => self::getUsdRate('usd', $to_usd_needed)
            ],
            'eur' => [
                'abbr' => 'eur',
                'name' => 'Euro',
                'icon' => KERNEL_CONFIG['static_host'] . '/img/currencies/euro.svg',
                'can_generate' => false,
                'is_exists' => false,
                'type' => 'fiat',
                'maximum_fraction_digits' => 2,
                'color' => '#6CC592',
                'gradient' => ['#62B27D', '#77D9A8'],
                'can_exchange' => false,
                'to_usd' => self::getUsdRate('eur', $to_usd_needed)
            ],
            'rub' => [
                'abbr' => 'rub',
                'name' => 'Rubles',
                'icon' => KERNEL_CONFIG['static_host'] . '/img/currencies/rubles.svg',
                'can_generate' => false,
                'is_exists' => false,
                'type' => 'fiat',
                'maximum_fraction_digits' => 2,
                'color' => '#6CC592',
                'gradient' => ['#62B27D', '#77D9A8'],
                'can_exchange' => true,
                'to_usd' => self::getUsdRate('rub', $to_usd_needed)
            ],
            'idr' => [
                'abbr' => 'idr',
                'name' => 'Indonesian Rupiah',
                'icon' => KERNEL_CONFIG['static_host'] . '/img/currencies/indonesian-rupiah.svg',
                'can_generate' => false,
                'is_exists' => false,
                'type' => 'fiat',
                'maximum_fraction_digits' => 2,
                'color' => '#6CC592',
                'gradient' => ['#62B27D', '#77D9A8'],
                'can_exchange' => true,
                'to_usd' => self::getUsdRate('idr', $to_usd_needed)
            ],
            'cny' => [
                'abbr' => 'cny',
                'name' => 'Chinese Yuan',
                'icon' => KERNEL_CONFIG['static_host'] . '/img/currencies/yuan-cny.svg',
                'can_generate' => false,
                'is_exists' => false,
                'type' => 'fiat',
                'maximum_fraction_digits' => 2,
                'color' => '#6CC592',
                'gradient' => ['#62B27D', '#77D9A8'],
                'can_exchange' => false,
                'to_usd' => self::getUsdRate('cny', $to_usd_needed)
            ],
            'gbp' => [
                'abbr' => 'gbp',
                'name' => 'British Pound',
                'icon' => KERNEL_CONFIG['static_host'] . '/img/currencies/gbp-pound.svg',
                'can_generate' => false,
                'is_exists' => false,
                'type' => 'fiat',
                'maximum_fraction_digits' => 2,
                'color' => '#6CC592',
                'gradient' => ['#62B27D', '#77D9A8'],
                'can_exchange' => false,
                'to_usd' => self::getUsdRate('gbp', $to_usd_needed)
            ],
            CURRENCY_FNDR => [
                'abbr' => CURRENCY_FNDR,
                'name' => 'Findiri Token',
                'icon' => KERNEL_CONFIG['static_host'] . '/img/currencies/fndr.svg',
                'can_generate' => false,
                'is_exists' => true,
                'type' => 'crypto',
                'maximum_fraction_digits' => 8,
                'color' => '#365FD9',
                'gradient' => ['#365FD9', '#4070FF'],
                'is_available' => true,
                'can_exchange' => false,
                'to_usd' => WalletModule::getTokenRate('usd'),
            ],
        ];
    }

    public static function isFiat($currency) {
        $currencies = self::availableCurrencies([], false);

        if (isset($currencies[$currency])) {
            return $currencies[$currency]['type'] === 'fiat';
        }

        return false;
    }

    public static function getRateFromCache(string $pair): ?float {
        if (App::isLocalEnvironment()) {
            [$base, $quote] = explode('_', $pair);
            return FiatWalletModule::getRate($base, $quote, false);
        }
        if (empty(self::$rates)) {
            self::$rates = json_decode(RedisAdapter::shared()->get(self::REDIS_RATES_KEY), true);
        }

        return self::$rates[$pair] ?? 0;
    }

    public function toBTC() {
        return FiatWalletModule::getAmountInAnotherCurrency(CURRENCY_USD, CURRENCY_BTC, $this->toUSD());
    }

    public static function getRate(string $quote, ?string $currency = null, bool $from_exchange = false): float {
        if (!$currency) {
            $currencies = explode('_', $quote);
            if (!isset($currencies[1])) {
                throw new \Exception();
            }

            [$quote, $currency] = $currencies;
        }
        if (strtolower($quote) === strtolower($currency)) {
            return 1;
        }

        if (strtolower($currency) == CURRENCY_FNDR && !$from_exchange) {
            return WalletModule::getTokenRate($quote);
        }

        $key_base_to_quote = strtolower($currency) . '_' . strtolower($quote);
        $key_quote_to_base = strtolower($quote) . '_' . strtolower($currency);
        $keys = self::$symbol_rate_keys_stage;

        if (array_key_exists($key_base_to_quote, $keys)) {
            $rate = self::getRateFromCache($key_base_to_quote);
        } elseif (array_key_exists($key_quote_to_base, $keys)) {
            $rate = self::getRateFromCache($key_quote_to_base);
            if ($rate > 0) {
                $rate = 1 / $rate;
            } else {
                if (App::isDevelopment()) {
                    return 1;
                }
                throw new Exception('Rate is zero');
            }
        } else {
            throw new Exception('Unknown pair: ' . $key_base_to_quote . ' or ' . $key_quote_to_base);
        }

        return $rate;
    }

    public function alignAmount(string $quote): float {
        return $this->amount * self::getRate($quote, $this->currency);
    }

    public function checkAmount($amount): bool {
        $wallet_row = Db::get(static::getTableName(),null, Where::equal('id', $this->id), true);
        if ($wallet_row['amount'] >= $amount) {
            return true;
        }

        return false;
    }

    public function checkProfit($amount): bool {
        $wallet_row = Db::get(static::getTableName(),null, Where::equal('id', $this->id), true);
        if ($wallet_row['profit'] >= $amount) {
            return true;
        }

        return false;
    }

    public function addAmount($amount): bool {
        return Transaction::wrap(function () use ($amount) {
            $ret = Db::add(static::getTableName(), 'amount', $amount, Where::equal('id', (int) $this->id));
            if ($ret) {
                $this->amount += $amount;
            }

            if (!$this->has_history && $this->amount != 0) {
                $this->has_history = 1;
                $this->save();
            }

            return $ret;
        });
    }

    public function subAmount($amount): bool {
        $ret = Db::sub(static::getTableName(), 'amount', $amount, Where::equal('id', (int) $this->id));
        if ($ret) {
            $this->amount -= $amount;
        }
        return $ret;
    }

    public function addProfit($amount): bool {
        $ret = Db::add(static::getTableName(), 'profit', $amount, Where::equal('id', (int) $this->id));
        if ($ret) {
            $this->profit += $amount;
        }

        return $ret;
    }

    public function subProfit($amount): bool {
        $ret = Db::sub(static::getTableName(), 'profit', $amount, Where::equal('id', (int) $this->id));
        if ($ret) {
            $this->profit -= $amount;
        }
        return $ret;
    }

    private static function getUsdRate($currency, $usd_needed = true): ?float {
        if ($usd_needed) {
            return FiatWalletModule::getRate($currency, 'usd');
        }

        return null;
    }

    public function isSavingAvailable(): bool {
        if ($this->currency !== CURRENCY_FNDR) {
            return false;
        }

        return $this->amount > 100;
    }
}
