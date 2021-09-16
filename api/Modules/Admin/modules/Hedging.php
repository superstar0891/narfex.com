<?php

namespace Admin\modules;

use Admin\helpers\ActionRequest;
use Admin\helpers\DataManager;
use Admin\helpers\FormManager;
use Admin\helpers\PageContainer;
use Admin\helpers\TabsManager;
use Admin\layout\Action;
use Admin\layout\ActionSheet;
use Admin\layout\ActionSheetItem;
use Admin\layout\Block;
use Admin\layout\Button;
use Admin\layout\DropDown;
use Admin\layout\Group;
use Admin\layout\InfoList;
use Admin\layout\InfoListItem;
use Admin\layout\Input;
use Admin\layout\Layout;
use Admin\layout\NumberFormat;
use Admin\layout\Tab;
use Admin\layout\TableColumn;
use Admin\layout\Text;
use Admin\layout\Toast;
use Core\Services\Hedging\Hedging as HedgingModule;
use Db\Model\ModelSet;
use Db\Transaction;
use Db\Where;
use Models\ExternalExchangePositionModel;
use Models\UserPermissionModel;
use Models\WalletModel;

class Hedging extends PageContainer {

    public static $permission_list = [
        UserPermissionModel::HEDGING,
    ];

    /* @var TabsManager */
    private $tabs;

    /* @var DataManager */
    private $table;

    /* @var Action */
    private $close_position;

    /* @var Action */
    private $get_info;

    /* @var FormManager */
    private $close_position_form;

    /* @var FormManager */
    private $close_position_multi_form;

    /* @var Action */
    private $close_multi_modal;

    /* @var Action */
    private $change_status;

    /* @var FormManager */
    private $change_status_form;

    public function registerActions() {
        $this->registerClosePositionActions();
        $this->makeTable();

        $this->get_info = $this->createAction(function(ActionRequest $request) {
            $position_id = $request->getParam('position_id');
            $position = ExternalExchangePositionModel::get($position_id);

            return $this->showModal('Position info', $this->getPositionInfo($position));
        });

        $this->tabs = $this->createTabsManager()
            ->setTabs(
                Tab::withParams('Opened')->setRenderer(function () {
                    return [
                        $this->getSummary(),
                        $this->table
                            ->setFilters(['status' => ExternalExchangePositionModel::STATUS_PENDING])
                            ->build()
                    ];
                }),
                Tab::withParams('Closed')->setRenderer(function () {
                    return $this->table
                        ->setFilters(['status' => ExternalExchangePositionModel::STATUS_CLOSED])
                        ->setOrderBy(['id' => 'DESC'])
                        ->build();
                })
            );

        $this->change_status = $this->createAction(function (ActionRequest $request) {
            return $this->showModal(
                'Измнение статуса',
                $this->change_status_form->setParams($request->getParams())->build()
            );
        });

        $this->change_status_form = $this->createFormManager()
            ->setItems(function (array $params) {
                $position_id = $params['position_id'];
                $position = ExternalExchangePositionModel::get($position_id);

                $options = [
                    [ExternalExchangePositionModel::STATUS_PENDING, strtoupper(ExternalExchangePositionModel::STATUS_PENDING)],
                    [ExternalExchangePositionModel::STATUS_CLOSED, strtoupper(ExternalExchangePositionModel::STATUS_CLOSED)],
                ];

                return [
                  DropDown::withParams('status', 'Выберите статус', $options, $position->status)
                ];
            })
            ->onSubmit(function (ActionRequest $request) {
                $position_id = $request->getParam('position_id');
                $status = $request->getValue('status', ['required', 'oneOf' => [
                    ExternalExchangePositionModel::STATUS_PENDING,
                    ExternalExchangePositionModel::STATUS_CLOSED,
                ]]);

                $position = ExternalExchangePositionModel::get($position_id);
                $position->status = $status;
                $position->save();

                return [
                    $this->closeModal(),
                    $this->showToast('Статус изменен'),
                    $this->table
                        ->setFilters(['status' => $request->getParam('status')])
                        ->getReloadAction($request->getParams(), $request->getValues()),
                ];
            });
    }


