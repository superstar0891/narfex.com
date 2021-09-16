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
use Admin\layout\Button;
use Admin\layout\DropDown;
use Admin\layout\Group;
use Admin\layout\Input;
use Admin\layout\NumberFormat;
use Admin\layout\Select;
use Admin\layout\Tab;
use Admin\layout\TableRow;
use Admin\layout\Text;
use Admin\layout\Time;
use Admin\layout\Toast;
use Admin\SubModules\HedgingStackModal;
use DateInterval;
use DatePeriod;
use DateTime;
use Db\Model\Model;
use Db\Model\ModelSet;
use Db\Where;
use Exception;
use Exceptions\InvalidKeyException;
use Models\HedgingExAccount;
use Models\StackHistoryModel;
use Models\StackModel;
use Models\UserPermissionModel;

class HedgingStacks extends PageContainer {
    use HedgingStackModal;

    public static $permission_list = [
        UserPermissionModel::HEDGING_STACKS,
    ];

    /* @var DataManager */
    private $tabs;
    /* @var DataManager */
    private $table;
    /* @var DataManager */
    private $statistics_table;
    /** @var Action */
    private $show_exchange_accounts;
    /** @var Action */
    private $add_exchange_account;
    /** @var Action */
    private $edit_exchange_account;
    /** @var Action */
    private $add_stack_action;
    /** @var Action */
    private $edit_stack_action;
    /** @var Action */
    private $close_stack_action;
    /** @var Action */
    private $add_sale_action;
    /** @var Action */
    private $add_withdrawal_action;
    /** @var Action */
    private $add_withdrawal_two_step_action;
    /** @var FormManager */
    private $exchange_account_form;
    /** @var DataManager */
    private $exchange_account_table;
    /** @var FormManager */
    private $add_stack_form;
    /** @var FormManager */
    private $sale_form;
    /** @var FormManager */
    private $edit_stack_form;
    /** @var FormManager */
    private $close_stack_form;
    /** @var FormManager */
    private $withdrawal_form;
    /** @var FormManager */
    private $withdrawal_two_step_form;

    public function registerActions() {
        parent::registerActions();
        $this->createStackTabs();

        $this->addExchangeAccountAction();
        $this->showExchangeAccountsAction();
        $this->editExchangeAccountAction();
        $this->createEditStackAction();
        $this->createCloseStackAction();
        $this->createAddSaleAction();
        $this->createWithdrawalTwoStepAction();
        $this->createWithdrawalAction();
        $this->createAddStackAction();

        $this->createStackStatisticsTable();
        $this->createStacksTable();
        $this->exchangeAccountsTable();

        $this->editStackForm();
        $this->addStackForm();
        $this->createSaleForm();
        $this->withdrawalOneStepForm();
        $this->withdrawalTwoStepForm();
        $this->createCloseStackForm();
        $this->exchangeAccountsForm();
    }

    public function build() {
        $buttons = [];
        $buttons[] = Button::withParams('Add stack')
            ->onClick($this->add_stack_action);
        $buttons[] = Button::withParams('Add exchange account')
            ->onClick($this->add_exchange_account);
        $buttons[] = Button::withParams('Show accounts')
            ->onClick($this->show_exchange_accounts);

        $this->layout->push(Block::withParams('Actions', Group::withItems(...$buttons)));
        $this->layout->push(Block::withParams('Stacks', $this->tabs->build()));
    }

    private function createStackTabs() {
        $this->tabs = $this->createTabsManager()
            ->setTabs(
                Tab::withParams('Stacks')->setRenderer(function () {
                    return $this->table->build();
                }),
                Tab::withParams('Statistics')->setRenderer(function () {
                    return $this->statistics_table->build();
                })
            );
    }

