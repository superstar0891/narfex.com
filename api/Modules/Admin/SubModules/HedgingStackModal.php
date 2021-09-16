<?php

namespace Admin\SubModules;

use Admin\helpers\ActionRequest;
use Admin\helpers\DataManager;
use Admin\helpers\FormManager;
use Admin\layout\Action;
use Admin\layout\ActionSheet;
use Admin\layout\ActionSheetItem;
use Admin\layout\Button;
use Admin\layout\ClientAction;
use Admin\layout\Group;
use Admin\layout\InfoList;
use Admin\layout\InfoListItem;
use Admin\layout\NumberFormat;
use Admin\layout\Select;
use Admin\layout\TableColumn;
use Admin\layout\Text;
use Admin\layout\Time;
use Admin\layout\Title;
use Admin\layout\Wrapper;
use ccxt\BadRequest;
use Db\Model\ModelSet;
use Db\Where;
use Exception;
use Models\HedgingExAccount;
use Models\StackHistoryModel;
use Models\StackModel;
use Modules\HedgingExchangeModule;

trait HedgingStackModal {
    /* @var DataManager */
    private $sales_table;
    /* @var DataManager */
    private $withdrawals_table;
    /* @var Action */
    private $delete_history_action;
    /** @var Action */
    private $info_stack_action;
    /** @var Action */
    private $add_short_action;
    /** @var Action */
    private $add_long_action;
    /** @var FormManager */
    private $add_short_form;
    /** @var FormManager */
    private $add_long_form;

    public function hedgingStackModalRegisterActions() {
        $this->createSalesTable();
        $this->createWithdrawalsTable();
        $this->createInfoStackAction();

        $this->createDeleteHistoryAction();
        $this->createAddLongAction();
        $this->createAddShortAction();

        $this->createShortForm();
        $this->createLongForm();
    }

    private function createSalesTable() {
        $headers = ['ID', 'Amount', 'Sale rate', 'Fiat to USD', 'Long rate', 'Long fee', 'Long info', 'Date', 'Actions'];
        $this->sales_table = $this->createManagedTable(StackHistoryModel::class, $headers)
            ->setDataMapper(function (ModelSet $sales) {
                return $sales->map(function (StackHistoryModel $sale) {
                    $actions = [
                        ActionSheetItem::withParams('Edit')
                            ->onClick($this->add_sale_action->use(['id' => $sale->id, 'stack_id' => $sale->stack_id])),
                        ActionSheetItem::withParams('Delete')
                            ->onClick($this->delete_history_action->use(['id' => $sale->id]))
                    ];
                    if (is_null($sale->long_rate) || is_null($sale->long_fee)) {
                        $actions[] = ActionSheetItem::withParams('Open long')
                            ->onClick($this->add_long_action->use([
                                'stack_history_id' => $sale->id,
                            ]));
                    }
                    return [
                        $sale->id,
                        NumberFormat::withParams($sale->amount, $sale->currency),
                        NumberFormat::withParams($sale->sale_rate, $sale->sale_currency),
                        NumberFormat::withParams($sale->fiat_to_usd, $sale->sale_currency),
                        $sale->long_rate ? NumberFormat::withParams($sale->long_rate, 'usd') : 'NULL',
                        NumberFormat::withParams($sale->long_fee ?? 0, null, ['percent' => true, 'fraction_digits' => 4]),
                        is_null($sale->long_rate) || is_null($sale->long_fee)
                            ? 'Not opened'
                            : ($sale->account_id ? 'Opened, account id - ' .  $sale->account_id : 'Manually opened'),
                        Time::withParams($sale->created_at_timestamp),
                        ActionSheet::withItems(...$actions),
                    ];
                });
            })
            ->setFiltering(function (array $filters, Where $where) {
                $stack_id = $filters['stack_id'];
                return Where::and()
                    ->set('type', Where::OperatorEq, StackHistoryModel::TYPE_SALE)
                    ->set('stack_id', Where::OperatorEq, $stack_id);
            });
    }

