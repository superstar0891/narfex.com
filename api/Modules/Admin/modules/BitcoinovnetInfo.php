<?php

namespace Admin\modules;

use Admin\helpers\ActionRequest;
use Admin\helpers\DataManager;
use Admin\helpers\FormManager;
use Admin\helpers\PageContainer;
use Admin\layout\Action;
use Admin\layout\Block;
use Admin\layout\Button;
use Admin\layout\Clipboard;
use Admin\layout\Group;
use Admin\layout\InfoList;
use Admin\layout\InfoListItem;
use Admin\layout\Input;
use Admin\layout\Layout;
use Admin\layout\NumberFormat;
use Admin\layout\Time;
use Admin\layout\Toast;
use Core\Blockchain\Factory;
use Core\Response\JsonResponse;
use Core\Services\Telegram\SendService;
use Db\Model\ModelSet;
use Db\Transaction;
use Db\Where;
use Exception;
use Models\HedgingTransactionModel;
use Models\TransactionModel;
use Models\UserPermissionModel;
use Modules\BitcoinovnetModule;
use Modules\HedgingExchangeModule;
use Modules\WalletModule;

class BitcoinovnetInfo extends PageContainer {

    public static $permission_list = [
        UserPermissionModel::ADMIN_BITCOINOVNET,
    ];

    /* @var DataManager */
    private $table;

    /** @var Action */
    private $info_action;

    /** @var Action */
    private $withdraw_action;

    /** @var Action */
    private $change_leverage_action;

    /** @var FormManager */
    private $withdraw_form;

    /** @var FormManager */
    private $leverage_form;

    private $short_leverage = 0;

    private $long_leverage = 0;

    public function registerActions() {
        $this->createTransactionsTable();

        $this->createWithdrawalAction();
        $this->createWithdrawalForm();

        $this->createChangeLeverageAction();
        $this->createLeverageForm();
    }

    public function build() {
        $balances = $this->getBalances();

        $buttons = [];
        $buttons[] = Button::withParams('Withdrawal')
            ->onClick($this->withdraw_action);
        $buttons[] = Button::withParams('Short leverage')
            ->onClick($this->change_leverage_action->use([
                'leverage' => $this->short_leverage,
                'account' => 'short',
            ]));
        $buttons[] = Button::withParams('Long leverage')
            ->onClick($this->change_leverage_action->use([
                'leverage' => $this->long_leverage,
                'account' => 'long',
            ]));

        $this->layout->push(Block::withParams('Actions', Group::withItems(...$buttons)));
        $this->layout->push(Block::withParams('Balances', $balances));
        $this->layout->push(Block::withParams('Transactions', $this->table->build()));
    }

    private function getBalances(): Layout {
        try {
            $inst = Factory::getBtcBitcoinovnetInstance();
            $info = $inst->getWalletInfo();
        } catch (Exception $e) {
            $info = ['balance' => 0];
            JsonResponse::errorMessage($e->getMessage());
        }

        $items = [];
        $items[] = InfoListItem::withParams(
            'Blockchain Balance',
            NumberFormat::withParams(floatval($info['balance']), CURRENCY_BTC, ['hidden_currency' => true])
        );

        try {
            $short_conf = KERNEL_CONFIG['bitcoinovnet_hedging']['bitmex']['short'];
            $long_conf = KERNEL_CONFIG['bitcoinovnet_hedging']['bitmex']['long'];
            $exchange_short = HedgingExchangeModule::getExchange('bitmex', $short_conf['key'], $short_conf['secret']);
            $exchange_long = HedgingExchangeModule::getExchange('bitmex', $long_conf['key'], $long_conf['secret']);

            $short_balance = $exchange_short->getBalance();
            $long_balance = $exchange_long->getBalance();

            $symbol_short = $exchange_short->mapSymbol(\Symbols::BTCUSD);
            $symbol_long = $exchange_long->mapSymbol(\Symbols::BTCUSD);

            $short_position_info = $exchange_short->getPosition($symbol_short);
            $short_position = array_get_val($short_position_info, 'amount', 0);
            $this->short_leverage = array_get_val($short_position_info, 'leverage', 0);

            $long_position_info = $exchange_long->getPosition($symbol_long);
            $long_position = array_get_val($long_position_info, 'amount', 0);
            $this->long_leverage = array_get_val($long_position_info, 'leverage', 0);
        } catch (Exception $e) {
            $short_balance = 0;
            $short_position = 0;

            $long_balance = 0;
            $long_position = 0;

            JsonResponse::errorMessage($e->getMessage());
        }

        $items[] = InfoListItem::withParams(
            "Bitmex Short (Leverage x{$this->short_leverage})",
            NumberFormat::withParams($short_balance * $this->short_leverage, CURRENCY_BTC, ['hidden_currency' => true])
        );

        $items[] = InfoListItem::withParams(
            "Bitmex Short",
            NumberFormat::withParams($short_balance, CURRENCY_BTC, ['hidden_currency' => true])
        );

        $items[] = InfoListItem::withParams(
            "Bitmex Long (Leverage x{$this->long_leverage})",
            NumberFormat::withParams($long_balance * $this->long_leverage, CURRENCY_BTC, ['hidden_currency' => true])
        );

        $items[] = InfoListItem::withParams(
            "Bitmex Long",
            NumberFormat::withParams($long_balance, CURRENCY_BTC, ['hidden_currency' => true])
        );

        $items[] = InfoListItem::withParams(
            'In short position',
            NumberFormat::withParams($short_position, CURRENCY_USD)
        );

        $items[] = InfoListItem::withParams(
            'In long position',
            NumberFormat::withParams($long_position, CURRENCY_USD)
        );

        return InfoList::withItems(...$items);
    }