    private function createStacksTable() {
        $headers = ['ID', 'Name', 'Primary', 'Buy rate', 'Buy fee', 'Short rate', 'Short fee', 'Date', 'Close date', 'Actions'];
        $this->table = $this
            ->createManagedTable(StackModel::class, $headers)
            ->setDataMapper(function (ModelSet $stacks) {
                return $stacks->map(function (StackModel $stack) {
                    $columns = [
                        $stack->id,
                        $stack->name ?? 'Stack ' . $stack->id,
                        NumberFormat::withParams($stack->primary_amount, $stack->primary_currency),
                        NumberFormat::withParams($stack->buy_rate, $stack->secondary_currency),
                        NumberFormat::withParams($stack->buy_fee, null, ['percent' => true, 'fraction_digits' => 4]),
                        NumberFormat::withParams($stack->short_rate ?? 0, 'usd'),
                        NumberFormat::withParams($stack->short_fee ?? 0, null, ['percent' => true, 'fraction_digits' => 4]),
                        Time::withParams($stack->created_at_timestamp),
                        $stack->close_at_timestamp ? Time::withParams($stack->close_at_timestamp) : 'Not closed',
                        ActionSheet::withItems(
                            ActionSheetItem::withParams('Info')
                                ->onClick($this->info_stack_action->use(['stack_id' => $stack->id])),
                            ActionSheetItem::withParams('Add sale')
                                ->onClick($this->add_sale_action->use(['stack_id' => $stack->id])),
                            ActionSheetItem::withParams('Add withdrawal in one step')
                                ->onClick($this->add_withdrawal_action->use(['stack_id' => $stack->id])),
                            ActionSheetItem::withParams('Add withdrawal in two step')
                                ->onClick($this->add_withdrawal_two_step_action->use(['stack_id' => $stack->id])),
                            ActionSheetItem::withParams('Edit')
                                ->onClick($this->edit_stack_action->use(['id' => $stack->id])),
                            ActionSheetItem::withParams('Close')
                                ->onClick($this->close_stack_action->use(['id' => $stack->id]))
                        ),
                    ];

                    $style = TableRow::STYLE_SUCCESS;
                    if ($stack->close_long_rate !== null) {
                        $style = TableRow::STYLE_DEFAULT;
                    }
                    return TableRow::withParams(...$columns)->setStyle($style);
                });
            })
            ->setSearchForm(function () {
                return [
                    DropDown::withParams('status', 'Select status', [
                        [
                            'label' => 'All',
                            'value' => 'all',
                        ],
                        [
                            'label' => 'Open',
                            'value' => 'open',
                        ],
                        [
                            'label' => 'Close',
                            'value' => 'close',
                        ],
                    ]),
                ];
            })
            ->setFiltering(function (array $filters, Where $where) {
                if (isset($filters['status'])) {
                    switch ($filters['status']) {
                        case 'all':
                            return Where::or();
                            break;
                        case 'open':
                            $where->set('close_long_rate', Where::OperatorIs, NULL);
                            break;
                        case 'close':
                            $where->set('close_long_rate', Where::OperatorIsNot, NULL);
                            break;
                    }
                }

                return $where;
            });
    }

    public function editStackForm() {
        $this->edit_stack_form = $this->createStackForm();
    }

    public function addStackForm() {
        $this->add_stack_form = $this->createStackForm();
    }

