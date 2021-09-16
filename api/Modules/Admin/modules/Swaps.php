<?php


namespace Admin\modules;


use Admin\helpers\DataManager;
use Admin\helpers\PageContainer;
use Admin\layout\Block;
use Admin\layout\Input;
use Admin\layout\NumberFormat;
use Admin\layout\Select;
use Admin\layout\Time;
use Db\Model\ModelSet;
use Db\Where;
use Models\SwapModel;
use Models\UserBalanceHistoryModel;
use Models\UserModel;

class Swaps extends PageContainer {
    /** @var DataManager */
    private $table;

    public function registerActions() {
        $this->createSwapsTable();
    }

    public function build() {
        $this->layout->push(Block::withParams('Swaps', $this->table->build()));
    }

    public function createSwapsTable() {
        $headers = [
            'ID',
            'User (ID)',
            'Gave',
            'Got',
            'Rate',
            'Status',
            'Date'
        ];

        $this->table = $this
            ->createManagedTable(SwapModel::class, $headers)
            ->setDataMapper(function(ModelSet $swaps){
                $users_ids = $swaps->column('user_id');
                $users = UserModel::select(Where::in('id', $users_ids));

                return $swaps->map(function(SwapModel $swap) use ($users){
                    /** @var UserModel $user */
                    $user = $users->getItem($swap->user_id);
                    return [
                        $swap->id,
                        $user ? $user->fullName() . ' (' . $user->id . ')' : '',
                        NumberFormat::withParams($swap->from_amount, $swap->from_currency),
                        NumberFormat::withParams($swap->to_amount, $swap->to_currency),
                        NumberFormat::withParams($swap->rate, $swap->from_currency),
                        UserBalanceHistoryModel::STATUSES_MAP[$swap->status],
                        Time::withParams($swap->created_at_timestamp)
                    ];
                });
            })
            ->setSearchForm(function () {
                $currencies = [
                    CURRENCY_BTC => strtoupper(CURRENCY_BTC),
                    CURRENCY_USD => strtoupper(CURRENCY_USD),
                    CURRENCY_ETH => strtoupper(CURRENCY_ETH),
                    CURRENCY_RUB => strtoupper(CURRENCY_RUB),
                    CURRENCY_IDR => strtoupper(CURRENCY_IDR),
                    CURRENCY_LTC => strtoupper(CURRENCY_LTC),
                    CURRENCY_EUR => strtoupper(CURRENCY_EUR)
                ];
                return [
                    Input::withParams('swap_id', 'Enter swap id'),
                    Input::withParams('user', 'Enter user login/name/email'),
                    Select::withParams('from_currencies', 'Enter from currency', $currencies)->setMultiple(true),
                    Select::withParams('to_currencies', 'Enter to currency', $currencies)->setMultiple(true),
                ];
            })
            ->setFiltering(function(array $filters, Where $where){
                if (isset($filters['swap_id'])) {
                    $where->set(Where::equal('id', $filters['swap_id']));
                }

                if (isset($filters['from_currencies'])) {
                    $currencies = $filters['from_currencies'];
                    if (!empty($currencies)) {
                        $where->set(Where::in('from_currency', $currencies));
                    }
                }

                if (isset($filters['to_currencies'])) {
                    $currencies = $filters['to_currencies'];
                    if (!empty($currencies)) {
                        $where->set(Where::in('to_currency', $currencies));
                    }
                }

                $where = DataManager::applyUserFilters($filters, $where);

                return $where;
            });
    }
}
