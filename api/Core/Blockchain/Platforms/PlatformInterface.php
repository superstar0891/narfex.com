<?php

namespace Blockchain\Platforms;

use Blockchain\Exception\CallListenerMethodException;

interface PlatformInterface {
    /* @param string|null $address
     * @return array
     *
     * @throws CallListenerMethodException
    */
    public function getWalletInfo(string $address = null): array;

    /* @throws CallListenerMethodException */
    public function getBlockchainInfo(): array;

    /* @param mixed $extra
     * @return string
     *
     * @throws CallListenerMethodException
     */
    public function genAddress($extra): string;

    /* @param string|null $from_address
     * @param string $to_address
     * @param float $amount
     * @param string|null $password
     * @return string - transaction id
     *
     * @throws CallListenerMethodException
    */
    public function sendToAddress(?string $from_address, string $to_address, float $amount, ?string $password = null): string;

    /* @param string $transaction_id
     * @return array
     *
     * @throws CallListenerMethodException
     */
    public function getTransactionInfo(string $transaction_id): array;

    /* @param string $transaction_id
     * @param string|null $block_hash
     * @return array
     *
     * @throws CallListenerMethodException
     */
    public function getRawTransactionInfo(string $transaction_id, string $block_hash = null): array;

    /* @param string $block_hash
     * @return array
     *
     * @throws CallListenerMethodException
     */
    public function getBlockInfo(string $block_hash): array;
}