    private function createTransactionsTable() {
        $headers = ['ID', 'Txid', 'Status', 'Category', 'Confirmations', 'Amount', 'Date', 'Actions'];
        $this->table = $this
            ->createManagedTable(TransactionModel::class, $headers, Where::equal('platform', PLATFORM_BITCOINOVNET))
            ->setDataMapper(function (ModelSet $transactions) {
                return $transactions->map(function (TransactionModel $transaction) {
                    return [
                        $transaction->id,
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
                    Input::withParams('txid', 'Txid'),
                    Input::withParams('date_from', 'Date from d/m/Y'),
                    Input::withParams('date_to', 'Date to d/m/Y'),
                ];
            })
            ->setFiltering(function (array $filters, Where $where) {
                if (isset($filters['txid'])) {
                    $where->set('txid', Where::OperatorLike, '%' . $filters['txid'] . '%');
                }
                $where = DataManager::applyDateFilters($filters, $where);
                return $where;
            });

        $this->info_action = $this->createAction(function (ActionRequest $request) {
            $transaction_id = $request->getParam('transaction_id');
            try {
                /** @var TransactionModel $transaction */
                $transaction = TransactionModel::get($transaction_id);
            } catch (Exception $e) {
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

    public function createWithdrawalAction() {
        $this->withdraw_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal(
                'Withdraw BTC',
                $this->withdraw_form->setParams($request->getParams())->build()
            );
        });
    }

    public function createWithdrawalForm() {
        $this->withdraw_form = $this->createFormManager()
            ->setItems(function (array $params) {
                return [
                    Input::withParams('address', 'Address'),
                    Input::withParams('amount', 'Amount'),
                    Input::withParams('secret', 'Secret key')
                ];
            })->onSubmit(function (ActionRequest $request) {
                $admin = $this->getAdmin();
                $currency = CURRENCY_BTC;

                /* @var string $address
                 * @var int $amount
                 * @var string $secret
                 */
                extract($request->getValues([
                        'address' => ['required'],
                        'amount' => ['required', 'positive'],
                        'secret' => ['required']
                    ]
                ));

                $conf = KERNEL_CONFIG['admin_withdraw'];
                if ($secret !== $conf['secret']) {
                    return $this->showErrorToast('Secret is incorrect');
                }

                if (!isset($conf['whitelist'][$currency])) {
                    return $this->showErrorToast('Unsupported currency');
                }

                $white_list = explode(',', $conf['whitelist'][$currency]);
                $white_list = array_map('trim', $white_list);
                $white_list = array_filter($white_list);

                if (!in_array($address, $white_list, true)) {
                    return $this->showErrorToast('Address is incorrect');
                }

                $instance = Factory::getBtcBitcoinovnetInstance();

                $balance = $instance->getWalletInfo()['balance'];
                $from_address = null;
                $password = null;

                if ($balance < $amount) {
                    return $this->showErrorToast('Not enough money to transfer founds');
                }

                try {
                    $txid = $instance->sendToAddress(
                        $from_address,
                        $address,
                        $amount,
                        $password
                    );

                    BitcoinovnetModule::updateBitcoinovnetBalance();
                } catch (\Exception $e) {
                    return $this->showErrorToast($e->getMessage());
                }

                Transaction::wrap(function () use ($txid, $currency, $amount, $address, $admin) {
                    WalletModule::createTransaction('send', $currency, $amount, [
                        'status' => TransactionModel::STATUS_UNCONFIRMED,
                        'txid' => $txid,
                        'to' => $address,
                        'user_id' => $admin->id,
                        'platform' => PLATFORM_BITCOINOVNET,
                    ]);

                    $telegram = new SendService(SendService::CHAT_BITCOINOVNET);
                    $telegram->sendMessageSafety('Blockchain wallet withdrawal, amount: ' . formatNum($amount, 6));
                    BitcoinovnetModule::addHedgingQueues($amount, HedgingTransactionModel::TYPE_BUY, 'short', null, $admin);
                });


                return [
                    $this->closeModal(),
                    $this->showToast('Transaction created'),
                    $this->openPage($this->id())
                ];
            }, true);
    }

    public function createChangeLeverageAction() {
        $this->change_leverage_action = $this->createAction(function(ActionRequest $request) {
            $account = $request->getParam('account');

            return [
                $this->showModal(
                    "Change {$account} leverage",
                    $this->leverage_form->setParams($request->getParams())->build()
                )
            ];
        });
    }

    public function createLeverageForm() {
        $this->leverage_form = $this->createFormManager()
            ->setItems(function ($params) {
                return [
                    Input::withParams(
                        'leverage',
                        'Leverage',
                        array_get_val($params, 'leverage', 0),
                        '',
                        'Leverage'
                    )
                ];
            })
            ->onSubmit(function (ActionRequest $request) {
                $leverage = (int) $request->getValue('leverage', ['required']);
                $account = $request->getParam('account');

                try {
                    $conf = KERNEL_CONFIG['bitcoinovnet_hedging']['bitmex'][$account];
                    $exchange = HedgingExchangeModule::getExchange('bitmex', $conf['key'], $conf['secret']);
                    $exchange->setLeverage($exchange->mapSymbol(\Symbols::BTCUSD), $leverage);
                } catch (Exception $e) {
                    return [
                        $this->showErrorToast($e->getMessage())
                    ];
                }

                return [
                    $this->openPage($this->id()),
                    $this->closeModal(),
                ];
            }, true);
    }
}
