<?php

namespace Core\Command;


use Db\Where;
use Models\DepositModel;
use Models\ProfitModel;
use Models\WalletModel;

class Index implements CommandInterface {
    private $name;

    function __construct(string $name) {
        $this->name = $name;
    }

    public function exec() {
        switch ($this->name) {
            case 'profits':
                $this->profits();
                break;
            case 'profits_target':
                $this->profitsTarget();
                break;
            default:
                echo $this->name . " - Unknown indexation name \n";
        }
    }

    public function profitsTarget() {
        echo "starting index profit targets\n";
        $last_id = 0;
        while(true) {
            $profits = ProfitModel::queryBuilder()
                ->columns([])
                ->where(Where::and()
                    ->set('id', Where::OperatorGreater, $last_id)
                    ->set('type', Where::OperatorEq, 'referral_profit')
                )
                ->orderBy(['id' => 'ASC'])
                ->limit(10000)
                ->select();

            if (empty($profits)) {
                die("Done\n");
            }

            $profits = ProfitModel::rowsToSet($profits);
            $deposits = [];
            /* @var \Models\ProfitModel $profit */
            foreach ($profits as $profit) {
                if ($profit->deposit_id > 0) {
                    $deposits[$profit->deposit_id] = true;
                }
            }

            $deposits_map = [];
            if (count($deposits)) {
                $deposits = DepositModel::select(Where::in('id', array_keys($deposits)), false);
            } else {
                $deposits = [];
            }

            /* @var \Models\DepositModel $deposit */
            foreach ($deposits as $deposit) {
                $deposits_map[$deposit->id] = $deposit;
            }

            /* @var \Models\ProfitModel $profit */
            foreach ($profits as $profit) {
                if (isset($deposits_map[$profit->deposit_id])) {
                    $user_id = $deposits_map[$profit->deposit_id]->user_id;
                } else {
                    continue;
                }

                $profit->target_id = $user_id;
                $profit->save();

                $last_id = $profit->id;
            }
        }
    }

    private function profits() {
        echo "starting index profits\n";
        $last_id = 0;
        while(true) {
            $profits = ProfitModel::queryBuilder()
                ->columns([])
                ->where(Where::and()
                    ->set('id', Where::OperatorGreater, $last_id)
                    ->set('currency', Where::OperatorIs, NULL)
                )
                ->orderBy(['id' => 'ASC'])
                ->limit(10000)
                ->select();

            if (empty($profits)) {
                die("Done\n");
            }

            $profits = ProfitModel::rowsToSet($profits);


            $wallets = [];
            $deposits = [];
            /* @var \Models\ProfitModel $profit */
            foreach ($profits as $profit) {
                if ($profit->wallet_id > 0) {
                    $wallets[$profit->wallet_id] = true;
                } else if ($profit->deposit_id) {
                    $deposits[$profit->deposit_id] = true;
                }
            }

            $wallets_map = [];
            $deposits_map = [];

            if (count($wallets)) {
                $wallets = WalletModel::select(Where::in('id', array_keys($wallets)), false);
            } else {
                $wallets = [];
            }

            /* @var \Models\WalletModel $wallet */
            foreach ($wallets as $wallet) {
                $wallets_map[$wallet->id] = $wallet;
            }

            if (count($deposits)) {
                $deposits = DepositModel::select(Where::in('id', array_keys($deposits)), false);
            } else {
                $deposits = [];
            }

            /* @var \Models\DepositModel $deposit */
            foreach ($deposits as $deposit) {
                $deposits_map[$deposit->id] = $deposit;
            }

            /* @var \Models\ProfitModel $profit */
            foreach ($profits as $profit) {
                if (isset($wallets_map[$profit->wallet_id])) {
                    $currency = $wallets_map[$profit->wallet_id]->currency;
                } else if (isset($deposits_map[$profit->deposit_id])) {
                    $currency = $deposits_map[$profit->deposit_id]->currency;
                } else {
                    continue;
                }

                $profit->currency = $currency;
                $profit->save();

                $last_id = $profit->id;
            }
        }
    }
}
