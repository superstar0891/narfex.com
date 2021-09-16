<?php

namespace Admin\modules;

use Admin\helpers\ActionRequest;
use Admin\helpers\DataManager;
use Admin\helpers\FormManager;
use Admin\helpers\PageContainer;
use Admin\layout\Action;
use Admin\layout\Block;
use Admin\layout\Button;
use Admin\layout\DropDown;
use Admin\layout\Group;
use Admin\layout\Input;
use Admin\layout\NumberFormat;
use Admin\layout\Toast;
use Db\Model\ModelSet;
use Db\Transaction;
use Db\Where;
use Models\BalanceModel;
use Models\FiatInvoiceModel;
use Models\FiatPaymentModel;
use Models\UserModel;
use Modules\BalanceModule;
use Modules\FiatWalletModule;
use Modules\StatsModule;

class FiatPayments extends PageContainer {

    /* @var DataManager */
    private $table;

    /* @var Action */
    private $add_action;

    /* @var FormManager */
    private $add_form;

    /* @var Action */
    private $search_action;

    /* @var FormManager */
    private $search_form;

    /* @var DataManager */
    private $search_table;

    public function registerActions() {
        $this->table = $this
            ->createManagedTable(FiatPaymentModel::class, ['ID', 'User ID', 'Amount', 'Status', 'Type', 'Date'])
            ->setDataMapper(function (ModelSet $payments) {
                return $payments->map(function (FiatPaymentModel $payment) {
                    return [
                        $payment->id,
                        $payment->user_id,
                        NumberFormat::withParams($payment->amount, $payment->currency),
                        $payment->status,
                        ucfirst($payment->payment_type),
                        date('d/m/Y', $payment->created_at_timestamp)
                    ];
                });
            });

        $currencies = array_map(function ($row) {
            return [$row, strtoupper($row)];
        }, KERNEL_CONFIG['fiat']['currencies']);

        $this->add_form = $this->createFormManager()
            ->setItems(
                function ($params) use ($currencies) {
                    return [
                        Input::withParams('user_id', 'User ID'),
                        Input::withParams('amount', 'Amount'),
                        DropDown::withParams('currency', 'Currency', $currencies)
                    ];
                })
            ->onSubmit(function (ActionRequest $request) {
                $values = $request->getValues();
                $params = $request->getParams();

                $user_id = (int) $values['user_id'];
                $amount = (double) $values['amount'];
                $currency = $values['currency'];

                /* @var UserModel $user */
                try {
                    $user = UserModel::get($user_id);
                } catch (\Exception $e) {
                    return $this->showToast('User not found', Toast::TYPE_ERROR);
                }

                if ($amount <= 0) {
                    return $this->showToast('Amount is incorrect', Toast::TYPE_ERROR);
                }

                Transaction::wrap(function () use ($user_id, $currency, $amount, $user) {
                    $fee_conf = KERNEL_CONFIG['fiat']['invoice_fee'][$currency];
                    $fee = max($fee_conf['min'], $amount * $fee_conf['percent'] / 100);

                    $balance = BalanceModule::getBalanceOrCreate($user_id, $currency, BalanceModel::CATEGORY_FIAT);
                    FiatWalletModule::addPayment('invoice', $balance, $amount - $fee, $user);
                    StatsModule::profit('invoice_fee', $fee, $currency, $user_id);
                });

                return [
                    $this->showToast('Payment added'),
                    $this->table->getReloadAction($params, $values),
                    $this->closeModal(),
                ];
            });


        $this->add_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal('New payment', $this->add_form->build());
        });

        $this->search_form = $this->createFormManager()
            ->setItems(
                function ($params) {
                    return [
                        Input::withParams('query', 'Enter name or login')
                    ];
                })
            ->setSubmitButtonText('Search')
            ->onSubmit(function (ActionRequest $request) {
                $this->search_table->setFilters(['query' => trim($request->getValue('query'))]);
                return $this->search_table->getReloadAction($request->getParams(), $request->getValues());
            });

        $this->search_table = $this
            ->createManagedTable(UserModel::class, ['ID', 'Name', 'Login', 'Invoices'])
            ->setDataMapper(function (ModelSet $users) {
                $invoices = FiatInvoiceModel::select(Where::in('user_id', $users->column('id')));
                $invoices_map = [];
                /* @var FiatInvoiceModel $invoice */
                foreach ($invoices as $invoice) {
                    if (!isset($invoices_map[$invoice->user_id])) {
                        $invoices_map[$invoice->user_id] = [];
                    }

                    $invoices_map[$invoice->user_id][] = $invoice->amount . ' ' . strtoupper($invoice->currency);
                }

                return $users->map(function (UserModel $user) use ($invoices_map) {
                    $invoices = isset($invoices_map[$user->id]) ? implode(', ', $invoices_map[$user->id]) : 'None';
                    return [
                        $user->id,
                        $user->first_name . ' ' . $user->last_name,
                        $user->login,
                        $invoices,
                    ];
                });
            })
            ->setFiltering(function (array $filters, Where $where) {
                if (!isset($filters['query']) || !trim($filters['query'])) {
                    $where->set('id', Where::OperatorEq, 0);
                } else {
                    $query = trim($filters['query']);
                    $where->set(Where::and()
                        ->set('active', Where::OperatorEq, 1)
                        ->set(Where::or()
                            ->set('login', Where::OperatorLike, "%{$query}%")
                            ->set("CONCAT(first_name, ' ', last_name)", Where::OperatorLike, "%{$query}%")
                        ));
                }

                return $where;
            });

        $this->search_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal(
                'New payment',
                $this->search_form->build(),
                $this->search_table->build()
            );
        });
    }

    public function build() {
        $actions = Block::withParams(
            'Actions',
            Group::withItems(
                Button::withParams('Add')->onClick($this->add_action),
                Button::withParams('Search invoices')->onClick($this->search_action)
            )
        );

        $this->layout->push($actions);
        $this->layout->push(Block::withParams('Payments', $this->table->build()));
    }
}