    private function createStackForm() {
        $blockchain_currencies = blockchain_currencies();
        $fiat_currencies = fiat_currencies();

        return $this->createFormManager()
            ->setItems(function ($params) use ($blockchain_currencies, $fiat_currencies) {
                $id = array_get_val($params, 'id');

                $name = '';
                $primary_amount = '';
                $primary_currency = '';
                $buy_rate = '';
                $fiat_to_usd = '';
                $secondary_currency = '';
                $buy_fee = '';
                $short_rate = '';
                $short_fee = '';

                if (!is_null($id)) {
                    $stack = StackModel::get($id);
                    $name = $stack->name;
                    $primary_amount = $stack->primary_amount;
                    $primary_currency = $stack->primary_currency;
                    $buy_rate = $stack->buy_rate;
                    $fiat_to_usd = $stack->fiat_to_usd;
                    $secondary_currency = $stack->secondary_currency;
                    $buy_fee = $stack->buy_fee;
                    $short_rate = $stack->short_rate ?? '';
                    $short_fee = $stack->short_fee ?? '';
                }

                return [
                    Input::withParams(
                        'name', 'Name',
                        $name, '', 'Name'
                    ),
                    Input::withParams(
                        'primary_amount', 'Primary amount',
                        $primary_amount, '', 'Primary amount'
                    ),
                    Select::withParams(
                        'primary_currency', 'Primary currency', $blockchain_currencies,
                        $primary_currency == '' ? CURRENCY_BTC : $primary_currency, 'Primary currency'
                    ),
                    Input::withParams(
                        'buy_rate', 'Buy rate',
                        $buy_rate, '', 'Buy rate'
                    ),
                    Select::withParams(
                        'secondary_currency', 'Primary currency', $fiat_currencies,
                        $secondary_currency == '' ? CURRENCY_IDR : $secondary_currency, 'Secondary currency'
                    ),
                    Input::withParams(
                        'fiat_to_usd', 'Fiat rate to USD',
                        $fiat_to_usd, '', 'Fiat rate to USD'
                    ),
                    Input::withParams(
                        'buy_fee', 'Buy fee',
                        $buy_fee == '' ? 0.3 : $buy_fee, '', 'Buy fee'
                    ),
                    Input::withParams(
                        'short_rate', 'Short rate',
                        $short_rate, '', 'Short rate'
                    ),
                    Input::withParams(
                        'short_fee', 'Short fee',
                        $short_fee == '' ? 0.075 : $short_fee, '', 'Short fee'
                    ),
                ];
            })
            ->onSubmit(function (ActionRequest $request) use ($blockchain_currencies, $fiat_currencies) {
                try {
                    $id = $request->getParam('id');
                    $stack = StackModel::get($id);
                } catch (InvalidKeyException $e) {
                    $stack = new StackModel();
                } catch (Exception $e) {
                    return [
                        $this->closeModal(),
                        $this->showToast($e->getMessage(), Toast::TYPE_ERROR),
                    ];
                }

                $filters = [
                    'primary_amount' => ['required', 'double'],
                    'primary_currency' => [
                        'required', 'maxLen' => 32, 'minLen' => 2,
                        'oneOf' => array_keys($blockchain_currencies)
                    ],
                    'buy_rate' => ['required', 'double'],
                    'fiat_to_usd' => ['required', 'double'],
                    'secondary_currency' => [
                        'required','maxLen' => 32, 'minLen' => 2,
                        'oneOf' => array_keys($fiat_currencies)
                    ],
                    'buy_fee' => ['required', 'double'],
                ];

                $values = $request->getValues($filters);
                foreach ($values as $key => $value) {
                    if ($value == '') {
                        continue;
                    }
                    $stack->$key = $value;
                }
                if (false === (bool) $stack->name) {
                    $stack->name = 'Stack ' . $stack->id;
                }
                $stack->save();

                return [
                    $this->table->getReloadAction($request->getParams(), $request->getValues()),
                    $this->closeModal(),
                    $this->showToast('Stack saved'),
                ];
            });
    }

    public function withdrawalTwoStepForm() {
        $this->withdrawal_two_step_form = $this->createWithdrawalForm();
    }

    public function withdrawalOneStepForm() {
        $this->withdrawal_form = $this->createWithdrawalForm(false);
    }