    private function registerClosePositionActions() {
        $this->close_position = $this->createAction(function (ActionRequest $request) {
            $position_id = $request->getParam('position_id');
            $position = ExternalExchangePositionModel::get($position_id);

            return $this->showModal(
                'Close position',
                $this->getPositionInfo($position),
                $this->close_position_form
                    ->setParams($request->getParams())
                    ->build()
            );
        });

        $this->close_position_form = $this->createFormManager()
            ->setItems(
                function (array $params) {
                    return [
                        Input::withParams('rate', 'Курс')
                    ];
                })
            ->setSubmitButtonText('Закрыть позицию')
            ->onSubmit(function (ActionRequest $request) {
                $id = (int) $request->getParam('position_id');
                $position = ExternalExchangePositionModel::get($id);

                $params = $request->getValues([
                    'rate' => ['required', 'positive'],
                ]);

                /* @var string $currency
                 * @var float $rate
                 */
                extract($params);

                if ($position->status !== ExternalExchangePositionModel::STATUS_PENDING) {
                    return $this->showToast('Position already closed', Toast::TYPE_ERROR);
                }

                try {
                    $position->buy_currency = 'usd';
                    $position->buy_rate = $rate;
                    HedgingModule::closePosition($position);
                } catch (\Exception $e) {
                    return $this->showToast($e->getMessage(), Toast::TYPE_ERROR);
                }

                return [
                    $this->showToast('Position closed'),
                    $this->closeModal(),
                    $this->table
                        ->setFilters(['status' => ExternalExchangePositionModel::STATUS_PENDING])
                        ->getReloadAction($request->getParams(), $request->getValues()),
                ];
            });

        $this->close_multi_modal = $this->createAction(function (ActionRequest $request) {
            $currency = $request->getParam('currency');

            $total = $this->getOpenedAmount($currency);
            if ($total <= 0) {
                return $this->showToast('Нечего закрывать');
            }

            $bitmex = HedgingModule::getPosition($currency)['amount'] ?? 0;

            return $this->showModal(
                'Закрытие позиций',
                InfoList::withItems(
                    InfoListItem::withParams('В позиции', formatNum($total) . ' ' . strtoupper($currency)),
                    InfoListItem::withParams('Bitmex', $bitmex . ' ' . strtoupper($currency))
                ),
                $this->close_position_multi_form->setParams($request->getParams())->build()
            );
        });

        $this->close_position_multi_form = $this->createFormManager()
            ->setItems(
                function (array $params) {
                    return [
                        Input::withParams('rate', 'Курс'),
                        Input::withParams('amount', 'Количество')
                    ];
                })
            ->setSubmitButtonText('Закрыть позицию')
            ->onSubmit(function (ActionRequest $request) {
                $currency = $request->getParam('currency');
                $amount = $request->getValue('amount', ['required', 'positive']);
                $rate = $request->getValue('rate', ['required', 'positive']);

                $positions = ExternalExchangePositionModel::queryBuilder()
                    ->columns([])
                    ->where(Where::and()
                        ->set(Where::equal('status', ExternalExchangePositionModel::STATUS_PENDING))
                        ->set(Where::equal('currency', $currency))
                    )
                    ->orderBy(['id' => 'ASC'])
                    ->select();
                $positions = ExternalExchangePositionModel::rowsToSet($positions);

                try {
                    $closed_count = Transaction::wrap(function () use ($positions, $amount, $rate) {
                        $to_close = [];
                        /* @var ExternalExchangePositionModel $position */
                        foreach ($positions as $position) {
                            if ($position->amount > $amount) {
                                break;
                            }
                            $amount -= $position->amount;

                            $position->buy_currency = 'usd';
                            $position->buy_rate = $rate;
                            $to_close[] = $position;
                        }

                        if (count($to_close) > 0) {
                            HedgingModule::closePosition(...$to_close);
                        }

                        return count($to_close);
                    });
                } catch (\Exception $e) {
                    return $this->showToast($e->getMessage(), Toast::TYPE_ERROR);
                }

                if (!$closed_count) {
                    return $this->showToast('Ни одной позиции не закрыто', Toast::TYPE_ERROR);
                }

                return [
                    $this->closeModal(),
                    $this->table
                        ->setFilters(['status' => ExternalExchangePositionModel::STATUS_PENDING])
                        ->getReloadAction($request->getParams(), $request->getValues()),
                    $this->showToast('Закрыто позиций: ' . $closed_count)
                ];
            });
    }

