<?php

namespace Core\Services\ExternalExchange;

use Core\App;

class Bitmex extends Base {
    public function __construct(string $key, string $secret) {
        $this->exchange = new \ccxt\bitmex([
            'apiKey' => $key,
            'secret' => $secret,
            'enableRateLimit' => true,
        ]);

        if (App::isDevelopment() && isset($this->exchange->urls['test'])) {
            $this->exchange->urls['api'] = $this->exchange->urls['test'];
            $this->exchange->timeout = 30000;
        }

        $this->exchange->load_markets();
        $this->exchange->markets['XBTUSD'] = ['id' => 'XBTUSD', 'active' => true];
        $this->exchange->markets['ETHUSD'] = ['id' => 'ETHUSD', 'active' => true];
    }

    public function getLastPrice(string $symbol): float {
        $ticker = $this->exchange->request('instrument', 'private', 'GET', [
            'symbol' => $symbol,
        ])[0];
        return $ticker['bidPrice'];
    }

    public function mapSymbol(string $symbol): string {
        $map = [
            \Symbols::BTCUSD => 'XBTUSD',
        ];

        return $map[$symbol] ?? $symbol;
    }
}
