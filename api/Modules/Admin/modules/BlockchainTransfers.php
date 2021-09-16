<?php

namespace Admin\modules;

use Admin\helpers\PageContainer;
use Admin\layout\Block;
use Admin\layout\Input;
use Admin\layout\NumberFormat;
use Admin\layout\Time;
use Db\Model\ModelSet;
use Db\Where;
use Models\TransferModel;
use Models\UserModel;

class BlockchainTransfers extends PageContainer {

    /* @var \Admin\helpers\DataManager */
    private $table;

    public function registerActions() {
        $headers = ['ID', 'From', 'To', 'Amount', 'Date'];
        $this->table = $this
            ->createManagedTable(TransferModel::class, $headers)
            ->setDataMapper(function (ModelSet $transfers) {
                $user_ids = array_unique(
                    array_merge(
                        $transfers->column('from_user_id'), 
                        $transfers->column('to_user_id')
                    )
                );
                $users = UserModel::select(Where::in('id', $user_ids));
                return $transfers->map(function (TransferModel $transfer) use ($users) {
                    /* @var UserModel $user_from */
                    $user_from = $users->getItem($transfer->from_user_id);
                    /* @var UserModel $user_to */
                    $user_to = $users->getItem($transfer->to_user_id);
                    return [
                        $transfer->id,
                        $user_from ? "$user_from->login ($user_from->id)" : 'None',
                        $user_to ? "$user_to->login ($user_to->id)" : 'None',
                        NumberFormat::withParams($transfer->amount, $transfer->currency),
                        $transfer->created_at_timestamp ?
                            Time::withParams($transfer->created_at_timestamp) :
                            (strtotime($transfer->created_at) !== false ? Time::withParams(strtotime($transfer->created_at)) : ''),
                    ];
                });
            })
            ->setSearchForm(function () {
                return [
                    Input::withParams('user', 'Enter user'),
                ];
            })
            ->setFiltering(function (array $filters, Where $where) {
                $user = $filters['user'] ?? null;

                if ($user) {
                    $user = trim($user);
                    $users = UserModel::select(
                        Where::and()
                            ->set(Where::equal('platform', PLATFORM_FINDIRI))
                            ->set(
                                Where::or()
                                    ->set(Where::equal('id', $user))
                                    ->set('login', Where::OperatorLike, "%$user%")
                                    ->set('email', Where::OperatorLike, "%$user%")
                                    ->set("CONCAT(first_name, ' ', last_name)", Where::OperatorLike, "%$user%")
                            )
                    );
                    $where->set(Where::or()
                        ->set('from_user_id', Where::OperatorIN, $users->column('id'))
                        ->set('to_user_id', Where::OperatorIN, $users->column('id')));
                }

                return $where;
            });
    }

    public function build() {
        $this->layout->push(Block::withParams('Transactions', $this->table->build()));
    }
}
