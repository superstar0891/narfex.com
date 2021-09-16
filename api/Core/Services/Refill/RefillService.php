<?php


namespace Core\Services\Refill;


use Core\Services\Telegram\SendService;
use Db\Transaction;
use Db\Where;
use Models\BalanceModel;
use Models\RefillModel;
use Models\UserBalanceHistoryModel;
use Models\UserModel;
use Modules\FeeModule;
use Modules\NotificationsModule;

class RefillService {
    /** @var UserModel */
    private $user;
    /** @var string */
    private $currency;
    /** @var float */
    private $amount;
    /** @var float */
    private $fee;
    /** @var string */
    private $bank_code;
    /** @var string */
    private $external_id;
    /** @var BalanceModel */
    private $balance;
    /** @var string */
    private $provider;
    /** @var RefillModel */
    private $model = null;

    public function setUser(UserModel $user) {
        $this->user = $user;

        return $this;
    }

    public function setCurrency(string $currency) {
        $this->currency = $currency;

        return $this;
    }

    public function setAmount(float $amount) {
        $this->amount = $amount;
        $this->fee = FeeModule::getFee($amount, $this->currency);

        return $this;
    }

    public function setBankCode(string $bank_code) {
        $this->bank_code = $bank_code;

        return $this;
    }

    public function setExternalId(string $external_id) {
        $this->external_id = $external_id;

        return $this;
    }

    public function setProvider(string $provider) {
        $this->provider = $provider;

        return $this;
    }

    public function setBalance(BalanceModel $balance) {
        $this->balance = $balance;

        return $this;
    }

    public function execute() {
        $total_amount = $this->amount - $this->fee;
        $refill = new RefillModel();
        $refill->currency = $this->currency;
        $refill->external_id = $this->external_id;
        $refill->bank_code = $this->bank_code;
        $refill->provider = $this->provider;
        $refill->user_id = $this->user->id;
        $refill->fee = $this->fee;
        $refill->amount = $total_amount;
        $refill->to_id = $this->balance->id;
        $refill->to_type = UserBalanceHistoryModel::TYPE_BALANCE;
        Transaction::wrap(function() use ($refill, $total_amount) {
            if (RefillModel::first(Where::equal('external_id', $this->external_id))) {
                throw new \Exception('Refill already exist');
            }

            $refill->save();
            $this->model = $refill;
            $this->balance->incrAmount($total_amount);
            NotificationsModule::sendRefillNotification($refill);
        });

        return $this;
    }

    public function getRefillModel() {
        return $this->model;
    }

    public function sendTelegramNotificationToAdmins() {
        $telegram_service = new SendService();
        $telegram_service->sendMessage('#fiat_refill' . PHP_EOL . sprintf('ID: %s, %s(%s), Date: %s, %s',
                $this->model->id,
                $this->user->login,
                $this->user->id,
                (new \DateTime())->setTimestamp($this->model->created_at_timestamp)->format('d.m.Y H:i:s'),
                formatNum($this->model->amount, 2) . ' IDR'
            ));
    }
}