    private function createWithdrawalsTable() {
        $headers = ['ID', 'Amount', 'Swap rate', 'Swap fee', 'Date', 'Actions'];
        $this->withdrawals_table = $this->createManagedTable(StackHistoryModel::class, $headers)
            ->setDataMapper(function (ModelSet $withdrawals) {
                return $withdrawals->map(function (StackHistoryModel $withdrawal) {
                    $swap_rates = json_decode($withdrawal->swap_rate, true);
                    $swap_fees = json_decode($withdrawal->swap_fee, true);

                    if (count($swap_rates) > 1) {
                        $on_click = $this->add_withdrawal_two_step_action
                            ->use(['id' => $withdrawal->id, 'stack_id' => $withdrawal->stack_id]);
                    } else {
                        $on_click = $this->add_withdrawal_action
                            ->use(['id' => $withdrawal->id, 'stack_id' => $withdrawal->stack_id]);
                    }

                    $rates_column = [];
                    $fees_column = [];
                    foreach ($swap_rates as $currency => $rate) {
                        $currency = str_replace('swap_rate_', '', $currency);
                        $rate = formatNum($rate, 2);
                        $rates_column[] = "{$currency}: {$rate}";
                    }

                    foreach ($swap_fees as $currency => $fee) {
                        $currency = str_replace('swap_fee_', '', $currency);
                        $fees_column[] = "{$currency}: {$fee}%";
                    }

                    return [
                        $withdrawal->id,
                        NumberFormat::withParams($withdrawal->amount, $withdrawal->currency),
                        TableColumn::withParams($rates_column[0], isset($rates_column[1]) ? $rates_column[1] : ''),
                        TableColumn::withParams($fees_column[0], isset($fees_column[1]) ? $fees_column[1] : ''),
                        Time::withParams($withdrawal->created_at_timestamp),
                        ActionSheet::withItems(
                            ActionSheetItem::withParams('Edit')
                                ->onClick($on_click),
                            ActionSheetItem::withParams('Delete')
                                ->onClick($this->delete_history_action->use(['id' => $withdrawal->id]))
                        ),
                    ];
                });
            })
            ->setFiltering(function (array $filters, Where $where) {
                $stack_id = $filters['stack_id'];
                return Where::and()
                    ->set('type', Where::OperatorEq, StackHistoryModel::TYPE_WITHDRAWAL)
                    ->set('stack_id', Where::OperatorEq, $stack_id);
            });
    }

