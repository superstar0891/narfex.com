<?php

namespace Admin\modules;

use Admin\helpers\ActionRequest;
use \Admin\helpers\TabsManager;
use \Admin\helpers\PageContainer;
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
use Admin\layout\Tab;
use \Admin\helpers\DataManager;
use Core\Services\BalanceHistory\BalanceHistorySaver;
use Core\Services\Merchant\CardsService;
use Db\Model\ModelSet;
use Db\Transaction;
use Db\Where;
use Models\BalanceModel;
use Models\BankCardModel;
use Models\BankCardOperationModel;
use \Admin\helpers\FormManager;
use Models\RefillModel;
use Models\UserBalanceHistoryModel;
use Models\UserModel;
use Models\UserRoleModel;
use Modules\BalanceModule;
use Modules\FeeModule;
use Modules\NotificationsModule;
use \Models\UserPermissionModel;

class BankCards extends PageContainer {

    public static $permission_list = [UserPermissionModel::BANK_CARD_MANAGE];

    /* @var TabsManager */
    private $tabs;

    /* @var DataManager */
    private $review_table;

    /* @var DataManager */
    private $table;

    /* @var FormManager */
    private $confirm_form;

    /* @var DataManager */
    private $cards_table;

    /* @var FormManager */
    private $cards_edit_form;

    /* @var Action */
    private $add_card_action;

    /* @var DataManager */
    private $withdrawal_table;

    /* @var FormManager */
    private $withdrawal_form;

    /* @var Action */
    private $withdraw_action;

    public function registerActions() {
        $this->reviewActions();
        $this->createTable();
        $this->createTabs();
        $this->cardsActions();
        $this->withdrawalActions();
    }

    public function build() {
        $this->layout->push(Block::withParams('Cards Payments', $this->tabs->build()));
    }

    private function createTabs() {
        $is_admin = $this->getAdmin()->isAdmin();

        $tabs = [
            Tab::withParams('Review')->setRenderer(function () {
                return $this->review_table->build();
            }),
            Tab::withParams('Withdrawals')->setRenderer(function () {
                return [
                    Button::withParams('New Withdrawal')->onClick($this->withdraw_action),
                    $this->withdrawal_table->build(),
                ];
            })
        ];

        if ($is_admin) {
            $tabs[] = Tab::withParams('Other')->setRenderer(function () {
                return $this->table->setFilters(['tab' => 'booked'])->build();
            });
        }

        $tabs[] = Tab::withParams('Cards')->setRenderer(function () use ($is_admin) {
            $result = [];
            if ($is_admin) {
                $result[] = Button::withParams('Add new card')->onClick($this->add_card_action);
            }
            $result[] = $this->cards_table->build();
            return $result;
        });

        $this->tabs = $this->createTabsManager()->setTabs(...$tabs);
    }

    private static function loadManagedCards(UserModel $manager): array {
        static $manager_cards = null;

        if ($manager_cards !== null) {
            return $manager_cards;
        }

        return $manager_cards = BankCardModel::select(Where::equal('managed_by', $manager->id), false)
            ->map(function (BankCardModel $card) {
                return $card->id;
            });
    }