    private function createWithdrawalForm($two_step = true) {
        $fiat_currencies = fiat_currencies();

        return $this->createFormManager()
            ->setItems(function ($params) use ($fiat_currencies, $two_step) {
                $id = array_get_val($params, 'id');

                if (!is_null($id)) {
                    try {
                        $stack_history = StackHistoryModel::get($id);
                        $amount = $stack_history->amount;
                        $currency = $stack_history->currency;

                        $swap_rates = json_decode($stack_history->swap_rate, true);
                        $swap_fees = json_decode($stack_history->swap_fee, true);
                        if ($two_step) {
                            $swap_rate_usd = $swap_rates['swap_rate_usd'];
                            $swap_fee_usd = $swap_fees['swap_fee_usd'];
                        }
                        $swap_rate_idr = $swap_rates['swap_rate_idr'];
                        $swap_fee_idr = $swap_fees['swap_fee_idr'];
                    } catch (Exception $e) {
                        //
                    }
                }

                $inputs = [
                    Input::withParams(
                        'amount', 'Amount',
                        isset($amount) ? $amount : '', '', 'Amount'
                    ),
                    Select::withParams(
                        'currency', 'Currency', $fiat_currencies,
                        isset($currency) ? $currency : CURRENCY_RUB, 'Currency'
                    ),
                ];

                if ($two_step) {
                    $inputs[] = Input::withParams(
                        'swap_rate_usd', 'Swap rate to USD',
                        isset($swap_rate_usd) ? $swap_rate_usd : '', '', 'Swap rate to USD'
                    );
                    $inputs[] = Input::withParams(
                        'swap_fee_usd', 'Swap fee to USD',
                        isset($swap_fee_usd) ? $swap_fee_usd : 0, '', 'Swap fee to USD'
                    );
                }

                $inputs[] = Input::withParams(
                    'swap_rate_idr', 'Swap rate to IDR',
                    isset($swap_rate_idr) ? $swap_rate_idr : '', '', 'Swap rate to IDR'
                );
                $inputs[] = Input::withParams(
                    'swap_fee_idr', 'Swap fee to IDR',
                    isset($swap_fee_idr) ? $swap_fee_idr : 0, '', 'Swap fee to IDR'
                );

                return $inputs;
            })
            ->onSubmit(function (ActionRequest $request) use ($fiat_currencies, $two_step) {
                $stack_history = $this->onSubmitStackHistory($request);
                if (false === $stack_history instanceof StackHistoryModel) {
                    return $stack_history;
                }
                /** @var StackHistoryModel $stack_history */
                $stack_id = $request->getParam('stack_id');

                $filters = [
                    'amount' => ['required', 'double'],
                    'currency' => [
                        'required', 'maxLen' => 32, 'minLen' => 2,
                        'oneOf' => array_keys($fiat_currencies)
                    ],
                    'swap_rate_idr' => ['required', 'double'],
                    'swap_fee_idr' => ['required', 'double'],
                ];

                if ($two_step) {
                    $filters['swap_rate_usd'] = [
                        'required', 'double'
                    ];
                    $filters['swap_fee_usd'] = [
                        'required', 'double'
                    ];
                }

                $values = $request->getValues($filters);

                $stack_history->type = StackHistoryModel::TYPE_WITHDRAWAL;
                $stack_history->stack_id = $stack_id;
                $stack_history->amount = $values['amount'];
                $stack_history->currency = $values['currency'];
                $stack_history->swap_rate = json_encode(
                    array_filter($values, function ($key) {
                        return strpos($key, 'swap_rate') !== false;
                    }, ARRAY_FILTER_USE_KEY)
                );
                $stack_history->swap_fee = json_encode(
                    array_filter($values, function ($key) {
                        return strpos($key, 'swap_fee') !== false;
                    }, ARRAY_FILTER_USE_KEY)
                );
                $stack_history->save();

                return [
                    $this->closeModal(),
                    $this->showToast('Withdrawal saved'),
                    $this->buildInfoStackModal(StackModel::get($stack_id), $request->getParams(), $request->getValues()),
                ];
            });
    }

