<?php


namespace Core\Command;


use Db\Where;
use Models\DepositModel;
use Models\TransferModel;
use Models\WithdrawDisabledModel;

class Osipov implements CommandInterface {
    private $name;
    private $params;

    function __construct(string $name, ?array $params = null) {
        $this->name = $name;
        $this->params = $params;
    }

    public function exec() {
        echo $this->name . PHP_EOL;
        print_r($this->params);
        switch ($this->name) {
            case 'disable_users':
                $this->disableUsers();
                break;
            default:
                die('Unknown job: ' . $this->name);
        };
    }

    private function disableUsers() {
        $except_users = DepositModel::USERS_WITH_ENABLED_DEPOSITS;

        $deposits = DepositModel::queryBuilder()
            ->columns(['user_id', 'status'])
            ->where(Where::and()->set(Where::in('status', ['done', 'accepted'])))
            ->select();
        $deposits = DepositModel::rowsToSet($deposits);
        $users_ids_from_deposits = $deposits->column('user_id');
        $transfers = TransferModel::queryBuilder()->columns(['from_user_id', 'to_user_id'])->select();
        $transfers = TransferModel::rowsToSet($transfers);
        $users_ids_from_transfers = $transfers->column('from_user_id');
        $users_ids_to_transfers = $transfers->column('to_user_id');
        $users_ids = array_merge($users_ids_from_deposits, $users_ids_from_transfers, $users_ids_to_transfers);
        $users_ids = array_unique($users_ids);

        foreach ($users_ids as $user_id) {
            if (in_array($user_id, $except_users)) {
                continue;
            }

            $w = new WithdrawDisabledModel();
            $w->user_id = $user_id;
            $w->banner_id = ID_NRADIONOV;
            $w->reason = 'Banned due to disabling investments';
            $w->save();
        }
    }
}