    /**
     * @param StackModel $stack
     * @param array $params
     * @param array $values
     * @return ClientAction
     * @throws Exception
     */
    private function buildInfoStackModal(StackModel $stack, array $params, array $values): ClientAction {
        $total = StackHistoryModel::queryBuilder()
            ->columns([
                'SUM(amount)' => 'total',
            ], true)
            ->where(Where::and()
                ->set('stack_id', Where::OperatorEq, $stack->id)
                ->set('type', Where::OperatorEq, StackHistoryModel::TYPE_SALE)
            )
            ->select();

        $sales_total_amount = array_get_val(current($total), 'total', 0);

        $items = [];

        if (!is_null($stack->short_rate) && !is_null($stack->short_fee)) {
            if (is_null($stack->account_id)) {
                $items[] = Text::withParams('Short opened manually');
            } else {
                $items[] = Text::withParams('Short opened, account id - ' . (string) $stack->account_id);
            }
        } else {
            $items[] = Button::withParams('Open short')
                ->onClick($this->add_short_action->use(
                    [
                        'stack_id' => $stack->id,
                    ]
                ));
        }

        $actions = Group::withItems(...$items);

        $first_row = [];
        $first_row[] = Wrapper::withParams('Main info', InfoList::withItems(
            InfoListItem::withParams('ID', $stack->id),
            InfoListItem::withParams('Primary', NumberFormat::withParams(
                $stack->primary_amount, $stack->primary_currency
            )),
            InfoListItem::withParams('Buy rate', NumberFormat::withParams(
                $stack->buy_rate, $stack->secondary_currency
            )),
            InfoListItem::withParams('Buy fee', NumberFormat::withParams(
                $stack->buy_fee, null, ['percent' => true, 'fraction_digits' => 4]
            )),
            InfoListItem::withParams('Fiat to USD', NumberFormat::withParams(
                $stack->fiat_to_usd, $stack->secondary_currency
            )),
            InfoListItem::withParams('Short rate', NumberFormat::withParams(
                $stack->short_rate ?? 0, 'usd'
            )),
            InfoListItem::withParams('Short fee', NumberFormat::withParams(
                $stack->short_fee ?? 0, null, ['percent' => true, 'fraction_digits' => 4]
            )),
            InfoListItem::withParams('Date', Time::withParams($stack->created_at_timestamp)),
            InfoListItem::withParams(
                'Close date',
                $stack->close_at_timestamp ? Time::withParams($stack->close_at_timestamp) : 'Not closed'
            )
        ));
        $first_row[] = Wrapper::withParams('', InfoList::withItems(
            InfoListItem::withParams('Balance', $stack->primary_amount - $sales_total_amount),
            InfoListItem::withParams(
                'Close short rate',
                $stack->close_short_rate ?
                    NumberFormat::withParams(
                        $stack->close_short_rate, $stack->close_short_currency
                    ) : ''
            ),
            InfoListItem::withParams(
                'Close short fee',
                $stack->close_short_fee ?
                    NumberFormat::withParams(
                        $stack->close_short_fee, null, ['percent' => true, 'fraction_digits' => 4]
                    ) : ''
            ),
            InfoListItem::withParams(
                'Close long rate',
                $stack->close_long_rate ?
                    NumberFormat::withParams(
                        $stack->close_long_rate,
                        $stack->close_long_currency
                    ) : ''
            ),
            InfoListItem::withParams(
                'Close long fee',
                $stack->close_long_fee ?
                    NumberFormat::withParams(
                        $stack->close_long_fee, null, ['percent' => true, 'fraction_digits' => 4]
                    ) : ''
            )
        ));

        $second_row = [];

        $profits = $this->calcProfits($stack);
        $short_fee = array_get_val($profits, 'short_fee', 0);
        $long_fee = array_get_val($profits, 'long_fee', 0);

        $rate_profit = array_get_val($profits, 'rate_profit', 0);

        $short_profit = array_get_val($profits, 'short_profit', 0);
        $short_profit -= $short_fee;

        $long_profit = array_get_val($profits, 'long_profit', 0);
        $long_profit -= $long_fee;

        $profit = array_get_val($profits, 'profit', 0);

        $rate_profit = formatNum($rate_profit, 2, ',');
        $short_fee = formatNum($short_fee, 2, ',');
        $long_fee = formatNum($long_fee, 2, ',');
        $short_profit = formatNum($short_profit, 2, ',');
        $long_profit = formatNum($long_profit, 2, ',');
        $profit = formatNum($profit, 2, ',');

        $second_row[] = Wrapper::withParams('Profits', InfoList::withItems(
            InfoListItem::withParams('Rate profit', "{$rate_profit} USD"),
            InfoListItem::withParams('Short profit', "{$short_profit} (fee {$short_fee}) USD"),
            InfoListItem::withParams('Long profit', "{$long_profit} (fee {$long_fee}) USD"),
            InfoListItem::withParams('Profit', "{$profit} USD")
        ));

        return $this->showModal(
            $stack->name ?? "Stack " . $stack->id,
            $actions,
            Group::withItems(...$first_row),
            Group::withItems(...$second_row),
            Title::withParams('Sales'),
            $this->sales_table->setFilters($params)->build(),
            Title::withParams('Withdrawals'),
            $this->withdrawals_table->setFilters($params)->build()
        );
    }