    private function createSaleForm() {
        $fiat_currencies = fiat_currencies();
        $blockchain_currencies = blockchain_currencies();

        $this->sale_form = $this->createFormManager()
            ->setItems(function ($params) use ($blockchain_currencies, $fiat_currencies) {
                $id = array_get_val($params, 'id');

                $amount = '';
                $currency = '';
                $sale_rate = '';
                $sale_currency = '';
                $fiat_to_usd = '';
                $long_rate = '';
                $long_fee = '';

                if (!is_null($id)) {
                    try {
                        $stack_history = StackHistoryModel::get($id);
                        $amount = $stack_history->amount ?? '';
                        $currency = $stack_history->currency ?? '';
                        $sale_rate = $stack_history->sale_rate ?? '';
                        $sale_currency = $stack_history->sale_currency ?? '';
                        $fiat_to_usd = $stack_history->fiat_to_usd ?? '';
                        $long_rate = $stack_history->long_rate ?? '';
                        $long_fee = $stack_history->long_fee ?? '';
                    } catch (Exception $e) {
                        //
                    }
                }

                return [
                    Input::withParams('amount', 'Amount', $amount, '', 'Amount'),
                    Select::withParams(
                        'currency', 'Currency', $blockchain_currencies,
                        $currency == '' ? CURRENCY_BTC : $currency, 'Currency'
                    ),
                    Input::withParams('sale_rate', 'Sale rate', $sale_rate, '', 'Sale rate'),
                    Select::withParams(
                        'sale_currency', 'Sale currency', $fiat_currencies,
                        $sale_currency == '' ? CURRENCY_RUB : $sale_currency, 'Sale currency'
                    ),
                    Input::withParams(
                        'fiat_to_usd', 'Fiat rate to USD',
                        $fiat_to_usd, '', 'Fiat rate to USD'
                    ),
                    Input::withParams(
                        'long_rate', 'Long rate', $long_rate, '', 'Long rate'),
                    Input::withParams(
                        'long_fee', 'Long fee',
                        $long_fee == '' ? 0.075 : $long_fee, '', 'Long fee'
                    ),
                ];
            })
            ->onSubmit(function (ActionRequest $request) use ($blockchain_currencies, $fiat_currencies) {
                $stack_history = $this->onSubmitStackHistory($request);
                if (false === $stack_history instanceof StackHistoryModel) {
                    return $stack_history;
                }
                $stack_id = $request->getParam('stack_id');

                $values = $request->getValues([
                    'amount' => ['required', 'double'],
                    'currency' => [
                        'required', 'maxLen' => 32, 'minLen' => 2,
                        'oneOf' => array_keys($blockchain_currencies)
                    ],
                    'sale_rate' => ['required', 'double'],
                    'sale_currency' => [
                        'required','maxLen' => 32, 'minLen' => 2,
                        'oneOf' => array_keys($fiat_currencies)
                    ],
                ]);

                $stack_history->type = StackHistoryModel::TYPE_SALE;
                $stack_history->stack_id = $stack_id;
                foreach ($values as $key => $value) {
                    if ($value == '') {
                        continue;
                    }
                    $stack_history->$key = $value;
                }
                $stack_history->save();

                return [
                    $this->closeModal(),
                    $this->showToast('Sale saved'),
                    $this->buildInfoStackModal(StackModel::get($stack_id), $request->getParams(), $request->getValues()),
                ];
            });
    }

    /**
     * @param ActionRequest $request
     * @return array|Model
     */
    private function onSubmitStackHistory(ActionRequest $request) {
        try {
            $id = $request->getParam('id');
            $stack_history = StackHistoryModel::get($id);
        } catch (InvalidKeyException $e) {
            $stack_history = new StackHistoryModel();
        } catch (Exception $e) {
            return [
                $this->closeModal(),
                $this->showToast($e->getMessage(), Toast::TYPE_ERROR),
            ];
        }

        return $stack_history;
    }

