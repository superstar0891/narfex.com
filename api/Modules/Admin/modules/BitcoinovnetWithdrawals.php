<?php

namespace Admin\modules;

use Admin\helpers\ActionRequest;
use Admin\helpers\DataManager;
use Admin\helpers\FormManager;
use Admin\helpers\PageContainer;
use Admin\layout\Action;
use Admin\layout\ActionSheet;
use Admin\layout\ActionSheetItem;
use Admin\layout\Block;
use Admin\layout\Input;
use Admin\layout\NumberFormat;
use Admin\layout\Select;
use Admin\layout\TableRow;
use Admin\layout\Time;
use Db\Model\ModelSet;
use Db\Transaction;
use Db\Where;
use Models\BalanceModel;
use Models\BitcoinovnetWithdrawal;
use Models\UserModel;
use Modules\BalanceModule;

class BitcoinovnetWithdrawals extends PageContainer {
    /** @var DataManager */
    private $table;
    /** @var Action */
    private $approve;
    /** @var Action */
    private $reject;
    /** @var FormManager */
    private $reject_form;

    public function registerActions() {
        $this->table = $this->createManagedTable(
            BitcoinovnetWithdrawal::class,
            ['ID', 'Status', 'User', 'Admin',  'Amount', 'Card number', 'Reject message', 'Date', 'Actions']
        )
            ->setDataMapper(function (ModelSet $items) {
                $users = UserModel::select(Where::and()
                    ->set(Where::equal('platform', PLATFORM_BITCOINOVNET))
                    ->set(Where::in('id', $items->column('user_id')))
                );
                $admins = UserModel::select(Where::and()
                    ->set(Where::in('id', $items->column('admin_id')))
                );
                return $items->map(function (BitcoinovnetWithdrawal $withdrawal) use ($users, $admins) {
                    $actions = '';

                    if ($withdrawal->isPending()) {
                        $actions = ActionSheet::withItems(
                            ActionSheetItem::withParams('Approve')->onClick($this->approve->use(['withdrawal_id' => $withdrawal->id])),
                            ActionSheetItem::withParams('Reject')->onClick($this->reject->use(['withdrawal_id' => $withdrawal->id]))
                        );
                    }
                    /** @var UserModel $user */
                    $user = $users->getItem($withdrawal->user_id);
                    /** @var UserModel|null $admin */
                    $admin = $withdrawal->admin_id ? $admins->getItem($withdrawal->admin_id) : null;

                    $row = TableRow::withParams(...[
                        $withdrawal->id,
                        $withdrawal->status,
                        $user ? "$user->email ($user->id)" : $withdrawal->user_id,
                        $admin ? "$admin->email ($admin->id)" : ($withdrawal->admin_id ?: 'null'),
                        NumberFormat::withParams($withdrawal->amount, $withdrawal->currency),
                        $withdrawal->card_number,
                        $withdrawal->reject_message ? mb_strimwidth($withdrawal->reject_message, 0, 50, '...') : '',
                        Time::withParams($withdrawal->created_at_timestamp),
                        $actions
                    ]);

                    if ($withdrawal->isRejected()) {
                        $row->danger();
                    }

                    if ($withdrawal->isPending()) {
                        $row->accent();
                    }

                    return $row;
                });
            })
            ->setSearchForm(function () {
                return [
                    Input::withParams('user', 'Enter user login/name/email'),
                    Select::withParams('status', 'Status', [
                        BitcoinovnetWithdrawal::STATUS_PENDING => BitcoinovnetWithdrawal::STATUS_PENDING,
                        BitcoinovnetWithdrawal::STATUS_CONFIRMED => BitcoinovnetWithdrawal::STATUS_CONFIRMED,
                        BitcoinovnetWithdrawal::STATUS_REJECT => BitcoinovnetWithdrawal::STATUS_REJECT,
                    ])->setMultiple(true),
                    Input::withParams('card_number', 'Card number'),
                    Input::withParams('date_from', 'Date from d/m/Y'),
                    Input::withParams('date_to', 'Date to d/m/Y'),
                ];
            })
            ->setFiltering(function (array $filters, Where $where) {
                if (isset($filters['status']) && !empty($filters['status'])) {
                    $where->set(Where::in('status', $filters['status']));
                }
                if (isset($filters['card_number']) && $filters['card_number']) {
                    $where->set('card_number', Where::OperatorLike, '%' .$filters['card_number'] . '%');
                }
                $where = DataManager::applyDateFilters($filters, $where);
                $where = DataManager::applyUserFilters($filters, $where, ['user_id'], PLATFORM_BITCOINOVNET);
                return $where;
            });

        $this->approve = $this->createAction(function (ActionRequest $request) {
            try {
                $withdrawal = BitcoinovnetWithdrawal::get($request->getParam('withdrawal_id'));
                $withdrawal->approve();
                $withdrawal->admin_id = $this->getAdmin()->id;
                $withdrawal->save();
            } catch (\Exception $e) {
                return $this->showErrorToast($e->getMessage());
            }
            return [
                $this->table->getReloadAction($request->getParams(), $request->getValues())
            ];
        })->needGa()->setConfirm(true, 'Approve withdrawal?');

        $this->reject = $this->createAction(function (ActionRequest $request) {
            return [
                $this->showModal('Reject withdrawal', $this->reject_form->setParams($request->getParams())->build()),
            ];
        });

        $this->reject_form = $this->createFormManager()
            ->setItems(function ($params) {
                return [
                    Input::withParams('reject_message', 'Reject message')
                ];
            })
            ->onSubmit(function (ActionRequest $request) {
                try {
                    Transaction::wrap(function () use ($request) {
                        $withdrawal = BitcoinovnetWithdrawal::get($request->getParam('withdrawal_id'));
                        $withdrawal->reject($request->getValue('reject_message', ['required']));
                        $withdrawal->admin_id = $this->getAdmin()->id;

                        $balance = BalanceModule::getBalanceOrCreate(
                            $withdrawal->user_id,
                            $withdrawal->currency,
                            BalanceModel::CATEGORY_BITCOINOVNET_AGENT
                        );
                        $res = $balance->incrAmount($withdrawal->amount);
                        if (!$res) {
                            return $this->showErrorToast('Failed to return the money');
                        }

                        $withdrawal->save();

                        return $withdrawal;
                    });
                } catch (\Exception $e) {
                    return $this->showErrorToast($e->getMessage());
                }

                return [
                    $this->table->getReloadAction([], [])
                ];
            }, true);
    }

    public function build() {
        $this->layout->push(Block::withParams('Main', $this->table->build()));
    }
}