    private function reviewActions() {
        $manager = $this->getAdmin();

        $header = ['Id', 'Card', 'Operation', 'Status',  'Date', 'Actions'];
        if ($manager->isAdmin()) {
            $header = ['Id', 'Card', 'User ID', 'Amount', 'Operation', 'Status',  'Date', 'Actions'];
        }

        $confirm = $this->createAction(function (ActionRequest $request) {
            return $this->showModal(
                'Confirm payment',
                $this->confirm_form->setParams($request->getParams())->build()
            );
        });

        $reject = $this->createAction(function (ActionRequest $request) use ($manager) {
            $operation_id = $request->getParam('operation_id');

            $operation = BankCardOperationModel::get($operation_id);

            if ($operation->operation !== BankCardOperationModel::OPERATION_BOOK) {
                return $this->showErrorToast('Wrong operation');
            }

            if (!in_array($operation->status, [BankCardOperationModel::STATUS_WAIT_FOR_REVIEW, BankCardOperationModel::STATUS_WAIT_FOR_PAY], true)) {
                if ($manager->isAdmin() && $operation->status === BankCardOperationModel::STATUS_WAIT_FOR_ADMIN_REVIEW) {
                    //
                } else {
                    return $this->showErrorToast('Wrong status');
                }
            }

            Transaction::wrap(function () use ($operation) {
                $operation->status = BankCardOperationModel::STATUS_REJECTED;
                $operation->save();

                $card = BankCardModel::get($operation->card_id, false);
                $card->booked_by = null;
                $card->book_expiration = null;
                $card->save();

                $balance = BalanceModule::getBalanceOrCreate($operation->user_id, CURRENCY_RUB, BalanceModel::CATEGORY_FIAT);

                BalanceHistorySaver::make()
                    ->setToRaw(
                        UserBalanceHistoryModel::TYPE_BALANCE,
                        $balance->id,
                        $operation->user_id,
                        CURRENCY_RUB
                    )
                    ->setToAmount($operation->amount)
                    ->setOperation(UserBalanceHistoryModel::OPERATION_BANK_CARD_REFILL_REJECT)
                    ->setObjectId($operation->id)
                    ->save();
            });

            return [
                $this->showToast('Payments was rejected'),
                $this->review_table->getReloadAction($request->getParams(), $request->getValues()),
            ];
        })->setConfirm(true, 'Do you wanna to reject this payment?', true);


        $review_table_where = Where::and()
            ->set(Where::in('status', [
                BankCardOperationModel::STATUS_WAIT_FOR_REVIEW,
                BankCardOperationModel::STATUS_WAIT_FOR_ADMIN_REVIEW,
            ]))
            ->set(Where::equal('operation', BankCardOperationModel::OPERATION_BOOK));

        if (!$manager->isAdmin()) {
            $manager_cards = self::loadManagedCards($manager);
            $review_table_where->set(Where::in('card_id', $manager_cards));
        }

        $this->review_table = $this
            ->createManagedTable(BankCardOperationModel::class, $header)
            ->setDataMapper(function (ModelSet $operations) use ($manager, $confirm, $reject) {

                $cards = BankCardModel::select(Where::in(
                    'id',
                    $operations->column('card_id')
                ), false);

                return $operations->map(function (BankCardOperationModel $operation) use ($cards, $manager, $confirm, $reject) {
                    /* @var BankCardModel $card */
                    $card = $cards->getItem($operation->card_id);

                    $result = [
                        $operation->id,
                        $card->number,
                    ];
                    if ($manager->isAdmin()) {
                        $result[] = $operation->user_id;
                        $result[] = formatNum($operation->amount, 2);
                    }

                    $result[] = strtoupper($operation->operation);
                    $result[] = strtoupper($operation->status);
                    $result[] = date('d/m/Y H:i', $operation->created_at_timestamp);
                    $result[] = ActionSheet::withItems(
                        ActionSheetItem::withParams('Confirm')
                            ->onClick($confirm->use(['operation_id' => $operation->id])),
                        ActionSheetItem::withParams('Reject', ActionSheetItem::TYPE_DESTRUCTIVE)
                            ->onClick($reject->use(['operation_id' => $operation->id]))
                    );

                    return $result;
                });
            })
            ->setWhere($review_table_where);

        $this->confirm_form = $this
            ->createFormManager()
            ->setItems(function (array $params) {
                return [
                    Input::withParams('amount', 'Amount')
                ];
            })
            ->onSubmit(function (ActionRequest $request) use ($manager) {
                $amount = $request->getValue('amount', ['required', 'positive']);
                $operation_id = $request->getParam('operation_id');

                $operation = BankCardOperationModel::get($operation_id);
                if ($operation->operation !== BankCardOperationModel::OPERATION_BOOK
                    || $operation->status !== BankCardOperationModel::STATUS_WAIT_FOR_REVIEW
                    || !$amount) {
                    return $this->showErrorToast('Error occurred');
                }

                if ($operation->amount !== $amount && !$manager->isAdmin()) {
                    $operation->got_amount = $amount;
                    $operation->manager_id = $manager->id;
                    $operation->status = BankCardOperationModel::STATUS_WAIT_FOR_ADMIN_REVIEW;
                    $operation->save();

                    return $this->showErrorToast('Incorrect amount, payment was sent for admin review');
                }

                Transaction::wrap(function () use ($amount, $operation, $manager) {
                    $fee = FeeModule::getFee($amount, CURRENCY_RUB);

                    $operation->got_amount = $amount;
                    $operation->status = BankCardOperationModel::STATUS_CONFIRMED;
                    $operation->manager_id = $manager->id;
                    $operation->fee = $fee;
                    $operation->save();

                    $card = BankCardModel::get($operation->card_id, false);
                    $card->booked_by = null;
                    $card->book_expiration = null;
                    $card->incrBalance($amount);
                    $card->save();

                    $result_amount = $amount - $fee;

                    $balance = BalanceModule::getBalanceOrCreate(
                        $operation->user_id,
                        CURRENCY_RUB,
                        BalanceModel::CATEGORY_FIAT
                    );
                    $balance->incrAmount($result_amount);

                    $refill = new RefillModel();
                    $refill->currency = CURRENCY_RUB;
                    $refill->external_id = $operation->id;
                    $refill->bank_code = $card->bank;
                    $refill->provider = RefillModel::PROVIDER_CARDS;
                    $refill->user_id = $operation->user_id;
                    $refill->fee = $fee;
                    $refill->amount = $result_amount;
                    $refill->to_id = $balance->id;
                    $refill->to_type = UserBalanceHistoryModel::TYPE_BALANCE;
                    $refill->save();

                    NotificationsModule::sendRefillNotification($refill);
                });

                return [
                    $this->closeModal(),
                    $this->showToast('Payment was confirmed'),
                    $this->review_table->getReloadAction($request->getParams(), $request->getValues()),
                ];
            });
    }