    /**
     * @param StackModel $stack
     * @return array
     * @throws Exception
     */
    private function calcProfits(StackModel $stack): array {
        if (is_null($stack->close_long_currency)) {
            return [];
        }

        $rate_profit = 0;
        $long_profit = 0;
        $long_fee = 0;
        $total_sell_amount = 0;

        $history = StackHistoryModel::select(Where::equal('stack_id', $stack->id));
        $sales = $history->filter(function (StackHistoryModel $model) {
            return $model->type === StackHistoryModel::TYPE_SALE;
        });

        foreach ($sales as $sale) {
            /** @var StackHistoryModel $sale */
            $rate_profit += $this->calcRateProfit($sale, $stack);
            $long_profit += $this->calcLongProfit($sale, $stack);
            $long_fee += $this->calcLongFee($sale);
            $total_sell_amount += $sale->amount;
        }

        $long_fee += $this->calcCloseLongFee($stack, $total_sell_amount);
        $short_fee = $this->calcCloseShortFee($stack);
        $short_profit = $this->calcShortProfit($stack);

        $profit = $rate_profit + $short_profit + $long_profit - $long_fee - $short_fee;
        return compact(
            'rate_profit',
            'short_profit',
            'long_profit',
            'profit',
            'long_fee',
            'short_fee'
        );
    }

    private function calcRateProfit(StackHistoryModel $sale, StackModel $stack): float {
        if ($sale->type !== StackHistoryModel::TYPE_SALE || $sale->stack_id !== $stack->id) {
            return 0;
        }
        $rate_in_usd = $sale->sale_rate / $sale->fiat_to_usd;
        $buy_rate_in_usd = $stack->buy_rate / $stack->fiat_to_usd;
        return ($rate_in_usd - $buy_rate_in_usd) * $sale->amount;
    }

    private function calcLongProfit(StackHistoryModel $sale, StackModel $stack): float {
        if (
            $sale->type !== StackHistoryModel::TYPE_SALE
            ||
            $sale->stack_id !== $stack->id
            ||
            is_null($sale->long_rate)
        ) {
            return 0;
        }
        return ($stack->close_long_rate - $sale->long_rate) * $sale->amount;
    }

    private function calcShortProfit(StackModel $stack): float {
        if (
            (is_null($stack->short_rate) || is_null($stack->short_fee))
            &&
            !is_null($stack->close_short_rate)
        ) {
            return 0;
        }
        return ($stack->short_rate - $stack->close_short_rate) * $stack->primary_amount;
    }

    private function calcCloseShortFee(StackModel $stack): float {
        if (
            (is_null($stack->short_rate) || is_null($stack->short_fee))
            &&
            !is_null($stack->close_short_rate)
        ) {
            return 0;
        }
        $short_fee_amount = $stack->short_rate * $stack->short_fee / 100;
        $close_short_fee_amount = $stack->close_short_rate * $stack->close_short_fee / 100;
        return $stack->primary_amount * ($short_fee_amount + $close_short_fee_amount);
    }

    private function calcLongFee(StackHistoryModel $sale): float {
        if (is_null($sale->long_rate) || is_null($sale->long_fee) || $sale->type !== StackHistoryModel::TYPE_SALE) {
            return 0;
        }
        return $sale->amount * $sale->long_rate * $sale->long_fee / 100;
    }

    private function calcCloseLongFee(StackModel $stack, float $total_sell_amount): float {
        if (
            is_null($stack->close_long_rate) ||
            is_null($stack->close_long_fee) ||
            is_null($stack->close_long_currency) ||
            is_null($stack->close_short_rate) ||
            is_null($stack->close_short_fee) ||
            is_null($stack->close_short_currency)
        ) {
            return 0;
        }
        return $total_sell_amount * $stack->close_long_rate * $stack->close_long_fee / 100;
    }

    private function createInfoStackAction() {
        $this->info_stack_action = $this->createAction(function (ActionRequest $request) {
            $stack = StackModel::get($request->getParam('stack_id'));
            return $this->buildInfoStackModal($stack, $request->getParams(), $request->getValues());
        });
    }

