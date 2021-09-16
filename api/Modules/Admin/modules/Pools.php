<?php

namespace Admin\modules;

use Admin\helpers\ActionRequest;
use Admin\helpers\PageContainer;
use Admin\layout\ActionSheet;
use Admin\layout\ActionSheetItem;
use Admin\layout\Block;
use Admin\layout\Input;
use Admin\layout\NumberFormat;
use Admin\layout\Tab;
use Admin\layout\TableColumn;
use Admin\layout\Toast;
use Db\Model\ModelSet;
use Db\Transaction;
use Db\Where;
use Exception;
use Models\DepositModel;
use Models\NotificationModel;
use Models\PoolModel;
use Models\ProfitModel;
use Models\UserModel;
use Models\WalletModel;
use Modules\InvestmentModule;
use Modules\NotificationsModule;

class Pools extends PageContainer {
    /* @var \Admin\helpers\TabsManager */
    private $tabs;

    /* @var \Admin\helpers\DataManager */
    private $table;

    /* @var \Admin\helpers\DataManager */
    private $profits_table;

    /* @var \Admin\helpers\FormManager */
    private $approve_form;

    public function registerActions() {

        $approve = $this->createAction(function (ActionRequest $request) {
            return $this->showModal('Approve pool request', $this->approve_form->build());
        });

        $decline = $this->createAction(function (ActionRequest $request) {
            /* @var \Models\PoolModel $item */
            $item = PoolModel::get($request->getParam('id'));

            if ($item->status != PoolModel::STATUS_IN_REVIEW) {
                return $this->showToast(lang('api_error'), Toast::TYPE_ERROR);
            }

            Transaction::wrap(function () use ($item) {
                $item->status = PoolModel::STATUS_REJECTED;
                $item->save();
                $item->delete();

                NotificationsModule::send($item->user_id, 'pool_declined');
            });

            return [
                $this->showToast('Pool request rejected'),
                $this->table->getReloadAction($request->getParams(), $request->getValues()),
            ];
        });

        $this->approve_form = $this->createFormManager()
            ->setItems(
                function ($params) {
                    return [
                        Input::withParams('amount', 'Amount')
                    ];
                })
            ->onSubmit(function (ActionRequest $request) {
                $values = $request->getValues();
                $params = $request->getParams();

                $amount = (double) $values['amount'];

                /* @var \Models\PoolModel $item */
                $item = PoolModel::get($params['id']);

                if ($item->status != PoolModel::STATUS_IN_REVIEW || $amount <= 0) {
                    return $this->showToast(lang('api_error'), Toast::TYPE_ERROR);
                }

                if ($amount > $item->amount) {
                    return $this->showToast('Maximum amount ' . $item->amount, Toast::TYPE_ERROR);
                }

                /* @var WalletModel $wallet */
                $wallet = WalletModel::get($item->wallet_id);
                if ($wallet->amount < $amount) {
                    return $this->showToast('На кошельке пользователя ' . $wallet->amount . ' ' . strtoupper($wallet->currency), Toast::TYPE_ERROR);
                }

                $item->amount_in_pool = $amount;
                $item->status = PoolModel::STATUS_ACCEPTED;

                Transaction::wrap(function () use ($item, $amount, $wallet) {
                    if (!$wallet->checkAmount($amount)) {
                        throw new Exception();
                    }

                    if (!$wallet->subAmount($amount)) {
                        throw new Exception();
                    }

                    $deposit = InvestmentModule::createPoolDeposit($item->user_id, $amount, $item->currency);

                    $item->deposit_id = $deposit->id;
                    $item->save();

                    NotificationsModule::send($item->user_id, NotificationModel::TYPE_POOL_APPROVED, [
                        'amount' => $amount,
                    ]);
                });

                return [
                    $this->closeModal(),
                    $this->table->getReloadAction($params, $values),
                    $this->showToast('Pool request approved'),
                ];
            });

        $headers = ['ID', 'Login', TableColumn::withParams('Used amount', 'Amount'), 'Status', 'Date', 'Actions'];
        $this->table = $this
            ->createManagedTable(PoolModel::class, $headers)
            ->setDataMapper(function (ModelSet $items) use ($approve, $decline) {
                $users = UserModel::select(Where::in('id', $items->column('user_id')));
                return $items->map(function (PoolModel $item) use ($users, $approve, $decline) {
                    /* @var UserModel $user */
                    $user = $users->getItem($item->user_id);

                    $actions = [];
                    if ($item->status == PoolModel::STATUS_ACCEPTED) {
                        $status = 'Accepted';
                    } else if ($item->status == PoolModel::STATUS_REJECTED) {
                        $status = 'Rejected';
                    } else {
                        $status = 'In Review';

                        $actions[] = ActionSheetItem::withParams('Approve')
                            ->onClick($approve->use(['pool_id' => $item->id]));

                        $actions[] = ActionSheetItem::withParams('Decline', ActionSheetItem::TYPE_DESTRUCTIVE)
                            ->onClick($decline->use(['pool_id' => $item->id]));
                    }

                    return [
                        $item->id,
                        $user->login,
                        TableColumn::withParams(
                            NumberFormat::withParams($item->amount_in_pool, $item->currency),
                            NumberFormat::withParams($item->amount, $item->currency, ['hidden_currency' => true])
                        ),
                        $status,
                        date('d/m/Y', $item->date_start),
                        count($actions) ? ActionSheet::withItems(...$actions) : '...',
                    ];
                });
            });

        $this->profits_table = $this
            ->createManagedTable(
                ProfitModel::class,
                ['ID', 'User', 'Deposit', 'Amount', 'Date'],
                Where::equal('type', 'pool_profit')
            )
            ->setDataMapper(function (ModelSet $profits) use ($approve, $decline) {
                $users = UserModel::select(Where::in('id', $profits->column('user_id')));

                $deposits_ids = array_filter($profits->column('deposit_id'));
                $deposits = DepositModel::select(Where::in('id', $deposits_ids));

                return $profits->map(function (ProfitModel $profit) use ($users, $deposits, $approve, $decline) {
                    /* @var UserModel $user */
                    $user = $users->getItem($profit->user_id);

                    /* @var DepositModel $deposit */
                    $deposit = $deposits->getItem($profit->deposit_id);
                    if ($deposit) {
                        $deposit_info = '#' .$deposit->id . ' (' . $deposit->amount . ' ' . strtoupper($deposit->currency) . ')';
                    } else {
                        $deposit_info = 'Unknown';
                    }

                    return [
                        $profit->id,
                        $user->login . ' (' . $user->fullName() . ')',
                        $deposit_info,
                        NumberFormat::withParams($profit->amount, $profit->currency),
                        date('d/m/Y', $profit->created_at_timestamp),
                    ];
                });
            });

        $this->tabs = $this->createTabsManager()
            ->setTabs(
                Tab::withParams('Requests')->setRenderer(function () {
                    $where = $this->table->getWhere()->set(Where::equal('status', PoolModel::STATUS_IN_REVIEW));
                    return $this->table->setWhere($where)->build();
                }),
                Tab::withParams('Confirmed')->setRenderer(function () {
                    $where = $this->table->getWhere()->set(Where::equal('status', PoolModel::STATUS_ACCEPTED));
                    return $this->table->setWhere($where)->build();
                }),
                Tab::withParams('Profits')->setRenderer(function () {
                    return $this->profits_table->build();
                })
            );
    }

    public function build() {
        $this->layout->push(Block::withParams('Pool', $this->tabs->build()));
    }
}
