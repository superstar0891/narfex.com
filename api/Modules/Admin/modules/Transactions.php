<?php

namespace Admin\modules;

use Admin\helpers\ActionRequest;
use Admin\helpers\PageContainer;
use Admin\layout\Action;
use Admin\layout\Block;
use Admin\layout\Button;
use Admin\layout\Clipboard;
use Admin\layout\InfoList;
use Admin\layout\InfoListItem;
use Admin\layout\Input;
use Admin\layout\NumberFormat;
use Admin\layout\Time;
use Admin\layout\Toast;
use Db\Model\ModelSet;
use Db\Where;
use Models\ReservedCardModel;
use Models\TransactionModel;
use Models\UserModel;

class Transactions extends PageContainer {

    /* @var \Admin\helpers\DataManager */
    private $table;

    /** @var Action */
    private $info_action;

    public function registerActions() {
        $headers = ['ID', 'User', 'Platform', 'Txid', 'Status', 'Category', 'Confirmations', 'Amount', 'Date', 'Actions'];
        $this->table = $this
            ->createManagedTable(TransactionModel::class, $headers)
            ->setDataMapper(function (ModelSet $transactions) {
                $user_ids = array_filter($transactions->column('user_id'));
                $users = UserModel::select(Where::in('id', $user_ids));

                return $transactions->map(function (TransactionModel $transaction) use ($users) {
                    /* @var UserModel $user */
                    $user = $transaction->user_id > 0 ? $users->getItem($transaction->user_id) : null;
                    return [
                        $transaction->id,
                        $user ? $user->login . " ($user->id)" : 'None',
                        $transaction->platform,
                        Clipboard::withParams($transaction->txid ?? '', 32),
                        $transaction->status,
                        $transaction->category,
                        $transaction->confirmations,
                        NumberFormat::withParams($transaction->amount, $transaction->currency),
                        $transaction->created_at_timestamp ? Time::withParams($transaction->created_at_timestamp) : '',
                        Button::withParams('Info')
                            ->onClick($this->info_action->use(['transaction_id' => $transaction->id]))
                    ];
                });
            })
            ->setSearchForm(function () {
                return [
                    Input::withParams('user_id', 'User id'),
                    Input::withParams('txid', 'Txid'),
                ];
            })
            ->setFiltering(function (array $filters, Where $where) {
                if (isset($filters['user_id'])) {
                    $user_id = positive($filters['user_id']);
                    if ($user_id > 0) {
                        $where->set('user_id', Where::OperatorEq, $user_id);
                    }
                }
                if (isset($filters['txid'])) {
                    $where->set('txid', Where::OperatorLike, '%' . $filters['txid'] . '%');
                }
                return $where;
            });

        $this->info_action = $this->createAction(function (ActionRequest $request) {
            $transaction_id = $request->getParam('transaction_id');
            try {
                /** @var TransactionModel $transaction */
                $transaction = TransactionModel::get($transaction_id);
            } catch (\Exception $e) {
                return [
                    $this->showToast($e->getMessage(), Toast::TYPE_ERROR)
                ];
            }

            $from_wallet = $transaction->category === TransactionModel::RECEIVE_CATEGORY ? $transaction->wallet_to : $transaction->user_wallet;
            $to_wallet = $transaction->category === TransactionModel::RECEIVE_CATEGORY ? $transaction->user_wallet : $transaction->wallet_to;

            $list_items = [];
            $list_items[] = InfoListItem::withParams('Txid', $transaction->txid);
            $list_items[] = InfoListItem::withParams('Amount', NumberFormat::withParams($transaction->amount, $transaction->currency));
            $list_items[] = InfoListItem::withParams('Category', $transaction->category);
            $list_items[] = InfoListItem::withParams('Status', $transaction->status);
            $list_items[] = InfoListItem::withParams('Confirmations', $transaction->confirmations);
            $list_items[] = InfoListItem::withParams('From wallet', $from_wallet);
            $list_items[] = InfoListItem::withParams('To wallet', $to_wallet);
            $list_items[] = InfoListItem::withParams('Wallet ID', $transaction->wallet_id);
            return $this->showModal('More info', InfoList::withItems(...$list_items));
        });
    }

    public function build() {
        $this->layout->push(Block::withParams('Transactions', $this->table->build()));
    }
}
