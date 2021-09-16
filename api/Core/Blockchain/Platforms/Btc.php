<?php

namespace Blockchain\Platforms;

use Blockchain\Exception\CallListenerMethodException;
use Blockchain\Exception\DisabledListenerException;
use Core\Response\JsonResponse;

class Btc implements PlatformInterface {

    private $wallet_name = null;

    public function __construct(?string $wallet_name = null) {
        if (!KERNEL_CONFIG['listeners']['btc']['enabled']) {
            throw new DisabledListenerException();
        }

        $this->wallet_name = $wallet_name;
        $this->initWallet();
    }

    public function getWalletInfo(string $address = null): array {
        $wallet_info = $this->method('/wallet/' . $this->wallet_name, 'getwalletinfo');
        if (!isset($wallet_info['result'])) {
            throw new CallListenerMethodException($wallet_info['error']);
        }

        return [
            'name' => $wallet_info['result']['walletname'],
            'balance' => (double) $wallet_info['result']['balance'],
        ];
    }

    public function getBlockchainInfo(): array {
        $info = $this->method('/', 'getblockchaininfo');
        return [
            'info' => isset($info['result']) ? $info['result'] : $info['error'],
        ];
    }

    public function genAddress($extra): string {
        $ret = $this->method('/wallet/' . $this->wallet_name, 'getnewaddress', [
            $this->getUserAccount($extra),
        ]);

        if (!isset($ret['result'])) {
            throw new CallListenerMethodException($ret['error']);
        }

        return $ret['result'];
    }

    public function sendToAddress(?string $from_address, string $to_address, float $amount, ?string $password = null): string {
        $rpc_send = $this->method('/wallet/' . $this->wallet_name, 'sendtoaddress', [
            $to_address,
            $amount,
        ]);

        if (!isset($rpc_send['result'])) {
            throw new CallListenerMethodException(json_encode($rpc_send));
        }

        return $rpc_send['result'];
    }

    public function getTransactionInfo(string $transaction_id): array {
        $rpc_tx = $this->method('/wallet/' . $this->wallet_name, 'gettransaction', [
            $transaction_id,
        ]);

        if (!isset($rpc_tx['result'])) {
            throw new CallListenerMethodException(json_encode($rpc_tx));
        }

        return $rpc_tx['result'];
    }

    public function getRawTransactionInfo(string $transaction_id, string $block_hash = null): array {
        $rpc_tx = $this->method('/wallet/' . $this->wallet_name, 'getrawtransaction', [
            $transaction_id, true, $block_hash
        ]);

        if (!isset($rpc_tx['result']) || $rpc_tx['error'] !== null) {
            throw new CallListenerMethodException(json_encode($rpc_tx));
        }

        return $rpc_tx['result'];
    }

    public function getBlockInfo(string $block_hash): array {
        $rpc_block = $this->method('/wallet/' . $this->wallet_name, 'getblock', [
            $block_hash, 2,
        ]);

        if (!isset($rpc_block['result'])) {
            throw new CallListenerMethodException(json_encode($rpc_block));
        }

        return $rpc_block['result'];
    }

    /* @param string $path
     * @param string $method
     * @param  array $params
     * @return mixed
     *
     * @throws CallListenerMethodException
     */
    private function method(string $path, string $method, array $params = []) {
        $conf = KERNEL_CONFIG['blockchain_proxy'];

        $url = $conf['host'] . '?' . http_build_query([
                'currency' => 'btc',
                'path' => $path,
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
            'jsonrpc' => '1.0',
            'id' => 'curl_request',
            'method' => $method,
            'params' => $params,
        ];
        $url_post_fields = json_encode($post_fields);
        curl_setopt($call, CURLOPT_POSTFIELDS, $url_post_fields);

        $res = curl_exec($call);
        curl_close($call);

        $res_json = $res ? json_decode($res, true) : null;
        if (!$res_json) {
            throw new CallListenerMethodException($res);
        }

        return $res_json;
    }

    /* @throws CallListenerMethodException */
    private function initWallet() {
        if (!$this->wallet_name) {
            $wallet_name = KERNEL_CONFIG['listeners']['btc']['wallet_name'];
        } else {
            $wallet_name = $this->wallet_name;
        }

        $info = $this->method('/wallet/' . $wallet_name, 'getwalletinfo');
        if (isset($info['error']) && $info['error']['code'] == -18) {
            $rpc_create = $this->method('/', 'createwallet', [
                $wallet_name,
            ]);

            if ($rpc_create && $rpc_create['result']) {
                $this->wallet_name = $rpc_create['result']['name'];
            } else {
                throw new CallListenerMethodException(json_decode($rpc_create));
            }
        } else {
            $this->wallet_name = $wallet_name;
        }
    }

    private function getUserAccount($identity): string {
        return 'user_account_' . $identity;
    }
}