    private function createTable() {
        $manager = $this->getAdmin();
        $table_where = Where::and();

        if (!$manager->isAdmin()) {
            $manager_cards = self::loadManagedCards($manager);
            $table_where->set(Where::in('card_id', $manager_cards));
        }

        $this->table = $this
            ->createManagedTable(BankCardOperationModel::class, [
                'Id',
                'User ID',
                'Card',
                'Amount',
                'Operation',
                'Status',
                'Manager Id',
                'Date'
            ])
            ->setDataMapper(function (ModelSet $operations) {
                $managers = UserModel::select(Where::in(
                    'id',
                    array_filter($operations->column('manager_id'))
                ));

                $cards = BankCardModel::select(Where::in(
                    'id',
                    $operations->column('card_id')
                ), false);

                return $operations->map(function (BankCardOperationModel $operation) use ($managers, $cards) {

                    /* @var UserModel $manager */
                    $manager = $operation->manager_id ? $managers->getItem($operation->manager_id) : null;

                    /* @var BankCardModel $card */
                    $card = $cards->getItem($operation->card_id);

                    return [
                        $operation->id,
                        $operation->user_id,
                        $card ? $card->number : 'None',
                        formatNum($operation->amount, 2) . '/' . formatNum($operation->got_amount, 2),
                        strtoupper($operation->operation),
                        strtoupper($operation->status),
                        $manager ? $manager->id . ' (' . $manager->fullName() . ')' : 'None',
                        date('d/m/Y H:i', $operation->created_at_timestamp),
                    ];
                });
            })
            ->setSearchForm(function () {
                return [
                    Input::withParams('number', 'Card number'),
                    Input::withParams('manager', 'Manager ID'),
                    Input::withParams('user_id', 'User ID'),
                    DropDown::withParams('bank', 'Bank', $this->getBanksOptions()),
                    DropDown::withParams('status', 'Status', [
                        [0, 'Status'],
                        [BankCardOperationModel::STATUS_REJECTED, 'Rejected'],
                        [BankCardOperationModel::STATUS_CONFIRMED, 'Confirmed'],
                        [BankCardOperationModel::STATUS_CANCELLED, 'Cancelled'],
                        [BankCardOperationModel::STATUS_EXPIRED, 'Expired'],
                    ])
                ];
            })
            ->setWhere($table_where)
            ->setFiltering(function (array $filters, Where $where) {

                if (isset($filters['number']) && $filters['number']) {
                    $number = intval(preg_replace('/\D/', '', $filters['number']));
                    $card = BankCardModel::first(Where::equal('number', $number));
                    $where->set(Where::equal('card_id', $card ? $card->id : 0));
                }

                if (isset($filters['manager']) && $filters['manager']) {
                    $manager = intval($filters['manager']);
                    $where->set(Where::equal('user_id', $manager));
                }

                if (isset($filters['user_id']) && $filters['user_id']) {
                    $user_id = intval($filters['user_id']);
                    $where->set(Where::equal('user_id', $user_id));
                }

                if (isset($filters['bank']) && $filters['bank']) {
                    $cards = BankCardModel::select(Where::equal('bank', $filters['bank']));
                    $where->set(Where::in('card_id', $cards->column('id')));
                }

                if (isset($filters['status']) && $filters['status']) {
                    $where->set(Where::equal('status', $filters['status']));
                }

                return $where;
            });
    }

