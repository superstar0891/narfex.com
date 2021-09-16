<?php

namespace Admin\modules;

use Admin\helpers\ActionRequest;
use Admin\helpers\PageContainer;
use Admin\layout\ActionSheet;
use Admin\layout\ActionSheetItem;
use Admin\layout\Block;
use Admin\layout\DropDown;
use Admin\layout\Input;
use Admin\layout\Tab;
use Admin\layout\Text;
use Db\Model\ModelSet;
use Db\Where;
use Models\DepositModel;

class Test extends PageContainer {

    /* @var \Admin\helpers\DataManager */
    private $table;

    /* @var \Admin\helpers\FormManager */
    private $form;

    /* @var \Admin\helpers\TabsManager */
    private $tabs;

    public function registerActions() {

        $approve = $this->createAction(function (ActionRequest $request) {
            return $this->showToast('Approved: ' . $request->getParam('deposit_id'));
        });

        $decline = $this->createAction(function (ActionRequest $request) {
            return $this->showModal('Decline' . $request->getParam('deposit_id'), $this->form->build());
        });

        $this->form = $this->createFormManager()
            ->setItems(
                function ($params) {
                    return [
                        Input::withParams('name', 'Enter user name'),
                        Input::withParams('user_id', 'Enter user id'),
                        DropDown::withParams('currency', 'Select currency', [
                            ['btc', 'Bitcoin'],
                            ['ltc', 'Litecoin'],
                            ['eth', 'Ethereum'],
                        ])
                    ];
                })
            ->onSubmit(function (ActionRequest $request) {
                return [
                    $this->closeModal(),
                    $this->showToast(json_encode($request->getValues()))
                ];
            });

        $this->table = $this
            ->createManagedTable(DepositModel::class, ['Id', 'User', 'Amount', 'Currency', 'Profit',  'Actions'])
            ->setDataMapper(function (ModelSet $deposits) use ($approve, $decline) {
                return $deposits->map(function (DepositModel $deposit) use ($approve, $decline) {
                    return [
                        $deposit->id,
                        $deposit->user_id,
                        $deposit->amount,
                        $deposit->currency,
                        $deposit->dynamic_profit,
                        ActionSheet::withItems(
                            ActionSheetItem::withParams('Approve')
                                ->onClick($approve->use(['deposit_id' => $deposit->id])),
                            ActionSheetItem::withParams('Decline', ActionSheetItem::TYPE_DESTRUCTIVE)
                                ->onClick($decline->use(['deposit_id' => $deposit->id]))
                        ),
                    ];
                });
            })
            ->setSearchForm(function () {
                return [
                    Input::withParams('user_id', 'Enter user_id'),
                    DropDown::withParams('currency', 'Currency', [
                        ['all', 'All'],
                        ['btc', 'Bitcoin'],
                        ['eth', 'Ethereum'],
                        ['ltc', 'Litecoin'],
                    ])
                ];
            })
            ->setFiltering(function (array $filters, Where $where) {
                if (isset($filters['currency']) && $filters['currency'] !== 'all') {
                    $where->set('currency', Where::OperatorEq, $filters['currency']);
                }
                if (isset($filters['user_id'])) {
                    $user_id = positive($filters['user_id']);
                    if ($user_id > 0) {
                        $where->set('user_id', Where::OperatorEq, $user_id);
                    }
                }
                return $where;
            })
            ->setOrderBy(['id' => 'DESC']);

        $this->tabs = $this->createTabsManager()
            ->setTabs(
                Tab::withParams('First')->setRenderer(function () {
                    return $this->table->build();
                }),
                Tab::withParams('Second')->setRenderer(function () {
                    return Text::withParams('Second tab content');
                })
            );
    }

    public function build() {
        $this->layout->push(Block::withParams('Block with table', $this->tabs->build()));
    }
}
