<?php

namespace Cron;

use Blockchain\Platforms\Eth;
use Core\Blockchain\Factory;
use Db\Where;
use Models\AddressModel;
use Models\TransactionModel;

class EthTransferCronJob implements CronJobInterface {
    public function exec() {
        $transactions = TransactionModel::queryBuilder()
            ->columns(['user_wallet'], true)
            ->where(Where::and()
                ->set('category', Where::OperatorEq, 'receive')
                ->set('currency', Where::OperatorEq, 'eth')
            )->groupBy(['user_wallet'])
            ->orderBy(['created_at' => 'desc'])
            ->limit(3000)
            ->select();

        $addresses = AddressModel::select(Where::and()
            ->set('currency', Where::OperatorEq, 'eth')
            ->set('address', Where::OperatorIN, array_column($transactions, 'user_wallet'))
            ->set('address', Where::OperatorNotEq, KERNEL_CONFIG['eth_root_address'])
        );

        /* @var Eth $inst */
        $inst = Factory::getInstance('eth');

        echo 'start..'.PHP_EOL;

        $gas_price = $inst->gasPrice();

        $conf = KERNEL_CONFIG['blockchain_proxy'];

        $url = $conf['host'] . '?' . http_build_query([
                'currency' => 'eth',
                'secret' => $conf['secret'],
            ]);

        $mh = curl_multi_init();
        $resources = [];

        /* @var \Models\AddressModel $address */
        foreach ($addresses as $address) {
            $balance = $inst->getWalletInfo($address->address);
            $amount = $inst->subGas($balance['balance'], $gas_price);

            if ($balance['balance'] > 0 && $amount > 0) {
                $options = $address->getOptions();

                $call = curl_init();
                curl_setopt($call, CURLOPT_URL, $url);
                curl_setopt($call, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($call, CURLOPT_CUSTOMREQUEST, 'POST');

                curl_setopt($call, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                ]);

                $tx = [
                    'from' => $address->address,
                    'to' => KERNEL_CONFIG['eth_root_address'],
                    'value' => ('0x' . (string)dechex($amount * 1e18)),
                    'gasPrice' => ('0x' . (string)dechex($gas_price)),
                    'gas' => ('0x' . (string)dechex(KERNEL_CONFIG['listeners']['eth']['gas_limit'])),
                ];

                $post_fields = [
                    'jsonrpc' => '2.0',
                    'id' => 'msg',
                    'method' => 'personal_sendTransaction',
                    'params' => [$tx, $options['passphrase']],
                ];
                $url_post_fields = json_encode($post_fields);
                curl_setopt($call, CURLOPT_POSTFIELDS, $url_post_fields);

                curl_multi_add_handle($mh, $call);
                $resources[] = $call;
            }

//            try {
//                $balance = $inst->getWalletInfo($address->address);
//                $amount = $inst->subGas($balance['balance'], $gas_price);
//                if ($balance['balance'] > 0 && $amount > 0) {
//                    $options = $address->getOptions();
//                    $inst->sendToAddress($address->address, KERNEL_CONFIG['eth_root_address'], $amount, $options['passphrase']);
//                }
//            } catch (\Exception $e) {
//
//            }
        }

        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh);
            }
        } while ($active && $status == CURLM_OK);

        foreach ($resources as $resource) {
            curl_multi_remove_handle($mh, $resource);
        }

        curl_multi_close($mh);

        die('Done');
    }
}