    private function createCloseStackForm() {
        $fiat_currencies = fiat_currencies();

        $this->close_stack_form = $this->createFormManager()
            ->setItems(function ($params) use ($fiat_currencies) {
                $id = array_get_val($params, 'id');

                $close_short_fee = '';
                $close_short_rate = '';
                $close_short_currency = '';
                $close_long_fee = '';
                $close_long_rate = '';
                $close_long_currency = '';

                if (!is_null($id)) {
                    try {
                        $stack = StackModel::get($id);
                        $close_short_fee = $stack->close_short_fee ?? '';
                        $close_short_rate = $stack->close_short_rate ?? '';
                        $close_short_currency = $stack->close_short_currency ?? '';
                        $close_long_fee = $stack->close_long_fee ?? '';
                        $close_long_rate = $stack->close_long_rate ?? '';
                        $close_long_currency = $stack->close_long_currency ?? '';
                    } catch (Exception $e) {
                        //
                    }
                }

                return [
                    Input::withParams(
                        'close_short_fee', 'Close short fee',
                        $close_short_fee == '' ? 0.075 : $close_short_fee,
                        '', 'Close short fee'
                    ),
                    Input::withParams(
                        'close_short_rate', 'Close short rate',
                        $close_short_rate, '', 'Close short rate'
                    ),
                    Select::withParams(
                        'close_short_currency', 'Close short currency', $fiat_currencies,
                        $close_short_currency == '' ? CURRENCY_USD : $close_short_currency, 'Close short currency'
                    ),
                    Input::withParams(
                        'close_long_fee', 'Close long fee',
                        $close_long_fee == '' ? 0.075 : $close_long_fee,
                        '', 'Close long fee'
                    ),
                    Input::withParams(
                        'close_long_rate', 'Close long rate',
                        $close_long_rate, '', 'Close long rate'
                    ),
                    Select::withParams(
                        'close_long_currency', 'Close long currency', $fiat_currencies,
                        $close_long_currency == '' ? CURRENCY_USD : $close_long_currency,
                        'Close long currency'
                    ),
                ];
            })
            ->onSubmit(function (ActionRequest $request) use ($fiat_currencies) {
                try {
                    $id = $request->getParam('id');
                    $stack = StackModel::get($id);
                } catch (Exception $e) {
                    return [
                        $this->closeModal(),
                        $this->showToast($e->getMessage(), Toast::TYPE_ERROR),
                    ];
                }

                $currencies = array_keys($fiat_currencies);

                $filters = [
                    'close_short_fee' => ['required', 'double'],
                    'close_short_rate' => ['required', 'double'],
                    'close_short_currency' => [
                        'required', 'maxLen' => 32, 'minLen' => 2,
                        'oneOf' => $currencies,
                    ],
                    'close_long_fee' => ['required', 'double'],
                    'close_long_rate' => ['required', 'double'],
                    'close_long_currency' => [
                        'required', 'maxLen' => 32, 'minLen' => 2,
                        'oneOf' => $currencies,
                    ],
                ];

                $values = $request->getValues($filters);
                foreach ($values as $key => $value) {
                    $stack->$key = $value;
                }
                if (is_null($stack->close_at_timestamp)) {
                    $stack->close_at_timestamp = time();
                }
                $stack->save();

                return [
                    $this->table->getReloadAction($request->getParams(), $request->getValues()),
                    $this->closeModal(),
                    $this->showToast('Stack saved'),
                ];
            });
    }