    private function makeTable() {

        $headers = ['ID', 'User', 'Amount', 'Fiat', 'Status', 'Hedging', 'Exchange', 'Date', 'Actions'];
        $this->table = $this
            ->createManagedTable(ExternalExchangePositionModel::class, $headers)
            ->setOrderBy(['id' => 'ASC'])
            ->setFiltering(function (array $filters, Where $where) {
                return $where->set(Where::equal('status', $filters['status']));
            })
            ->setDataMapper(function (ModelSet $items) {
                return $items->map(function (ExternalExchangePositionModel $item) {
                    $actions = [
                        ActionSheetItem::withParams('Информация')
                            ->onClick($this->get_info->use(['position_id' => $item->id])),
                        ActionSheetItem::withParams('Изменить статус')
                            ->onClick($this->change_status->use([
                                'position_id' => $item->id,
                                'status' => $this->table->getFilters()['status']
                            ]))
                    ];
                    if ($item->status === ExternalExchangePositionModel::STATUS_PENDING) {
                        $actions[] = ActionSheetItem::withParams('Close position', ActionSheetItem::TYPE_DESTRUCTIVE)
                            ->onClick($this->close_position->use(['position_id' => $item->id]));
                    }

                    $hedging_profit = $item->getHedgingProfit();
                    $exchange_profit = $item->getExchangeProfit();

                    return [
                        $item->id,
                        $item->user_id,
                        NumberFormat::withParams($item->amount, $item->currency),
                        NumberFormat::withParams($item->fiat_amount, $item->fiat_currency),
                        $item->status,
                        TableColumn::withParams(
                            NumberFormat::withParams($hedging_profit['amount'], strtoupper($item->currency)),
                            $hedging_profit['percent'] . ' %'
                        ),
                        TableColumn::withParams(
                            NumberFormat::withParams($exchange_profit['amount'], strtoupper($item->currency)),
                            $exchange_profit['percent'] . ' %'
                        ),
                        date('d/m/Y H:i', $item->created_at_timestamp),
                        ActionSheet::withItems(...$actions)
                    ];
                });
            });
    }

    public function build() {
        $this->layout->push(Block::withParams('Positions', $this->tabs->build()));
    }

    private function getPositionInfo(ExternalExchangePositionModel $position): Layout {
        $first_column = [];
        $second_column = [];

        $first_column[] = InfoListItem::withParams('Количество',  NumberFormat::withParams($position->amount, $position->currency));
        $first_column[] = InfoListItem::withParams('Количество',  NumberFormat::withParams($position->fiat_amount, $position->fiat_currency));
        $first_column[] = InfoListItem::withParams('User ID', $position->user_id);
        $first_column[] = InfoListItem::withParams('Статус', strtoupper($position->status));
        $first_column[] = InfoListItem::withParams('Обмен', strtoupper($position->currency) . '/' . strtoupper($position->fiat_currency));

        $second_column[] = InfoListItem::withParams('Курс обмена (USD)', NumberFormat::withParams($position->exchange_rate, 'usd'));
        if ($position->fiat_currency !== 'usd') {
            $second_column[] = InfoListItem::withParams(
                'Курс обмена (' . strtoupper($position->fiat_currency) . ')',
                NumberFormat::withParams($position->fiat_rate, $position->fiat_currency)
            );
        }
        $second_column[] = InfoListItem::withParams('Курс покупки', NumberFormat::withParams($position->real_rate, 'usd'));

        $close_rate = $position->isClosed() ? NumberFormat::withParams($position->close_rate, 'usd') : '-';
        $second_column[] = InfoListItem::withParams('Курс продажи', $close_rate);

        $buy_price = $position->isClosed() ? NumberFormat::withParams($position->buy_rate, $position->buy_currency) : '-';
        $second_column[] = InfoListItem::withParams('Цена покупки', $buy_price);

        return Group::withItems(
            InfoList::withItems(...$first_column),
            InfoList::withItems(...$second_column)
        );
    }

    private function getSummary() {
        $positions = ExternalExchangePositionModel::queryBuilder()
            ->columns([
                'SUM(amount)' => 'total',
                'currency',
            ], true)
            ->groupBy(['currency'])
            ->where(Where::equal('status', ExternalExchangePositionModel::STATUS_PENDING))
            ->select();

        $first_column = [];
        $second_column = [];
        foreach ($positions as $position) {
            $first_column[] = InfoListItem::withParams(
                strtoupper($position['currency']),
                Group::withItems(
                    Text::withParams(formatNum($position['total'])),
                    Button::withParams('Закрыть', Button::TYPE_PRIMARY, Button::SIZE_SMALL)
                        ->onClick($this->close_multi_modal->use(['currency' => $position['currency']]))
                )
            );

            $bitmex_amount = HedgingModule::getPosition($position['currency'])['amount'] ?? 0;
            $amount_in_crypto = $bitmex_amount * WalletModel::getRate($position['currency'], 'usd');
            $second_column[] = InfoListItem::withParams(
                'Bitmex USD',
                $bitmex_amount . ' (' . $amount_in_crypto . ' ' . strtoupper($position['currency']) . ')'
            );
        }

        return Group::withItems(
            InfoList::withItems(...$first_column),
            InfoList::withItems(...$second_column)
        );
    }

    private function getOpenedAmount($currency) {
        $info = ExternalExchangePositionModel::queryBuilder()
            ->columns(['SUM(amount)' => 'total'], true)
            ->where(Where::and()
                ->set(Where::equal('status', ExternalExchangePositionModel::STATUS_PENDING))
                ->set(Where::equal('currency', $currency))
            )
            ->get();

        return $info ? $info['total'] : 0;
    }
}
