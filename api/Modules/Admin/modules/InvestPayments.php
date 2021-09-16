<?php

namespace Admin\modules;

use Admin\common\SearchFilters;
use Admin\helpers\PageContainer;
use Admin\layout\Block;
use Admin\layout\Input;
use Admin\layout\NumberFormat;
use Admin\layout\Select;
use Admin\layout\Time;
use Db\Model\ModelSet;
use Db\Where;
use Models\PaymentModel;
use Models\UserModel;
use Models\WalletModel;

class InvestPayments extends PageContainer {
    private $table;

    public function registerActions() {
        $this->createProfitsTable();
    }

    public function build() {
        $this->layout->push(Block::withParams('Profits', $this->table->build()));
    }

    public function createProfitsTable() {
        $headers = ['ID', 'User (ID)', 'Amount', 'Status', 'Date'];
        $this->table = $this
            ->createManagedTable(PaymentModel::class, $headers)
            ->setDataMapper(function(ModelSet $payments) {
                $users_ids = $payments->column('user_id');
                $wallet_ids = $payments->column('wallet_id');
                $users = UserModel::select(Where::in('id', $users_ids));
                $wallets = WalletModel::select(Where::in('id', $wallet_ids));

                return $payments->map(function(PaymentModel $payment) use ($users, $wallets) {
                    /** @var UserModel $user */
                    $user = $users->getItem($payment->user_id);
                    /** @var WalletModel $wallet */
                    $wallet = $wallets->getItem($payment->wallet_id);
                    return [
                        $payment->id,
                        $user ? $user->fullName() . ' (' . $user->id . ')' : '',
                        NumberFormat::withParams($payment->amount, $wallet->currency),
                        $payment->status,
                        Time::withParams((int) strtotime($payment->created_at))
                    ];
                });
            })
            ->setUserFilters()
            ->setSearchForm(function() {
                return [
                    Input::withParams('user', 'Enter user'),
                    Select::withParams('status', 'Select status', [
                        'all' => 'all',
                        'waiting' => 'waiting',
                        'accepted' => 'accepted',
                        'deleted' => 'deleted',
                    ]),
                ];
            })
            ->setFiltering(function(array $filters, Where $where) {
                $where = SearchFilters::user($filters, $where);
                if (isset($filters['status']) && $filters['status'] !== 'all') {
                    $where->set('status', Where::OperatorEq, $filters['status']);
                }
                return $where;
            });
    }
}