    private function createEditStackAction() {
        $this->edit_stack_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal('Edit stack', $this->edit_stack_form->setParams($request->getParams())->build());
        });
    }

    private function createCloseStackAction() {
        $this->close_stack_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal('Close stack', $this->close_stack_form->setParams($request->getParams())->build());
        });
    }

    private function createAddSaleAction() {
        $this->add_sale_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal(
                'Save sale',
                $this->sale_form->setParams($request->getParams())->build()
            );
        });
    }

    private function createWithdrawalTwoStepAction() {
        $this->add_withdrawal_two_step_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal(
                'Save withdrawal in two steps',
                $this->withdrawal_two_step_form->setParams($request->getParams())->build()
            );
        });
    }

    private function createWithdrawalAction() {
        $this->add_withdrawal_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal(
                'Save withdrawal in one step',
                $this->withdrawal_form->setParams($request->getParams())->build()
            );
        });
    }

    private function createAddStackAction() {
        $this->add_stack_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal('Add stack', $this->add_stack_form->setParams($request->getParams())->build());
        });
    }

    private function createStackStatisticsTable() {
        $this->statistics_table = $this->createManagedTable(
            StackModel::class,
            ['Date', 'Count', 'Profit USD', 'Profit', 'Total amount'],
            Where::and()
                ->set('close_short_rate', Where::OperatorIsNot, NULL)
                ->set('close_long_rate', Where::OperatorIsNot, NULL)
        )
            ->setDataMapper(function (ModelSet $stacks, $params) {
                $data = [];
                $period = array_get_val($params, 'period', 'day');

                if ($stacks->isEmpty()) {
                    return [];
                }

                $array_stacks = $stacks->toArray();
                /** @var StackModel $last_stack */
                $last_stack = $stacks->last();
                /** @var StackModel $first_stack */
                $first_stack = $stacks->first();
                $last_date = (new DateTime)->setTimestamp($last_stack->close_at_timestamp);
                $first_date = (new DateTime)->setTimestamp($first_stack->close_at_timestamp);

                switch ($period) {
                    case 'day':
                        $first_date->modify('0:00 this day');
                        $last_date->modify('23:59:59 this day');
                        break;
                    case 'week':
                        $first_date->modify('0:00 monday this week');
                        break;
                    case 'month':
                        $first_date->modify('0:00 first day of this month');
                        break;
                }

                $date_range = new DatePeriod(
                    $first_date,
                    DateInterval::createFromDateString('1 ' . $period),
                    $last_date
                );

                foreach ($date_range as $date) {
                    /** @var DateTime $date */
                    foreach ($array_stacks as $key => $stack) {
                        /** @var StackModel $stack */
                        $start_date = clone $date;
                        $end_date = clone $date;
                        $start_date->setTime(0, 0);
                        switch ($period) {
                            case 'day':
                                $end_date->setTime(23, 59, 59);
                                break;
                            case 'week':
                                $end_date->modify('+1 weeks')->setTime(23, 59, 59);
                                break;
                            case 'month':
                                $end_date->modify('+1 month')->setTime(23, 59, 59);
                                break;
                        }

                        $start_date_timestamp = $start_date->getTimestamp();
                        $end_date_timestamp = $end_date->getTimestamp();
                        $close_stack_date = $stack->close_at_timestamp;

                        if ($period === 'day') {
                            $period_date = $date->format('d-m-Y');
                        } else {
                            $period_date = sprintf(
                                '%s - %s',
                                $start_date->format('d-m-Y'),
                                $end_date->format('d-m-Y')
                            );
                        }

                        if ($close_stack_date >= $start_date_timestamp && $close_stack_date <= $end_date_timestamp) {
                            $profits = $this->calcProfits($stack);
                            $profit = array_get_val($profits, 'profit', 0);

                            $profit_key = $stack->primary_currency . '_profit';
                            $profit_value = floatval(($profit * $stack->fiat_to_usd) / $stack->buy_rate);
                            $total_key = 'total_' . $stack->primary_currency;

                            if (isset($data[$period_date])) {
                                $data[$period_date]['count']++;
                                $data[$period_date]['profit_usd'] += $profit;
                                if (isset($data[$period_date][$total_key])) {
                                    $data[$period_date][$total_key] += $stack->primary_amount;
                                }
                                if (isset($data[$period_date][$profit_key])) {
                                    $data[$period_date][$profit_key] += $profit_value;
                                }
                            } else {
                                $data[$period_date] = [
                                    'count' => 1,
                                    $total_key => $stack->primary_amount,
                                    'profit_usd' => $profit,
                                    $profit_key => $profit_value,
                                    'date' => $period_date,
                                ];
                            }

                            unset($array_stacks[$key]);
                        } else {
                            break;
                        }
                    }
                }

                return array_map(function ($item) {
                    $profits = [];
                    $totals = [];
                    foreach ($item as $key => $value) {
                        if (strpos($key, '_profit') !== false) {
                            $currency = str_replace('_profit', '', $key);
                            $profits[] = Text::withParams(
                                sprintf(
                                    '%s %s ',
                                    formatNum($value, 8, ','),
                                    strtoupper($currency)
                                )
                            );
                        }
                        if (strpos($key, 'total_') !== false) {
                            $currency = str_replace('total_', '', $key);
                            $totals[] = Text::withParams(
                                sprintf(
                                    '%s %s ',
                                    formatNum($value, 8, ','),
                                    strtoupper($currency)
                                )
                            );
                        }
                    }

                    return [
                        $item['date'],
                        $item['count'],
                        NumberFormat::withParams($item['profit_usd'], 'usd'),
                        $profits,
                        $totals,
                    ];
                }, array_reverse($data));
            })
            ->setSearchForm(function () {
                return [
                    Select::withParams('period', 'Period', [
                        'day' => 'Per day',
                        'week' => 'Per week',
                        'month' => 'Per month',
                    ])
                ];
            })
            ->setFiltering(function (array $filters, Where $where) {
                if (isset($filters['period'])) {
                    $period = '1 month';
                    switch ($filters['period']) {
                        case 'day':
                            break;
                        case 'week':
                            $period = '3 month';
                            break;
                        case 'month':
                            $period = '1 year';
                            break;
                    }
                    $where->set(
                        'updated_at_timestamp',
                        Where::OperatorGreaterEq,
                        (new \DateTime("$period ago"))->getTimestamp()
                    );
                    return [$where, $filters];
                }

                $where->set(
                    'updated_at_timestamp',
                    Where::OperatorGreaterEq,
                    (new \DateTime('1 month ago'))->getTimestamp()
                );
                return $where;
            })
            ->setOrderBy(['created_at_timestamp' => 'ASC']);
    }

    public function addExchangeAccountAction() {
        $this->add_exchange_account = $this->createAction(function (ActionRequest $request) {
            return $this->showModal('Add exchange account', $this->exchange_account_form->build());
        });
    }

    public function showExchangeAccountsAction() {
        $this->show_exchange_accounts = $this->createAction(function (ActionRequest $request) {
            return $this->showModal('Exchange accounts', $this->exchange_account_table->build());
        });
    }

    public function editExchangeAccountAction() {
        $this->edit_exchange_account = $this->createAction(function (ActionRequest $request) {
            return $this->showModal(
                'Edit exchange accounts',
                $this->exchange_account_form->setParams($request->getParams())->build()
            );
        });
    }

    public function exchangeAccountsForm() {
        $this->exchange_account_form = $this->createFormManager()
            ->setItems(function($params) {
                $exchange = '';
                $description = '';
                $public_key = '';
                if (isset($params['id'])) {
                    $ex_account = HedgingExAccount::get($params['id']);
                    $exchange = $ex_account->exchange;
                    $description = $ex_account->description;
                    $public_key = $ex_account->public_key;
                }
                return [
                    DropDown::withParams('exchange', 'Choose exchange',[
                        ['label' => 'Binance', 'value' => HedgingExAccount::EXCHANGE_BINANCE],
                        ['label' => 'Bitmex', 'value' => HedgingExAccount::EXCHANGE_BITMEX]
                    ], $exchange),
                    Input::withParams('description', 'Description', $description, '', 'Description'),
                    Input::withParams('public_key', 'Public key', $public_key, '', 'Public key'),
                    Input::withParams('private_key', 'Private key', '', '', 'Private key'),
                ];
            })
            ->onSubmit(function (ActionRequest $request) {
                try {
                    $ex_account = HedgingExAccount::get($request->getParam('id'));
                } catch (InvalidKeyException $e) {
                    $ex_account = new HedgingExAccount();
                }

                $values = $request->getValues([
                    'exchange' => ['required', 'oneOf' => [HedgingExAccount::EXCHANGE_BINANCE, HedgingExAccount::EXCHANGE_BITMEX]],
                    'description' => ['required'],
                    'public_key' => ['required'],
                    'private_key' => ['required'],
                ]);

                foreach ($values as $key => $value) {
                    if ($key === 'private_key') {
                        $hash = encrypt($value);
                        $hex_hash = bin2hex($hash);
                        $ex_account->private_key = $hex_hash;
                    } else {
                        $ex_account->$key = $value;
                    }
                }
                $ex_account->save();

                return [
                    $this->exchange_account_table->getReloadAction($request->getParams(), $request->getValues()),
                    $this->closeModal(),
                    $this->showToast('Exchange account saved'),
                ];
            });
    }

    public function exchangeAccountsTable() {
        $headers = ['ID', 'Exchange', 'Description', 'Public key', 'Private key', 'Date', 'Actions'];
        $this->exchange_account_table = $this->createManagedTable(HedgingExAccount::class, $headers)
            ->setDataMapper(function (ModelSet $accounts) {
                return $accounts->map(function (HedgingExAccount $account) {
                    return [
                        $account->id,
                        $account->exchange,
                        $account->description,
                        $account->public_key,
                        '*************',
                        Time::withParams($account->created_at_timestamp),
                        ActionSheet::withItems(
                            ActionSheetItem::withParams('Edit')
                                ->onClick($this->edit_exchange_account->use(['id' => $account->id]))
                        )
                    ];
                });
            });
    }
}