    private function cardsActions() {
        $is_admin = $this->getAdmin()->isAdmin();

        $info_action = $this->createAction(function(ActionRequest $request) {
            $card_id = $request->getParam('card_id');

            $card = BankCardModel::get($card_id);
            $users = UserModel::select(Where::in(
                'id',
                array_filter([$card->added_by, $card->managed_by])
            ));

            $added_by = $users->getItem($card->added_by);
            $manager = $card->managed_by ? $users->getItem($card->managed_by) : null;

            $columns = [
                [
                    InfoListItem::withParams('Id', $card_id),
                    InfoListItem::withParams('Number', $card->number),
                    InfoListItem::withParams('Holder', $card->holder_name),
                    InfoListItem::withParams('Expiration Date', $card->expiration_date),
                    InfoListItem::withParams('Bank', CardsService::BANKS[$card->bank]['name']),
                ],
                [
                    InfoListItem::withParams('Active', $card->active ? 'Yes' : 'No'),
                    InfoListItem::withParams('Booked', $card->booked_by ? 'Yes' : 'No'),
                    InfoListItem::withParams('Added by', $added_by->fullName()),
                    InfoListItem::withParams('Balance', formatNum($card->balance, 2)),
                    InfoListItem::withParams('Manager', $manager ? $manager->fullName() : 'None'),
                ]
            ];

            $content = Group::withItems(
                InfoList::withItems(...$columns[0]),
                InfoList::withItems(...$columns[1])
            );

            return $this->showModal('Card Info', $content);
        });

        $remove_action = $this
            ->createAction(function (ActionRequest $request) {
                $card = BankCardModel::get($request->getParam('card_id'));
                $card->delete();
                return $this->showToast('Card was removed');
            })
            ->setRole(UserRoleModel::ADMIN_ROLE)
            ->setConfirm(true, 'Do you wanna remove this card?', true);

        $edit_action = $this
            ->createAction(function (ActionRequest $request) {
                return $this->showModal(
                    'Edit card',
                    $this->cards_edit_form->setParams($request->getParams())->build()
                );
            })
            ->setRole(UserRoleModel::ADMIN_ROLE);

        $this->cards_table = $this
            ->createManagedTable(
                BankCardModel::class,
                ['Id', 'Number', 'Balance', 'Active', 'Booked', 'Manager', 'Actions']
            )
            ->setDataMapper(function (ModelSet $cards) use ($info_action, $remove_action, $edit_action) {

                $managers = UserModel::select(Where::in(
                    'id',
                    array_filter($cards->column('managed_by'))
                ), false);

                return $cards->map(function (BankCardModel $card)
                    use ($managers, $info_action, $remove_action, $edit_action) {
                    return [
                        $card->id,
                        $card->number,
                        formatNum($card->balance, 2),
                        $card->active ? 'Yes' : 'No',
                        $card->booked_by ? 'Yes' : 'No',
                        $card->managed_by ? $managers->getItem($card->managed_by)->fullName() : 'Unassigned',
                        ActionSheet::withItems(
                            ActionSheetItem::withParams('Info')
                                ->onClick($info_action->use(['card_id' => $card->id])),
                            ActionSheetItem::withParams('Edit')
                                ->onClick($edit_action->use(['card_id' => $card->id])),
                            ActionSheetItem::withParams('Remove', ActionSheetItem::TYPE_DESTRUCTIVE)
                                ->onClick($remove_action->use(['card_id' => $card->id]))
                        )
                    ];
                });
            });

        if ($is_admin) {
            $this->cards_table
                ->setSearchForm(function () {
                    return [
                        Input::withParams('number', 'Card number'),
                        Input::withParams('manager', 'Manager ID'),
                        DropDown::withParams('bank', 'Bank', $this->getBanksOptions())
                    ];
                })
                ->setFiltering(function (array $filters, Where $where) {

                    if (isset($filters['number']) && $filters['number']) {
                        $number = intval(preg_replace('/\D/', '', $filters['number']));
                        $where->set('number', Where::OperatorLike, "%{$number}%");
                    }

                    if (isset($filters['manager']) && $filters['manager']) {
                        $manager = intval($filters['manager']);
                        $where->set(Where::equal('managed_by', $manager));
                    }

                    if (isset($filters['bank']) && $filters['bank']) {
                        $where->set(Where::equal('bank', $filters['bank']));
                    }

                    return $where;
                });
        } else {
            $this->cards_table->setWhere(
                Where::equal('managed_by', $this->getAdmin()->id)
            );
        }

        $this->cards_edit_form = $this
            ->createFormManager()
            ->setItems(function (array $params) {
                $card_id = $params['card_id'] ?? 0;

                if ($card_id > 0) {
                    $card = BankCardModel::get($card_id);
                } else {
                    $card = new BankCardModel();
                    $card->number = '';
                    $card->holder_name = '';
                    $card->code = 0;
                    $card->expiration_date = '';
                    $card->mobile_number = '';
                    $card->balance = 0;
                    $card->active = 1;
                    $card->bank = '';
                }

                $banks = array_map(function ($code) {
                    return [$code, CardsService::BANKS[$code]['name']];
                }, CardsService::getBankCodes());

                return [
                    Input::withParams('number', '', $card->number, '', 'Number'),
                    Input::withParams('holder', '', $card->holder_name, '', 'Holder'),
                    Input::withParams('code', '', $card->code, '', 'CVV'),
                    Input::withParams('expiration_date', 'e.g. 10/20', $card->expiration_date, '', 'Expiration Date'),
                    Input::withParams('manager', '', $card->managed_by ?? 0, '', 'Manager ID'),
                    Input::withParams('mobile_number', '', $card->mobile_number, '', 'Mobile Number'),
                    Input::withParams('balance', '', $card->balance, '', 'Balance'),
                    Input::withParams('booked_by', '', $card->booked_by ?? 0, '', 'Book user id'),
                    DropDown::withParams('active', 'Active', [
                        ['1', 'Yes'],
                        ['0', 'No']
                    ], $card_id > 0 ? "{$card->active}" : ''),
                    DropDown::withParams('bank', 'Bank', $banks, $card->bank)
                ];
            })
            ->onSubmit(function (ActionRequest $request) {
                $card_id = $request->getParam('card_id') ?? 0;

                /* @var string $number
                 * @var string $holder
                 * @var int $code
                 * @var string $expiration_date
                 * @var int $manager
                 * @var string $mobile_number
                 * @var float $balance
                 * @var int $booked_by
                 * @var int $active
                 * @var string $bank
                 */
                extract($request->getValues([
                    'number' => ['required', 'minLen' => 16, 'maxLen' => 16],
                    'holder' => [],
                    'code' => ['positive'],
                    'expiration_date' => [],
                    'manager' => ['positive', 'default' => 0],
                    'mobile_number' => [],
                    'balance' => ['positive', 'default' => 0],
                    'booked_by' => [],
                    'active' => ['required', 'positive'],
                    'bank' => ['required'],
                ]));

                $number = intval(preg_replace('/\D/', '', $number));

                if (!preg_match('/^([0-9]{2}+)\/([0-9]{2}+)$/', $expiration_date)) {
                    return $this->showErrorToast('Incorrect format for expiration date');
                }

                if (!$card_id) {
                    if (BankCardModel::first(Where::equal('number', $number))) {
                        return $this->showErrorToast('Card with this number already exist');
                    }
                }

                $card = $card_id > 0 ? BankCardModel::get($card_id) : new BankCardModel();
                $card->added_by = $this->getAdmin()->id;
                $card->number = $number;
                $card->holder_name = $holder;
                $card->code = $code;
                $card->expiration_date = $expiration_date;
                $card->managed_by = $manager;
                $card->mobile_number = $mobile_number;
                $card->balance = $balance;
                $card->active = $active;
                $card->bank = $bank;

                Transaction::wrap(function () use ($card, $booked_by) {

                    if ($card->booked_by && !$booked_by) {
                        $card->booked_by = null;
                        $card->book_expiration = null;

                        $operations = BankCardOperationModel::select(Where::and()
                            ->set(Where::equal('operation', BankCardOperationModel::OPERATION_BOOK))
                            ->set(Where::equal('card_id', $card->id))
                        );

                        foreach ($operations as $operation) {
                            /* @var BankCardOperationModel $operation */

                            if (in_array($operation->status, [
                                    BankCardOperationModel::STATUS_WAIT_FOR_REVIEW,
                                    BankCardOperationModel::STATUS_WAIT_FOR_ADMIN_REVIEW
                            ], true)) {
                                throw new \Exception('Card is busy, check review page');
                            } else if (in_array($operation->status, [
                                BankCardOperationModel::STATUS_WAIT_FOR_PAY,
                            ], true)) {
                                $operation->status = BankCardOperationModel::STATUS_REJECTED;
                                $operation->save();
                            }
                        }
                    } else if ($card->booked_by != $booked_by) {
                        $card->booked_by = $booked_by;
                        $card->book_expiration = time() + CardsService::BOOK_TIME;
                    }

                    $card->save();
                });

                return [
                    $this->showToast('Card was saved'),
                    $this->closeModal(),
                    $this->cards_table->getReloadAction($request->getParams(), $request->getValues()),
                ];
            });

        $this->add_card_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal(
                'Add new card',
                $this->cards_edit_form->setParams(['card_id' => 0])->build()
            );
        });
    }

    private function getBanksOptions() {
        $banks = array_map(function ($code) {
            return [$code, CardsService::BANKS[$code]['name']];
        }, CardsService::getBankCodes());

        return array_merge([
            [0, 'Bank']
        ], $banks);
    }

    private function withdrawalActions() {
        $manager = $this->getAdmin();
        $is_admin = $manager->isAdmin();

        $this->withdrawal_table = $this
            ->createManagedTable(
                BankCardOperationModel::class,
                ['ID', 'Card Number', 'Amount', 'User', 'Date']
            )
            ->setDataMapper(function (ModelSet $operations) {
                $cards = BankCardModel::select(
                    Where::in('id', $operations->column('card_id')),
                    false
                );

                $users = UserModel::select(
                    Where::in('id', $operations->column('user_id')),
                    false
                );

                return $operations->map(function (BankCardOperationModel $operation) use ($cards, $users) {

                    $card = $cards->getItem($operation->card_id);
                    /* @var BankCardModel $card */

                    $user = $users->getItem($operation->user_id);
                    /* @var UserModel $user */

                    return [
                        $operation->id,
                        $card->number,
                        formatNum($operation->amount, 2),
                        $user->id . ' (' . $user->fullName() . ')',
                        date('d/m/Y H:i', $operation->created_at_timestamp),
                    ];
                });
            });

        $table_where = Where::and()
            ->set(Where::equal('operation', BankCardOperationModel::OPERATION_WITHDRAWAL));

        if ($is_admin) {
            $this
                ->withdrawal_table->setSearchForm(function () {
                    return [
                        Input::withParams('number', 'Card number'),
                        Input::withParams('user_id', 'User ID'),
                        DropDown::withParams('bank', 'Bank', $this->getBanksOptions()),
                    ];
                })
                ->setFiltering(function (array $filters, Where $where) {
                    if (isset($filters['number']) && $filters['number']) {
                        $number = intval(preg_replace('/\D/', '', $filters['number']));
                        $card = BankCardModel::first(Where::equal('number', $number));
                        $where->set(Where::equal('card_id', $card ? $card->id : 0));
                    }

                    if (isset($filters['user_id']) && $filters['user_id']) {
                        $user_id = intval($filters['user_id']);
                        $where->set(Where::equal('user_id', $user_id));
                    }

                    if (isset($filters['bank']) && $filters['bank']) {
                        $cards = BankCardModel::select(Where::equal('bank', $filters['bank']));
                        $where->set(Where::in('card_id', $cards->column('id')));
                    }

                    return $where;
                });
        } else {
            $cards = BankCardModel::select(Where::equal('managed_by', $this->getAdmin()->id));
            $table_where->set(Where::in('card_id', $cards->column('id')));
        }

        $this->withdrawal_table->setWhere($table_where);

        $this->withdrawal_form = $this
            ->createFormManager()
            ->setItems(function (array $params) use ($is_admin, $manager) {

                $where = Where::and();
                if (!$is_admin) {
                    $where->set(Where::equal('managed_by', $manager->id));
                }
                $cards = BankCardModel::select($where)->map(function (BankCardModel $card) {
                    return [$card->id, $card->number];
                });

                return [
                    Input::withParams('amount', 'Amount'),
                    DropDown::withParams('card_id', 'Card', $cards),
                ];
            })
            ->onSubmit(function (ActionRequest $request) use ($is_admin, $manager) {

                /* @var float $amount
                 * @var int $card_id
                 */
                extract($request->getValues([
                    'amount' => ['required', 'positive'],
                    'card_id' => ['required', 'positive'],
                ]));

                $card = BankCardModel::get($card_id);
                if (!$is_admin) {
                    if ($card->manager_id != $manager->id) {
                        return $this->showToast('Access denied');
                    }
                }

                Transaction::wrap(function () use ($manager, $amount, $card_id, $card) {
                    $operation = new BankCardOperationModel();
                    $operation->user_id = $manager->id;
                    $operation->operation = BankCardOperationModel::OPERATION_WITHDRAWAL;
                    $operation->amount = $amount;
                    $operation->card_id = $card_id;
                    $operation->status = BankCardOperationModel::STATUS_CONFIRMED;
                    $operation->manager_id = $manager->id;
                    $operation->save();

                    $card->decrBalance($amount);
                });

                return [
                    $this->showToast('Withdrawal was created'),
                    $this->closeModal(),
                    $this->withdrawal_table->getReloadAction($request->getParams(), $request->getValues()),
                ];
            });

        $this->withdraw_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal('New Withdrawal', $this->withdrawal_form->build());
        });
    }
}
