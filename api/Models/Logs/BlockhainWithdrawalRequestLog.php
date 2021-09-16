<?php

namespace Models\Logs;

class BlockhainWithdrawalRequestLog extends LogHelper {
    const PROCESS_WITHDRAWAL_REQUEST = 'process_withdrawal_request',
        REJECT_WITHDRAWAL_REQUEST = 'reject_withdrawal_request',
        PAUSED_WITHDRAWAL_REQUEST = 'paused_withdrawal_request',
        START_WITHDRAWAL_REQUEST = 'start_withdrawal_request';

    /** @var array */
    public static $actions = [
        self::PROCESS_WITHDRAWAL_REQUEST,
        self::REJECT_WITHDRAWAL_REQUEST,
        self::PAUSED_WITHDRAWAL_REQUEST,
        self::START_WITHDRAWAL_REQUEST,
    ];

    /** @var string */
    private $currency;
    /** @var double */
    private $amount;
    /** @var int */
    private $withdrawal_id;

    public static $fields = [
        'currency',
        'amount',
        'withdrawal_id',
    ];

    public function __construct(array $extra) {
        parent::__construct($extra);
        $this->setCurrency($extra['currency'])
            ->setAmount($extra['amount'])
            ->setWithdrawalId($extra['withdrawal_id']);

    }

    public function tableColumn(): string {
        return sprintf(
            'Withdrawal request: %s, currency: %s, amount: %s',
            $this->getWithdrawalId(),
            $this->getCurrency(),
            $this->getAmount()
        );
    }

    /**
     * @return string
     */
    public function getCurrency(): string {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return BlockhainWithdrawalRequestLog
     */
    public function setCurrency(string $currency): BlockhainWithdrawalRequestLog {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return float
     */
    public function getAmount(): float {
        return $this->amount;
    }

    /**
     * @param float $amount
     * @return BlockhainWithdrawalRequestLog
     */
    public function setAmount(float $amount): BlockhainWithdrawalRequestLog {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return int
     */
    public function getWithdrawalId(): int {
        return $this->withdrawal_id;
    }

    /**
     * @param int $withdrawal_id
     * @return BlockhainWithdrawalRequestLog
     */
    public function setWithdrawalId(int $withdrawal_id): BlockhainWithdrawalRequestLog {
        $this->withdrawal_id = $withdrawal_id;
        return $this;
    }

}
