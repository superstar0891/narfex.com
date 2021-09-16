<?php

namespace Core\Services\ExternalExchange;

class Binance extends Base {

    /* @var \ccxt\binance $exchange */
    var $exchange;

    public function __construct(?string $key = null, ?string $secret = null) {
        $this->exchange = new \ccxt\binance([
            'apiKey' => $key,
            'secret' => $secret,
            'enableRateLimit' => true,
            'defaultType' => 'future',
        ]);
    }

    public function getPosition(string $symbol): array {
        $balance = $this->exchange->fetch_balance([
            'type' => 'future',
        ]);
        print_r($balance['positions']);

        return [];
    }

    public function mapSymbol(string $symbol): ?string {
        $map = [
            \Symbols::BTCUSD => 'BTC/USDT',
        ];

        return $map[$symbol] ?? null;
    }

    public function getTickers() {
        return $this->exchange->fetch_tickers();
    }
}
