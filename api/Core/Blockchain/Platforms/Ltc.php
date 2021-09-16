<?php

namespace Blockchain\Platforms;

use Blockchain\Exception\CallListenerMethodException;
use Blockchain\Exception\DisabledListenerException;

class Ltc implements PlatformInterface {

    public function __construct() {
        if (!KERNEL_CONFIG['listeners']['ltc']['enabled']) {
            throw new DisabledListenerException();
        }
    }

    public function getWalletInfo(string $address = null): array {
        $wallet_info = $this->method('getwalletinfo');
        if (!isset($wallet_info['result'])) {
            throw new CallListenerMethodException(json_encode($wallet_info));
        }

        return [
            'name' => $wallet_info['result']['walletname'],
            'balance' => (double) $wallet_info['result']['balance'],
        ];
    }

    public function getBlockchainInfo(): array {
        $info = $this->method('getblockchaininfo');
        return [
            'info' => isset($info['result']) ? $info['result'] : $info['error'],
        ];
    }

    public function genAddress($extra): string {
        $ret = $this->method('getnewaddress', [
            $this->getUserAccount($extra),
        ]);

        if (!isset($ret['result'])) {
            throw new CallListenerMethodException(json_encode($ret));
        }

        return $ret['result'];
    }

    public function sendToAddress(?string $from_address, string $to_address, float $amount, ?string $password = null): string {
        $rpc_send = $this->method('sendtoaddress', [
            $to_address,
            $amount,
        ]);

        if (!isset($rpc_send['result'])) {
            throw new CallListenerMethodException(json_encode($rpc_send));
        }

        return $rpc_send['result'];
    }

    public function getTransactionInfo(string $transaction_id): array {
        $rpc_tx = $this->method('gettransaction', [
            $transaction_id,
        ]);

        if (!isset($rpc_tx['result'])) {
            throw new CallListenerMethodException(json_encode($rpc_tx));
        }

        return $rpc_tx['result'];
    }

    public function getRawTransactionInfo(string $transaction_id, string $block_hash = null): array {
        $rpc_tx = $this->method('getrawtransaction', [
            $transaction_id, true, $block_hash
        ]);

        if (!isset($rpc_tx['result']) || $rpc_tx['error'] !== null) {
            throw new CallListenerMethodException(json_encode($rpc_tx));
        }

        return $rpc_tx['result'];
    }

    public function getBlockInfo(string $block_hash): array {
        $rpc_block = $this->method('getblock', [
            $block_hash, 2,
        ]);

        if (!isset($rpc_block['result'])) {
            throw new CallListenerMethodException(json_encode($rpc_block));
        }

        return $rpc_block['result'];
    }

    /* @param string $method
     * @param  array $params
     * @return mixed
     *
     * @throws CallListenerMethodException
     */
    private function method( string $method, array $params = []) {
        $conf = KERNEL_CONFIG['blockchain_proxy'];

        $url = $conf['host'] . '?' . http_build_query([
                'currency' => 'ltc',
                'secret' => $conf['secret'],
            ]);

        $call = curl_init();
        curl_setopt($call, CURLOPT_URL, $url);
        curl_setopt($call, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($call, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($call, CURLOPT_TIMEOUT, 10);

        curl_setopt($call, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);

        $post_fields = [
            'jsonrpc' => '1.0',
            'id' => 'curl_request',
            'method' => $method,
            'params' => $params,
        ];
        $url_post_fields = json_encode($post_fields);
        curl_setopt($call, CURLOPT_POSTFIELDS, $url_post_fields);

        $res = curl_exec($call);
        curl_close($call);

        $res = $res ? json_decode($res, true) : null;
        if (!$res) {
            throw new CallListenerMethodException();
        }

        return $res;
    }

    private function getUserAccount($identity): string {
        return 'user_account_' . $identity;
    }
}
