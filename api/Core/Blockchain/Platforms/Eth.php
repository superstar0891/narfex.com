<?php

namespace Blockchain\Platforms;

use Blockchain\Exception\CallListenerMethodException;
use Blockchain\Exception\DisabledListenerException;

class Eth implements PlatformInterface {

    public function __construct() {
        if (!KERNEL_CONFIG['listeners']['eth']['enabled']) {
            throw new DisabledListenerException();
        }
    }

    public function getWalletInfo(string $address = null): array {
        $balance = $this->method('eth_getBalance', [
            $address,
            'latest'
        ]);

        return [
            'balance' => (double) hexdec($balance) / 1e18,
        ];
    }

    public function getBlockchainInfo(): array {
        return [
            'info' => $this->method('eth_syncing'),
        ];
    }

    public function genAddress($extra): string {
        $address = $this->method('personal_newAccount', [
            $extra
        ]);

        return $address;
    }

    public function sendToAddress(?string $from_address, string $to_address, float $amount, string $password = null): string {
        if (!$from_address) {
            throw new DisabledListenerException();
        }

        $gas_price = $this->gasPrice();

        $tx = [
            'from' => $from_address,
            'to' => $to_address,
            'value' => ('0x' . (string)dechex($amount * 1e18)),
            'gasPrice' => ('0x' . (string)dechex($gas_price)),
            'gas' => ('0x' . (string)dechex(KERNEL_CONFIG['listeners']['eth']['gas_limit'])),
        ];

        return $this->method('personal_sendTransaction', [$tx, $password]);
    }

    public function getTransactionInfo(string $transaction_id): array {
        $info = $this->method('eth_getTransactionByHash', [$transaction_id]);
        $current_block = hexdec($this->method('eth_blockNumber')) ?: 0;
        $block_number = hexdec($info['blockNumber']);

        if (!$current_block || !$block_number) {
            $confirmations = 0;
        } else {
            $confirmations = $current_block - $block_number;
        }

        return [
            'hash' => $info['hash'],
            'amount' => hexdec($info['value']) / 1e18,
            'confirmations' => $confirmations,
            'to' => $info['to'],
            'from' => $info['from'],
            'blockHash' => $info['blockHash']
        ];
    }

    public function getRawTransactionInfo(string $transaction_id, string $block_hash = null): array {
        return $this->getTransactionInfo($transaction_id);
    }

    public function getBlockInfo(string $block_hash): array {
        $info = $this->method('eth_getBlockByHash', [$block_hash]);
        if (!isset($info['result'])) {
            throw new CallListenerMethodException(json_encode($info));
        }

        return $info['result'];
    }

    /* @param string $method
     * @param  array $params
     * @return mixed
     *
     * @throws CallListenerMethodException
     */
    private function method(string $method, array $params = []) {
        $conf = KERNEL_CONFIG['blockchain_proxy'];

        $url = $conf['host'] . '?' . http_build_query([
                'currency' => 'eth',
                'secret' => $conf['secret'],
            ]);

        $call = curl_init();
        curl_setopt($call, CURLOPT_URL, $url);
        curl_setopt($call, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($call, CURLOPT_CUSTOMREQUEST, 'POST');

        curl_setopt($call, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);

        $post_fields = [
            'jsonrpc' => '2.0',
            'id' => 'msg',
            'method' => $method,
            'params' => $params,
        ];
        $url_post_fields = json_encode($post_fields);
        curl_setopt($call, CURLOPT_POSTFIELDS, $url_post_fields);

        $res = curl_exec($call);
        curl_close($call);

        $res_json = $res ? json_decode($res, true) : null;
        if (!$res_json || !isset($res_json['result'])) {
            throw new CallListenerMethodException($res);
        }

        return $res_json['result'];
    }

    public function addGas(float $amount, float $gas_price): float {
        return ($amount * 1e18 + $gas_price * KERNEL_CONFIG['listeners']['eth']['gas_limit']) / 1e18;
    }

    public function subGas(float $amount, float $gas_price): float {
        return ($amount * 1e18 - $gas_price * KERNEL_CONFIG['listeners']['eth']['gas_limit']) / 1e18;
    }

    public function gasPrice() {
        $ipc_gas = $this->method('eth_gasPrice');
        return hexdec($ipc_gas);
    }
}
