<?php

namespace Admin\modules;

use Admin\helpers\ActionRequest;
use Admin\helpers\DataManager;
use Admin\helpers\FormManager;
use Admin\helpers\PageContainer;
use Admin\helpers\TabsManager;
use Admin\layout\Action;
use Admin\layout\Block;
use Admin\layout\Button;
use Admin\layout\Clipboard;
use Admin\layout\Input;
use Admin\layout\NumberFormat;
use Admin\layout\Select;
use Admin\layout\Tab;
use Admin\layout\TableRow;
use Admin\layout\Text;
use Admin\layout\Time;
use Db\Model\ModelSet;
use Db\Where;
use Models\HedgingTransactionModel;
use Models\QueueErrorModel;
use Models\QueueModel;
use Modules\BitcoinovnetModule;
use Modules\FiatWalletModule;

class BitcoinovnetStack extends PageContainer {
    /** @var Action */
    private $add_short;
    /** @var FormManager */
    private $add_short_form;
    /** @var DataManager */
    private $transactions;
    /** @var DataManager */
    private $queues;
    /** @var DataManager */
    private $error_queues;
    /** @var TabsManager */
    private $tabs;

    public function registerActions() {
        $this->tabs = $this->createTabsManager()->setTabs(
            Tab::withParams('Transactions')->setRenderer(function () {
                return $this->transactions->build();
            }),
            Tab::withParams('Queues')->setRenderer(function () {
                return $this->queues->build();
            }),
            Tab::withParams('Failed queues')->setRenderer(function () {
                return $this->error_queues->build();
            })
        );

        $this->transactions = $this->createManagedTable(
            HedgingTransactionModel::class,
            ['ID', 'Exchange', 'Type', 'Account', 'Rate', 'Amount', 'Api comment', 'Date']
        )
            ->setDataMapper(function (ModelSet $transactions) {
                return $transactions->map(function (HedgingTransactionModel $transaction) {
                    return [
                        $transaction->id,
                        $transaction->exchange,
                        $transaction->type,
                        $transaction->account,
                        NumberFormat::withParams($transaction->rate, CURRENCY_RUB),
                        NumberFormat::withParams($transaction->amount, $transaction->currency),
                        $transaction->text ? Clipboard::withParams($transaction->text, 50) : '',
                        Time::withParams($transaction->created_at_timestamp),
                    ];
                });
            })
            ->setSearchForm(function () {
                return [
                    Select::withParams('type', 'Type', [
                        HedgingTransactionModel::TYPE_SELL => HedgingTransactionModel::TYPE_SELL,
                        HedgingTransactionModel::TYPE_BUY => HedgingTransactionModel::TYPE_BUY,
                    ])->setMultiple(true),
                    Select::withParams('account', 'Account', [
                        HedgingTransactionModel::ACCOUNT_LONG => HedgingTransactionModel::ACCOUNT_LONG,
                        HedgingTransactionModel::ACCOUNT_SHORT => HedgingTransactionModel::ACCOUNT_SHORT,
                    ])->setMultiple(true),
                    Input::withParams('date_from', 'Date from d/m/Y'),
                    Input::withParams('date_to', 'Date to d/m/Y'),
                ];
            })
            ->setFiltering(function (array $filters, Where $where) {
                if (isset($filters['account']) && !empty($filters['account'])) {
                    $where->set(Where::in('account', $filters['account']));
                }
                if (isset($filters['type']) && !empty($filters['type'])) {
                    $where->set(Where::in('type', $filters['type']));
                }
                $where = DataManager::applyDateFilters($filters, $where);
                return $where;
            });

        $this->queues = $this->createManagedTable(
            QueueModel::class, ['ID', 'Class', 'Tries', 'Current try', 'Done', 'Failed', 'Working', 'Serialize', 'Date']
        )
            ->setDataMapper(function (ModelSet $queues) {
                return $queues->map(function (QueueModel $queue) {
                    $columns = [
                        $queue->id,
                        str_replace('Core\Queue\\', '', $queue->class),
                        is_null($queue->tries) ? 'unlimited' : $queue->tries,
                        $queue->current_try,
                        $queue->done ? 'Yes' : 'No',
                        $queue->failed ? 'Yes' : 'No',
                        $queue->is_working ? 'Yes' : 'No',
                        Clipboard::withParams($queue->serialized_queue, 40),
                        Time::withParams($queue->created_at_timestamp),
                    ];

                    $row = TableRow::withParams(...$columns);

                    if (!$queue->done) {
                        $row->accent();
                    }

                    if ($queue->failed) {
                        $row->danger();
                    }

                    return $row;
                });
            })
            ->setSearchForm(function () {
                return [
                    Input::withParams('date_from', 'Date from d/m/Y'),
                    Input::withParams('date_to', 'Date to d/m/Y'),
                ];
            })
            ->setFiltering(function (array $filters, Where $where) {
                $where = DataManager::applyDateFilters($filters, $where);
                return $where;
            });

        $this->error_queues = $this->createManagedTable(
            QueueErrorModel::class, ['ID', 'Class', 'Queue ID', 'Error message', 'Error Trace', 'Date']
        )
            ->setDataMapper(function (ModelSet $queues) {
                return $queues->map(function (QueueErrorModel $queue) {
                    $columns = [
                        $queue->id,
                        $queue->class,
                        $queue->queue_id,
                        Clipboard::withParams($queue->error_message, 40),
                        Clipboard::withParams($queue->error_trace, 40),
                        Time::withParams($queue->created_at_timestamp),
                    ];
                    return TableRow::withParams(...$columns);
                });
            })
            ->setSearchForm(function () {
                return [
                    Input::withParams('date_from', 'Date from d/m/Y'),
                    Input::withParams('date_to', 'Date to d/m/Y'),
                ];
            })
            ->setFiltering(function (array $filters, Where $where) {
                $where = DataManager::applyDateFilters($filters, $where);
                return $where;
            });

        $this->add_short = $this->createAction(function() {
            return $this->showModal(
                'Add short',
                Text::withParams('Amount in btc'),
                $this->add_short_form->build()
            );
        });

        $this->add_short_form = $this->createFormManager()
            ->setItems(function () {
                return [
                    Input::withParams('amount', 'Amount', '', '', 'Amount')
                ];
            })
            ->onSubmit(function (ActionRequest $request) {
                $amount = round($request->getValue( 'amount', [
                    'required', 'double', 'positive'
                ]), 8);
                BitcoinovnetModule::addShort($amount, $this->getAdmin());

                return [
                    $this->closeModal(),
                    $this->transactions->getReloadAction([], []),
                    $this->queues->getReloadAction([], []),
                    $this->showToast('Short added to queue')
                ];
            });
    }

    public function build() {
        $button = Button::withParams('Add short')
            ->onClick($this->add_short);
        $this->layout->push(Block::withParams('Actions', $button));
        $this->layout->push(Block::withParams('Main', $this->tabs->build()));
    }
}