    private function createDeleteHistoryAction() {
        $this->delete_history_action = $this->createAction(function (ActionRequest $request) {
            $history = StackHistoryModel::get($request->getParam('id'));
            $is_sale = $history->type === StackHistoryModel::TYPE_SALE;
            $stack = StackModel::get($history->stack_id);
            $history->delete(true);
            return [
                $this->showToast($is_sale ? 'Sale deleted' : 'Withdrawal deleted'),
                $this->buildInfoStackModal($stack, ['stack_id' => $stack->id], $request->getValues())
            ];
        })->setConfirm(true, 'Delete sale', true);
    }

    public function createAddShortAction() {
        $this->add_short_action = $this->createAction(function (ActionRequest $request) {
            $stack = StackModel::get($request->getParam('stack_id'));
            $short_amount = formatNum($stack->getShortAmount(), 2, ',') . ' USD';
            return $this->showModal(
                'Open short',
                InfoList::withItems(
                    InfoListItem::withParams('Short amount', $short_amount)
                ),
                $this->add_short_form->setParams($request->getParams())->build()
            );
        });
    }

    public function createAddLongAction() {
        $this->add_long_action = $this->createAction(function (ActionRequest $request) {
            $stack_history = StackHistoryModel::get($request->getParam('stack_history_id'));
            $short_amount = formatNum($stack_history->getLongAmount(), 2, ',') . ' USD';
            return $this->showModal(
                'Open long',
                InfoList::withItems(
                    InfoListItem::withParams('Long amount', $short_amount)
                ),
                $this->add_long_form->setParams($request->getParams())->build()
            );
        });
    }

    public function createShortForm() {
        $this->add_short_form = $this->addShortOrLongForm();
    }

    public function createLongForm() {
        $this->add_long_form = $this->addShortOrLongForm();
    }

    private function addShortOrLongForm() {
        return $this->createFormManager()
            ->setItems(function ($params) {
                $accounts = HedgingExAccount::select();
                $select_options = [];
                foreach ($accounts as $account) {
                    /** @var HedgingExAccount $account */
                    $description = mb_strimwidth($account->description, 0, 50, '...');
                    $select_options[$account->id] = "({$account->exchange}) {$description}";
                }
                return [
                    Select::withParams('exchange_account', 'Exchange account', $select_options),
                ];
            })
            ->onSubmit(function (ActionRequest $request) {
                $params = $request->getParams();
                $is_short = true;
                if (isset($params['stack_id'])) {
                    $model = StackModel::get($request->getParam('stack_id'));
                }
                if (isset($params['stack_history_id'])) {
                    $is_short = false;
                    $model = StackHistoryModel::get($request->getParam('stack_history_id'));
                }
                $values = $request->getValues([
                    'exchange_account' => ['required', 'int']
                ]);
                $ex_account = HedgingExAccount::get($values['exchange_account']);

                if ($is_short) {
                    /** @var StackModel $model */
                    try {
                        HedgingExchangeModule::createShort($model, $ex_account);
                    } catch (BadRequest $e) {
                        return [
                            $this->showErrorToast("Bad request " . $e->getMessage())
                        ];
                    }
                    return [
                        $this->closeModal(),
                        $this->showToast('Short added successfully'),
                        $this->buildInfoStackModal($model, ['stack_id' => $model->id], $request->getValues())
                    ];
                } else {
                    /** @var StackHistoryModel $model */
                    try {
                        HedgingExchangeModule::createLong($model, $ex_account);
                    } catch (BadRequest $e) {
                        return [
                            $this->showErrorToast("Bad request " . $e->getMessage())
                        ];
                    }
                    return [
                        $this->closeModal(),
                        $this->showToast('Long added successfully'),
                        $this->buildInfoStackModal(
                            StackModel::get($model->stack_id),
                            ['stack_id' => $model->stack_id],
                            $request->getValues()
                        )
                    ];
                }
            });
    }
}