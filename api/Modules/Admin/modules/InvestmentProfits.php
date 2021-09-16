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
use Models\ProfitModel;
use Models\UserModel;

class InvestmentProfits extends PageContainer {
    private $table;

    public function registerActions() {
        $this->createProfitsTable();
    }

    public function build() {
        $this->layout->push(Block::withParams('Profits', $this->table->build()));
    }

    public function createProfitsTable() {
        $headers = [
            'ID',
            'User (ID)',
            'Amount',
            'Type',
            'Date'
        ];

        $this->table = $this
            ->createManagedTable(ProfitModel::class, $headers)
            ->setDataMapper(function(ModelSet $profits){
                $users_ids = $profits->column('user_id');
                $users = UserModel::select(Where::in('id', $users_ids));

                return $profits->map(function(ProfitModel $profit) use ($users){
                    /** @var UserModel $user */
                    $user = $users->getItem($profit->user_id);
                    return [
                        $profit->id,
                        $user ? $user->fullName() . ' (' . $user->id . ')' : '',
                        NumberFormat::withParams($profit->amount, $profit->currency),
                        $profit->type,
                        Time::withParams((int) strtotime($profit->created_at))
                    ];
                });
            })
            ->setSearchForm(function () {
                $available_types = [
                    'agent_profit' => 'Agent profit',
                    'invest_profit' => 'Invest profit',
                    'pool_profit' => 'Pool profit',
                    'referral_profit' => 'Referral profit',
                    'reinvest_profit' => 'Reinvest profit',
                    'return_deposit' => 'Return deposit',
                    'token_profit' => 'Token profit'
                ];
                return [
                    Input::withParams('user', 'Enter user login/name/email'),
                    Select::withParams('types', 'Select types', $available_types)->setMultiple(true),
                ];
            })
            ->setFiltering(function(array $filters, Where $where){
                if (isset($filters['types'])) {
                    $types = $filters['types'];
                    if (!empty($types)) {
                        $where->set(Where::in('type', $types));
                    }
                }

                $where = DataManager::applyUserFilters($filters, $where);
                return $where;
            });
    }
}
