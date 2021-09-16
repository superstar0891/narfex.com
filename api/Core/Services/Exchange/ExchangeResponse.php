<?php


namespace Core\Services\Exchange;


use Models\BalanceModel;
use Models\SwapModel;
use Models\WalletModel;
use Serializers\BalanceSerializer;
use Serializers\WalletSerializer;

class ExchangeResponse {
    private $from;
    private $from_type;
    private $to;
    private $to_type;
    private $history_item;

    public function __construct($from, $to, $history_item) {
        $this->from_type = get_class($from) === WalletModel::class ? 'wallet' : 'balance';
        $this->to_type = get_class($to) === WalletModel::class ? 'wallet' : 'balance';
        $this->from = $from;
        $this->to = $to;
        $this->history_item = $history_item;
    }

    /**
     * @return WalletModel|BalanceModel
     */
    public function getFrom() {
        return $this->from;
    }

    public function getTo() {
        return $this->to;
    }

    public function getFromType() {
        return $this->from_type;
    }

    public function getToType() {
        return $this->to_type;
    }

    public function getHistoryItem(): SwapModel {
        return $this->history_item;
    }

    public function getSerializedFrom(): array {
        if (get_class($this->from) === BalanceModel::class) {
            return BalanceSerializer::listItem($this->from);
        }

        if (get_class($this->from) === WalletModel::class) {
            return WalletSerializer::listItem($this->from);
        }

        throw new \Exception('From is not a correct type');
    }

    public function getSerializedTo() {
        if (get_class($this->to) === BalanceModel::class) {
            return BalanceSerializer::listItem($this->to);
        }

        if (get_class($this->to) === WalletModel::class) {
            return WalletSerializer::listItem($this->to);
        }

        throw new \Exception('To is not a correct type');
    }
}
