<?php


namespace Core\Services\Withdrawal;


use Core\Exceptions\Wallet\Withdrawal\WithdrawalMinAmountException;
use Core\Exceptions\Withdrawal\BalanceNotFoundException;
use Core\Exceptions\Withdrawal\InsufficientFundsException;
use Core\Services\Merchant\XenditService;
use Db\Transaction;
use Models\BalanceModel;
use Models\UserBalanceHistoryModel;
use Models\UserModel;
use Models\WithdrawalModel;
use Modules\BalanceModule;
use Modules\FeeModule;

class WithdrawalService {
    /** @var UserModel */
    private $user;
    /** @var string */
    private $bank_code;
    /** @var string */
    private $account_number;
    /** @var string */
    private $account_holder_name;
    /** @var float */
    private $amount;
    /** @var float */
    private $fee;
    /** @var string */
    private $currency;
    /** @var string */
    private $email;
    /** @var BalanceModel */
    private $balance;
    /** @var string */
    private $provider;

    public function setUser(UserModel $user) {
        $this->user = $user;

        return $this;
    }

    public function setBankCode(string $bank_code) {
        $this->bank_code = $bank_code;

        return $this;
    }

    public function setProvider(string $provider) {
        $this->provider = $provider;

        return $this;
    }

    public function setAccountHolderName(string $account_holder_name) {
        $this->account_holder_name = $account_holder_name;

        return $this;
    }

    public function setCurrency(string $currency) {
        $this->currency = $currency;

        return $this;
    }

    public function setAccountNumber(string $account_number) {
        $this->account_number = $account_number;

        return $this;
    }

    public function setAmount(float $amount) {
        $this->amount = $amount;
        $this->fee = FeeModule::getFee($amount, $this->currency);

        return $this;
    }

    public function setEmail(?string $email) {
        $this->email = $email;

        return $this;
    }

    public function execute(): WithdrawalModel {
        $this->balance = BalanceModule::getBalanceOrCreate($this->user->id, $this->currency, BalanceModel::CATEGORY_FIAT);
        $total_amount = $this->amount + $this->fee;

        if ($total_amount < XenditService::MIN_WITHDRAWAL_AMOUNT) {
            throw new WithdrawalMinAmountException();
        }

        if ($this->balance->category !== BalanceModel::CATEGORY_FIAT) {
            throw new BalanceNotFoundException();
        }

        if (!$this->balance->checkAmount($total_amount)) {
            throw new InsufficientFundsException();
        }

        $withdrawal = new WithdrawalModel();
        $withdrawal->from_id = $this->balance->id;
        $withdrawal->from_type = UserBalanceHistoryModel::TYPE_BALANCE;
        $withdrawal->user_id = $this->user->id;
        $withdrawal->currency = $this->currency;
        $withdrawal->amount = $this->amount;
        $withdrawal->fee = $this->fee;
        $withdrawal->provider = $this->provider;
        if ($this->email) {
            $withdrawal->email_to = json_encode([$this->email]);
        }
        $withdrawal->account_number = $this->account_number;
        $withdrawal->account_holder_name = $this->account_holder_name;
        $withdrawal->bank_code = $this->bank_code;
        $withdrawal->status = UserBalanceHistoryModel::STATUS_CONFIRMATION;

        return Transaction::wrap(function() use ($total_amount, $withdrawal) {
            $this->balance->incrLockedAmount($total_amount);
            $this->balance->decrAmount($total_amount);
            $withdrawal->save();
            return $withdrawal;
        });
    }

    public function getBalance() {
        return $this->balance;
    }
}
