<?php

namespace Core\Services\ExternalExchange;

abstract class Base {
    const SIDE_SELL = 'sell';
    const SIDE_BUY = 'buy';

    /* @var \ccxt\bitmex $exchange */
    var $exchange;

    public function getLastPrice(string $symbol): float {
        $ticker = $this->exchange->fetch_ticker($symbol);
        return $ticker['bid'];
    }

    /**
     * @param string $symbol
     * @param string $side
     * @param float $amount
     * @param bool $reduce_only
     * @throws \Exception
     */
    public function openMarketOrder(string $symbol, string $side, float $amount, $reduce_only = false) {
        $params = [];
        if ($reduce_only) {
            $params['execInst'] = 'ReduceOnly';
        }
        return $this->exchange->create_market_order($this->mapSymbol($symbol), $side, $amount, null, $params);
    }

    public function getPosition(string $symbol): array {
        $positions = $this->exchange->request('position', 'private');

        $position = null;
        foreach ($positions as $row) {
            if ($row['symbol'] === $symbol) {
                $position = $row;
            }
        }

        if (!$position) {
            return ['amount' => 0];
        }

        return [
            'roe' => $position['unrealisedRoePcnt'],
            'position' => $position['currentQty'] == 0 ? 'none' : ($position['currentQty'] > 0 ? self::SIDE_BUY : self::SIDE_SELL),
            'amount' => abs($position['currentQty']),
            'ts' => strtotime($position['timestamp']),
            'position_price' => $position['avgEntryPrice'],
            'lastPrice' => $position['lastPrice'],
            'prevClosePrice' => $position['prevClosePrice'],
            'realisedPnl' => (double) $position['realisedPnl'],
            'leverage' => (int) $position['leverage'],
        ];
    }

    public function getBalance(int $leverage = 1): float {
        $ret = $this->exchange->fetch_balance();
        return (float) $ret['BTC']['free'] * $leverage;
    }

    public function getOrders(string $symbol): array {
        return $this->exchange->fetch_orders($this->mapSymbol($symbol), null, 100, [
            'reverse' => 1,
        ]);
    }

    public function mapSymbol(string $symbol): ?string {
        throw new \Exception('Need to override "mapSymbol" method');
    }

    public function setLeverage(string $symbol, int $leverage = 20): array {
        return $this->exchange->request('position/leverage', 'private', 'POST', [
            'symbol' => $symbol,
            'leverage' => $leverage,
        ]);
    }
}
