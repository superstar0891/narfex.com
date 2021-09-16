<?php


namespace Admin\modules;


use Admin\helpers\DataManager;
use Admin\helpers\PageContainer;
use Admin\layout\Block;
use Admin\layout\Input;
use Admin\layout\NumberFormat;
use Admin\layout\Time;
use Db\Model\ModelSet;
use Db\Where;
use Models\RefillModel;
use Models\UserModel;

class Refills extends PageContainer {

    /** @var DataManager $table */
    private $table;

    public function registerActions() {
        $this->table = $this->createTable();
    }

    public function build() {
        $this->layout->push(Block::withParams('Refills', $this->table->build()));
    }

    private function createTable() {
        $headers = [
            'id',
            'User',
            'Currency',
            'Provider',
            'Bank',
            'Amount',
            'Fee',
            'Created at'
        ];
        return $this->createManagedTable(RefillModel::class, $headers)
            ->setDataMapper(function(ModelSet $items) {
                $users = UserModel::select(Where::in('id', $items->column('user_id')));
                return $items->map(function(RefillModel $refill) use ($users) {
                    /** @var UserModel $user */
                    $user = $users->getItem($refill->user_id);
                    $user_id = $user->id;
                    return [
                        $refill->id,
                        $user->login . " ({$user_id})",
                        $refill->currency,
                        $refill->provider ?? '',
                        $refill->bank_code ?? '',
                        NumberFormat::withParams($refill->amount, $refill->currency),
                        $refill->fee ? NumberFormat::withParams($refill->fee, $refill->currency) : 0,
                        Time::withParams((int) $refill->created_at_timestamp)
                    ];
                });
            })
            ->setOrderBy(['created_at_timestamp' => 'DESC'])
            ->setSearchForm(function(){
                return [
                    Input::withParams('user', 'Enter user'),
                    Input::withParams('currency', 'Enter currency'),
                ];
            })
            ->setFiltering(function (array $filters, Where $where)  {
                $where = Where::and();
                $where = DataManager::applyUserFilters($filters, $where);

                if (isset($filters['currency'])) {
                    $where->set(Where::equal('currency', $filters['currency']));
                }

                return $where;
            });
    }
}
