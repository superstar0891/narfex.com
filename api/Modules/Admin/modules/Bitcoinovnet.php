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
use Admin\layout\Checkbox;
use Admin\layout\Clipboard;
use Admin\layout\DropDown;
use Admin\layout\Group;
use Admin\layout\InfoList;
use Admin\layout\InfoListItem;
use Admin\layout\Input;
use Admin\layout\NumberFormat;
use Admin\layout\Select;
use Admin\layout\Tab;
use Admin\layout\TableRow;
use Admin\layout\Image;
use Admin\layout\Time;
use Admin\SubModules\ManageReservations;
use Core\App;
use Core\Blockchain\Factory;
use Core\Services\Merchant\FastExchangeService;
use Core\Services\Qiwi\QiwiService;
use Core\Services\Storage\FileManager;
use Core\Services\Storage\FileNotFoundException;
use Core\Services\Telegram\SendService;
use Db\Model\ModelSet;
use Db\Transaction;
use Db\Where;
use Exceptions\InvalidKeyException;
use Exceptions\WithdrawalRequests\EnoughMoneyTransferException;
use Google\Cloud\Storage\StorageClient;
use Models\CardModel;
use Models\MerchantPayments;
use Models\ReservedCardModel;
use Models\TransactionModel;
use Models\UserModel;
use Modules\BitcoinovnetModule;
use Modules\WalletModule;

class Bitcoinovnet extends PageContainer {
    use ManageReservations;

    /* @var Action */
    private $add_action;
    /* @var Action */
    private $edit_action;
    /* @var Action */
    private $see_card_payments_action;
    /* @var Action */
    private $update_balance_action;
    /* @var Action */
    private $update_hook_action;
    /* @var Action */
    private $cancel_request_action;
    /* @var Action */
    private $show_payment_info_action;
    /* @var Action */
    private $moderation_action;
    /* @var Action */
    private $send_btc_action;
    /* @var Action */
    private $refund_action;
    /* @var Action */
    private $validate_action;
    /* @var Action */
    private $see_photo_action;
    /* @var Action */
    private $unbook_action;
    /* @var Action */
    private $info_action;
    /* @var Action */
    private $show_limits_action;
    /* @var TabsManager */
    private $tabs;
    /* @var DataManager */
    private $cards_table;
    /* @var DataManager */
    private $reservations_table;
    /* @var DataManager */
    private $all_payments_table;
    /* @var DataManager */
    private $payments_table;
    /* @var FormManager */
    private $add_card_form;
    /* @var FormManager */
    private $refund_form;
    /* @var FormManager */
    private $send_btc_form;

    public function registerActions() {
        parent::registerActions();

        $this->initCardActions();

        $this->tabs = $this->tabs();
        $this->add_card_form = $this->addCardForm();
        $this->refund_form = $this->refundForm();
        $this->send_btc_form = $this->sendBtcForm();
        $this->cards_table = $this->cardTable();
        $this->reservations_table = $this->reservationTable();
        $this->payments_table = $this->paymentsTable();
        $this->all_payments_table = $this->paymentsTable();

        $this->confirmed_action->needGa();
        $this->reject_action->needGa();
    }

    public function build() {
        $this->layout->push(Block::withParams('Bitcoinovnet cards', $this->tabs->build()));
    }

    private function initCardActions() {
        $this->add_action = $this->createAction(function(ActionRequest $request) {
            return $this->showModal('Add card', $this->add_card_form->build());
        });

        $this->edit_action = $this->createAction(function(ActionRequest $request) {
            return $this->showModal('Edit card', $this->add_card_form->setParams([
                'card_id' => $request->getParam('card_id')
            ])->build());
        });

        $this->update_hook_action = $this->createAction(function(ActionRequest $request) {
            try {
                $card = CardModel::get($request->getParam('card_id'));
                $card->hook_id = FastExchangeService::changeHook($card);
                $card->setSecretKeyHash(FastExchangeService::getSecretKey($card));
                $card->save();
            } catch (\Exception $e) {
                return $this->showErrorToast($e->getMessage());
            }

            return [
                $this->cards_table->getReloadAction($request->getParams(), []),
                $this->showToast('Hook updated')
            ];
        })->setConfirm(true, 'I know what I\'m doing');

        $this->update_balance_action = $this->createAction(function(ActionRequest $request) {
            try {
                Transaction::wrap(function () use ($request) {
                    $card = CardModel::get($request->getParam('card_id'));
                    $balance = FastExchangeService::getCardBalance($card);
                    $card->balance = $balance;
                    $card->save();

                    FastExchangeService::calcAvailableAmount($card);
                });
            } catch (\Exception $e) {
                return $this->showErrorToast($e->getMessage());
            }

            return [
                $this->cards_table->getReloadAction($request->getParams(), []),
                $this->showToast('Balance updated')
            ];
        });

        $this->see_card_payments_action = $this->createAction(function(ActionRequest $request) {
            return [
                $this->showModal(
                    'Payments',
                    $this->payments_table
                        ->setFilters(['card_id' => $request->getParam('card_id')])
                        ->build()
                )
            ];
        });

        $this->cancel_request_action = $this->createAction(function(ActionRequest $request) {
            try {
                $reservation = ReservedCardModel::get($request->getParam('reservation_id'));
                $reservation->cancelled();
                
                $card = CardModel::get($reservation->card_id);
                if ($card->booked == $reservation->id) {
                    $card->unbook();
                }
                return [
                    $this->showToast('Request cancelled'),
                    $this->reservations_table->getReloadAction($request->getParams(), $request->getValues())
                ];
            } catch (\Exception $e) {
                return [
                    $this->showErrorToast($e->getMessage())
                ];
            }
        })->setConfirm(true, 'Want to cancelled reservation?')->needGa();

        $this->moderation_action = $this->createAction(function(ActionRequest $request) {
            try {
                ReservedCardModel::get($request->getParam('reservation_id'))->moderation();
                return [
                    $this->showToast('Request sent for moderation'),
                    $this->reservations_table->getReloadAction($request->getParams(), $request->getValues())
                ];
            } catch (\Exception $e) {
                return [
                    $this->showErrorToast($e->getMessage())
                ];
            }
        })->setConfirm(true, 'Want to send for moderation?')->needGa();

        $this->show_payment_info_action = $this->createAction(function(ActionRequest $request) {
            $payment = MerchantPayments::first(
                Where::equal('reservation_id', $request->getParam('reservation_id'))
            );

            if (is_null($payment)) {
                return [
                    $this->showErrorToast('Payment not found')
                ];
            }

            $values = [];
            foreach (MerchantPayments::getFields() as $field => $_) {
                if ($field == 'extra') {
                    $extra = json_decode($payment->extra, true);
                    if (empty($extra)) {
                        continue;
                    }
                    foreach ($extra as $key => $value) {
                        if (is_array($value)) {
                            $value = json_encode($value);
                        }
                        $values[$key] = $value;
                    }
                }
                $values[$field] = $payment->$field;
            }

            $length = count($values);

            $info_lists = [];
            $info_list_items = [];
            foreach ($values as $field => $value) {
                $info_list_items[] = InfoListItem::withParams($field, $value);
            }

            $info_lists[] = InfoList::withItems(...array_slice($info_list_items, 0, ceil($length/2)));
            $info_lists[] = InfoList::withItems(...array_slice($info_list_items, ceil($length/2), $length));

            return [
                $this->showModal('Payment info', Group::withItems(...$info_lists))
            ];
        });

        $this->refund_action = $this->createAction(function(ActionRequest $request) {
            $reservation = ReservedCardModel::get($request->getParam('reservation_id'));
            return [
                $this->showModal(
                    'Refund rub',
                    InfoList::withItems(
                        InfoListItem::withParams('Card number', $reservation->card_number)
                    ),
                    $this->refund_form->setParams([
                        'reservation_id' => $reservation->id,
                        'amount' => $reservation->amount,
                    ])->build()
                )
            ];
        });

        $this->send_btc_action = $this->createAction(function(ActionRequest $request) {
            $reservation = ReservedCardModel::get($request->getParam('reservation_id'));

            return [
                $this->showModal(
                    'Send btc',
                    InfoList::withItems(
                        InfoListItem::withParams('Wallet address', $reservation->wallet_address),
                        InfoListItem::withParams('Amount in rub', $reservation->amount),
                        InfoListItem::withParams('Got amount', $reservation->got_amount),
                        InfoListItem::withParams('Current rate', $reservation->current_rate)
                    ),
                    $this->send_btc_form->setParams([
                        'reservation_id' => $reservation->id,
                        'amount' => ($reservation->got_amount ?? 0) / $reservation->current_rate,
                    ])->build()
                )
            ];
        });

        $this->validate_action = $this->createAction(function(ActionRequest $request) {
            Transaction::wrap(function () use ($request) {
                $reservation = ReservedCardModel::get($request->getParam('reservation_id'));
                $reservation->validate();

                if ($reservation->user_id !== null) {
                    $card = BitcoinovnetModule::getOrCreateUserCard(UserModel::get($reservation->user_id), $reservation);
                    if (!$card->isValidated()) {
                        $card->validate();
                    }
                }
            });

            return [
                $this->showToast('Request validated'),
                $this->reservations_table->getReloadAction([], []),
            ];
        })->setConfirm(true, 'Validate request?')->needGa();

        $this->see_photo_action = $this->createAction(function(ActionRequest $request) {
            $reservation = ReservedCardModel::get($request->getParam('reservation_id'));

            $file_manager = new FileManager(FileManager::STORAGE_LOCAL);
            try {
                $content = $file_manager->getStorage()->getContent('cards/' .  $reservation->photo_name);
            } catch (FileNotFoundException $e) {
                return [
                    $this->showErrorToast($e->getMessage())
                ];
            }
            $extension = pathinfo($reservation->photo_name, PATHINFO_EXTENSION);

            return [
                $this->showModal(
                    'Photo',
                    Image::withParams("data:image/{$extension};base64," . base64_encode($content))
                )
            ];
        });

        $this->info_action = $this->createAction(function(ActionRequest $request) {
            $reservation = ReservedCardModel::get($request->getParam('reservation_id'));
            return [
                $this->showModal('Info', Group::withItems(
                    InfoList::withItems(
                        InfoListItem::withParams('ID', $reservation->id),
                        InfoListItem::withParams('Request id', $reservation->request_id),
                        InfoListItem::withParams('Card id', $reservation->card_id),
                        InfoListItem::withParams('User id', $reservation->user_id ?: 'NULL'),
                        InfoListItem::withParams('Txid', $reservation->txid ?: ''),
                        InfoListItem::withParams('Operation', $reservation->operation),
                        InfoListItem::withParams('Amount', NumberFormat::withParams($reservation->amount, CURRENCY_RUB)),
                        InfoListItem::withParams('Got amount', NumberFormat::withParams($reservation->got_amount ?: 0, CURRENCY_RUB)),
                        InfoListItem::withParams('Walleet address', $reservation->wallet_address),
                        InfoListItem::withParams('Card number', $reservation->card_number),
                        InfoListItem::withParams('Total', NumberFormat::withParams($reservation->amount / $reservation->current_rate, CURRENCY_BTC)),
                        InfoListItem::withParams('Profit id', $reservation->profit_id ?: 'NULL')
                    ),
                    InfoList::withItems(
                        InfoListItem::withParams('Session id', $reservation->session_id ?: 'NULL'),
                        InfoListItem::withParams('Validate', $reservation->validate ? 'Yes' : 'No'),
                        InfoListItem::withParams('Card owner name', $reservation->card_owner_name),
                        InfoListItem::withParams('Email', $reservation->email),
                        InfoListItem::withParams('Promo code', $reservation->promo_code ?: ''),
                        InfoListItem::withParams('Status', $reservation->status),
                        InfoListItem::withParams('Hash', $reservation->hash),
                        InfoListItem::withParams('Fee', NumberFormat::withParams($reservation->fee ?: 0, CURRENCY_RUB)),
                        InfoListItem::withParams('Init rate', $reservation->initial_rate),
                        InfoListItem::withParams('Current rate', $reservation->current_rate),
                        InfoListItem::withParams('Rate update at', Time::withParams($reservation->rate_update_at_timestamp)),
                        InfoListItem::withParams('Created at', Time::withParams($reservation->created_at_timestamp))
                    )
                ))
            ];
        });

        $this->unbook_action = $this->createAction(function(ActionRequest $request) {
            $card = CardModel::get($request->getParam('card_id'));
            if ($card->isBooked()) {
                try {
                    ReservedCardModel::get($card->booked)->reject();
                } catch (\Exception $e) {
                    //
                }
                $card->unbook();
            }

            return [
                $this->showToast('Card unbooked')
            ];
        })->setConfirm(true, 'Unbook card?')->needGa();

        $this->show_limits_action = $this->createAction(function(ActionRequest $request) {
            $card = CardModel::get($request->getParam('card_id'));

            $types = [
                'REFILL' => 'Максимальный допустимый остаток на счёте',
                'TURNOVER' => 'Оборот в месяц',
                'PAYMENTS_P2P' => 'Переводы на другие кошельки в месяц',
                'PAYMENTS_PROVIDER_INTERNATIONALS' => 'Платежи в адрес иностранных компаний в месяц',
                'PAYMENTS_PROVIDER_PAYOUT' => 'Переводы на банковские счета и карты, кошельки других систем',
                'WITHDRAW_CASH' => 'Снятие наличных в месяц',
            ];

            $limits = FastExchangeService::getCardLimits($card, array_keys($types));
            $limits = array_reverse($limits);
            $layouts = [];
            foreach ($limits as $limit) {
                $interval = $limit['interval'];
                $layouts[] = Group::withItems(Block::withParams(
                    $types[$limit['type']],
                    InfoList::withItems(
                        InfoListItem::withParams('Rest', NumberFormat::withParams($limit['rest'], CURRENCY_RUB)),
                        InfoListItem::withParams('Max', NumberFormat::withParams($limit['max'], CURRENCY_RUB)),
                        InfoListItem::withParams('Spent', NumberFormat::withParams($limit['spent'], CURRENCY_RUB)),
                        InfoListItem::withParams('Date from', Time::withParams((new \DateTime($interval['dateFrom']))->getTimestamp())),
                        InfoListItem::withParams('Date from', Time::withParams((new \DateTime($interval['dateTill']))->getTimestamp()))
                    )
                ));
            }

            return [
                $this->showModal('Card limits', ...$layouts)
            ];
        });
    }

    private function sendBtcForm(): FormManager {
        return $this->createFormManager()
            ->setItems(function ($params) {
                $amount = round(array_get_val($params, 'amount', ''), 8);
                return [
                    Input::withParams('amount', 'Send amount', $amount),
                ];
            })
            ->onSubmit(function (ActionRequest $request) {
                $reservation = ReservedCardModel::get($request->getParam('reservation_id'));
                $amount = $request->getValue('amount', ['required', 'double']);
                $amount = round($amount, 8);

                if (App::isProduction()) {
                    $instance = Factory::getInstance(CURRENCY_BTC);
                    $balance = $instance->getWalletInfo()['balance'];
                } else {
                    $instance = null;
                    $balance = 100000000;
                }

                if ($balance < $amount) {
                    throw new EnoughMoneyTransferException('not enough money to transfer founds');
                }

                $reservation->status = ReservedCardModel::STATUS_BLOCKCHAIN_START_SEND;
                $reservation->save();

                Transaction::wrap(function () use ($reservation, $instance, $amount) {
                    if (App::isProduction()) {
                        $txid = $instance->sendToAddress(
                            null,
                            $reservation->wallet_address,
                            $amount,
                            null
                        );

                        BitcoinovnetModule::updateBitcoinovnetBalance();
                        BitcoinovnetModule::addLong($amount);
                    } else {
                        $settings = settings();
                        $settings->decrBitcoinovnetBtcBalance($amount);

                        $txid = bin2hex(openssl_random_pseudo_bytes(64));
                    }

                    WalletModule::createTransaction('send', CURRENCY_BTC, $amount, [
                        'status' => TransactionModel::STATUS_UNCONFIRMED,
                        'txid' => $txid,
                        'to' => $reservation->wallet_address,
                        'platform' => PLATFORM_BITCOINOVNET,
                    ]);

                    $reservation->txid = $txid;
                    $reservation->confirm();

                    $card = CardModel::get($reservation->card_id);
                    if ($card->booked == $reservation->id) {
                        $card->unbook();
                    }

                    $telegram = new SendService(SendService::CHAT_BITCOINOVNET);
                    $telegram->sendMessageSafety(
                        '#reservation_confirmed'
                        . PHP_EOL .
                        "Reservation: {$reservation->id}, Amount: {$reservation->amount}, Rate: {$reservation->current_rate}"
                    );
                });

                return [
                    $this->closeModal(),
                    $this->showToast('Successful send btc')
                ];
            }, true);
    }

    private function refundForm(): FormManager {
        return $this->createFormManager()
            ->setItems(function ($params) {
                $amount = array_get_val($params, 'amount', '');
                return [
                    Input::withParams('amount', 'Refund amount', $amount),
                ];
            })
            ->onSubmit(function (ActionRequest $request) {
                $reservation = ReservedCardModel::get($request->getParam('reservation_id'));
                $card = CardModel::get($reservation->card_id);

                $qiwi = new QiwiService($card->oauth_token);
                $provider_info = $qiwi->getProviderId($reservation->card_number);
                if (false == ($provider_info['code']['value'] == 0 || !in_array($provider_info['message'], ['1960', '21012']))) {
                    return [
                        $this->showErrorToast('Card provider incorrect')
                    ];
                }
                $qiwi->transfer($provider_info['message'], $reservation->card_number, $request->getParam('amount'));

                Transaction::wrap(function() use ($reservation, $card) {
                    $reservation->reject();
                    if ($card->booked == $reservation->id) {
                        $card->unbook();
                    }
                });

                return [
                    $this->closeModal(),
                    $this->showToast('Successful refund')
                ];
            }, true);
    }

    private function tabs(): TabsManager {
        return $this->createTabsManager()
            ->setTabs(
                Tab::withParams('Requests')->setRenderer(function () {
                    return [
                        $this->reservations_table->build()
                    ];
                }),
                Tab::withParams('Cards')->setRenderer(function () {
                    return [
                        Button::withParams('Add card')->onClick($this->add_action),
                        $this->cards_table->build()
                    ];
                }),
                Tab::withParams('Payments')->setRenderer(function () {
                    return [
                        $this->all_payments_table->build()
                    ];
                })
            );
    }

    private function addCardForm(): FormManager {
        return $this->createFormManager()
            ->setItems(function ($params) {
                if (isset($params['card_id'])) {
                    $card = CardModel::get($params['card_id']);
                } else {
                    $card = new CardModel();
                    $card->oauth_token = '';
                    $card->name = '';
                    $card->wallet_number = '';
                    $card->card_number = '';
                    $card->active = 0;
                    $card->merchant = CardModel::MERCHANT_TINKOFF;
                    $card->balance = 0;
                    $card->available_amount = 0;
                }

                return [
                    DropDown::withParams(
                        'merchant',
                        'Merchant',
                        [
                            [CardModel::MERCHANT_TINKOFF, CardModel::MERCHANT_TINKOFF],
                            [CardModel::MERCHANT_QIWI, CardModel::MERCHANT_QIWI],
                        ],
                        $card->merchant
                    ),
                    Input::withParams(
                        'name',
                        'Name',
                        $card->name ?? '',
                        '',
                        'Name'
                    ),
                    Input::withParams(
                        'oauth_token',
                        'Oauth token',
                        $card->oauth_token,
                        '',
                        'Oauth token'
                    ),
                    Input::withParams(
                        'wallet_number',
                        'Wallet number',
                        $card->wallet_number,
                        '',
                        'Wallet number'
                    ),
                    Input::withParams(
                        'card_number',
                        'Card number',
                        $card->card_number,
                        '',
                        'Card number'
                    ),
                    Input::withParams(
                        'balance',
                        'Balance',
                        $card->balance,
                        '',
                        'Balance'
                    ),
                    Input::withParams(
                        'available_amount',
                        'Available amount',
                        $card->available_amount,
                        '',
                        'Available amount'
                    ),
                    Checkbox::withParams('active', 'Active', $card->active),
                ];
            })
            ->onSubmit(function (ActionRequest $request) {
                $merchant = $request->getValue('merchant', ['required']);

                $filters = [
                    'name' => [],
                    'balance' => [],
                    'available_amount' => [],
                    'wallet_number' => [],
                    'card_number' => ['required', 'minLen' => 16, 'maxLen' => 20],
                    'oauth_token' => [],
                    'active' => [],
                ];

                if ($merchant === CardModel::MERCHANT_QIWI) {
                    $filters = [
                        'name' => [],
                        'balance' => [],
                        'available_amount' => [],
                        'wallet_number' => ['required'],
                        'card_number' => ['required', 'minLen' => 16, 'maxLen' => 20],
                        'oauth_token' => ['required'],
                        'active' => [],
                    ];
                }

                /**
                 * @var string|null $name
                 * @var float $balance
                 * @var float $available_amount
                 * @var string|null $wallet_number
                 * @var string|null $card_number
                 * @var string|null $oauth_token
                 * @var bool|null $active
                 */
                extract($request->getValues($filters));

                try {
                    $card = CardModel::get($request->getParam('card_id'));
                } catch (InvalidKeyException $e) {
                    $card = new CardModel();
                }
                $card->added_by = $this->getAdmin()->id;
                $card->merchant = $merchant;
                $card->name = $name;
                $card->oauth_token = $oauth_token;
                $card->card_number = $card_number;
                $card->wallet_number = $wallet_number;
                $card->active = $active ?? 0;
                $card->balance = $balance;
                $card->available_amount = $available_amount;

                if (is_null($card->id)) {
                    switch ($card->merchant) {
                        case CardModel::MERCHANT_QIWI:
                            try {
                                $card->hook_id = FastExchangeService::registerHook($card);
                            } catch (\Exception $e) {
                                $card->hook_id = FastExchangeService::changeHook($card);
                            }
                            $card->setSecretKeyHash(FastExchangeService::getSecretKey($card));
                            $card->balance = FastExchangeService::getCardBalance($card);
                            break;
                    }
                }
                $card->save();
                return [$this->closeModal(), $this->cards_table->getReloadAction($request->getParams(), []),];
            });
    }

    private function cardTable(): DataManager {
        $headers = ['ID', 'Balance', 'Available amount', 'Name', 'Merchant', 'Card number', 'Added by', 'Booked By', 'Expiration date', 'Actions'];

        return $this->createManagedTable(CardModel::class, $headers)
            ->setDataMapper(function (ModelSet $cards) {
                $users = UserModel::select(Where::in('id', $cards->column('added_by')));
                return $cards->map(function (CardModel $card) use ($users) {
                    $user = $users->getItem($card->added_by);
                    /** @var UserModel|null $user */

                    $actions = [
                        ActionSheetItem::withParams('Show payments')
                            ->onClick($this->see_card_payments_action->use(['card_id' => $card->id])),
                        ActionSheetItem::withParams('Update balance')
                            ->onClick($this->update_balance_action->use(['card_id' => $card->id])),
                        ActionSheetItem::withParams('Update hook')
                            ->onClick($this->update_hook_action->use(['card_id' => $card->id])),
                        ActionSheetItem::withParams('Edit')
                            ->onClick($this->edit_action->use(['card_id' => $card->id])),
                        ActionSheetItem::withParams('Show card limits')
                            ->onClick($this->show_limits_action->use(['card_id' => $card->id]))
                    ];

                    if ($card->isBooked()) {
                        $actions[] = ActionSheetItem::withParams('Unbook')
                            ->onClick($this->unbook_action->use(['card_id' => $card->id]));
                    }

                    $row = TableRow::withParams(
                        $card->id,
                        NumberFormat::withParams($card->balance, CURRENCY_RUB),
                        NumberFormat::withParams($card->available_amount, CURRENCY_RUB),
                        $card->name ?? '',
                        $card->merchant,
                        $card->card_number,
                        $user ? "{$user->email} ({$user->id})" : 'NULL',
                        $card->booked ?? 'NULL',
                        $card->book_expiration ? Time::withParams($card->book_expiration) : 'NULL',
                        ActionSheet::withItems(...$actions)
                    );

                    if (!$card->active) {
                        $row->danger();
                    }
                    return $row;
                });
            })
            ->setSearchForm(function () {
                return [
                    Input::withParams('card_number', 'Enter user login/name/email'),
                ];
            })
            ->setFiltering(function (array $filters, Where $where) {
                if (isset($filters['card_number']) && $filters['card_number']) {
                    $where->set('card_number', Where::OperatorLike, '%' . $filters['card_number'] . '%');
                }
                return $where;
            });
    }

    private function reservationTable(): DataManager {
        $headers = ['ID', 'Request ID', 'Card id', 'Status', 'Card number', 'Amount', 'Rate', 'Total', 'Validate', 'Txid', 'Date', 'Actions'];

        return $this->createManagedTable(ReservedCardModel::class, $headers)
            ->setDataMapper(function (ModelSet $reservations) {

                $payments = MerchantPayments::select(Where::in('reservation_id', $reservations->column('id')));

                return $reservations->map(function (ReservedCardModel $reservation) use ($payments)  {
                    /** @var UserModel|null $user */

                    $actions = [
                        ActionSheetItem::withParams('Reservation info')
                            ->onClick($this->info_action->use(['reservation_id' => $reservation->id]))
                    ];

                    $count = $payments->filter(function (MerchantPayments $payment) use ($reservation) {
                        return $payment->reservation_id == $reservation->id;
                    })->count();

                    if ($count > 0) {
                        $actions[] = ActionSheetItem::withParams('Payment info')
                            ->onClick($this->show_payment_info_action->use(['reservation_id' => $reservation->id]));
                    }

                    switch ($reservation->status) {
                        case ReservedCardModel::STATUS_WAIT_FOR_SEND:
                        case ReservedCardModel::STATUS_WAIT_FOR_PAY:
                            $actions[] = ActionSheetItem::withParams('Cancel')
                                ->onClick($this->cancel_request_action->use(['reservation_id' => $reservation->id]));
                            $actions[] = ActionSheetItem::withParams('Moderation')
                                ->onClick($this->moderation_action->use(['reservation_id' => $reservation->id]));

                            if (!$reservation->validate) {
                                $actions[] = ActionSheetItem::withParams('Verify card number')
                                    ->onClick($this->validate_action->use(['reservation_id' => $reservation->id]));
                            }
                            break;
                        case ReservedCardModel::STATUS_MODERATION:
                        case ReservedCardModel::STATUS_WRONG_AMOUNT:
                        case ReservedCardModel::STATUS_BLOCKCHAIN_START_SEND:
                            $actions[] = ActionSheetItem::withParams('Approve')
                                ->onClick($this->confirmed_action->use(['reservation_id' => $reservation->id]));
                            $actions[] = ActionSheetItem::withParams('Reject')
                                ->onClick($this->reject_action->use(['reservation_id' => $reservation->id]));
                            $actions[] = ActionSheetItem::withParams('Send btc')
                                    ->onClick($this->send_btc_action->use(['reservation_id' => $reservation->id]));
                            $actions[] = ActionSheetItem::withParams('Refund rub')
                                    ->onClick($this->refund_action->use(['reservation_id' => $reservation->id]));
                            break;
                    }

                    if (!is_null($reservation->photo_name)) {
                        $actions[] = ActionSheetItem::withParams('See photo')
                            ->onClick($this->see_photo_action->use(['reservation_id' => $reservation->id]));
                    }

                    $row = TableRow::withParams(
                        $reservation->id,
                        $reservation->getRequestId(),
                        $reservation->card_id,
                        $reservation->status,
                        $reservation->card_number,
                        NumberFormat::withParams($reservation->amount, CURRENCY_RUB),
                        NumberFormat::withParams($reservation->current_rate, CURRENCY_RUB),
                        NumberFormat::withParams($reservation->amount / $reservation->current_rate, CURRENCY_BTC),
                        $reservation->validate ? 'Yes' : 'No',
                        $reservation->txid ? Clipboard::withParams($reservation->txid, 10) : 'NULL',
                        Time::withParams($reservation->created_at_timestamp),
                        empty($actions) ? '' : ActionSheet::withItems(...$actions)
                    );

                    if (in_array($reservation->status, [
                        ReservedCardModel::STATUS_MODERATION,
                        ReservedCardModel::STATUS_WRONG_AMOUNT
                    ])) {
                        $row->danger();
                    }

                    if (in_array($reservation->status, [
                        ReservedCardModel::STATUS_WAIT_FOR_PAY,
                        ReservedCardModel::STATUS_WAIT_FOR_SEND
                    ])) {
                        $row->accent();
                    }
                    return $row;
                });
            })
            ->setSearchForm(function () {
                return [
                    Input::withParams('request_id', 'Request id'),
                    Input::withParams('card_id', 'Reserved card id'),
                    Input::withParams('card_info', 'Card number or name'),
                    Select::withParams('status', 'Select status', array_merge(
                        ['all' => 'all'],
                        ReservedCardModel::$statuses
                    )),
                    Input::withParams('date_from', 'Date from d/m/Y'),
                    Input::withParams('date_to', 'Date to d/m/Y'),
                ];
            })
            ->setFiltering(function (array $filters, Where $where) {
                if (isset($filters['status']) && $filters['status'] !== 'all') {
                    $where->set(Where::equal('status', $filters['status']));
                }
                if (isset($filters['card_id']) && (int) $filters['card_id']) {
                    $where->set(Where::equal('card_id', $filters['card_id']));
                }
                if (isset($filters['request_id']) && (int) $filters['request_id']) {
                    $where->set(Where::equal('request_id', $filters['request_id']));
                }
                if (isset($filters['card_info']) && $filters['card_info']) {
                    $info = $filters['card_info'];
                    $where->set(Where::or()
                        ->set('card_number', Where::OperatorLike, "%{$info}%")
                        ->set('card_owner_name', Where::OperatorLike, "%{$info}%"));
                }
                $where = DataManager::applyDateFilters($filters, $where);

                return $where;
            });
    }

    private function paymentsTable(): DataManager {
        return $this->createManagedTable(MerchantPayments::class, [
                'ID', 'Merchant', 'Account', 'Type', 'Status', 'Txid', 'Marchant Txid', 'Amount', 'Reservation id', 'Card id', 'Date'
            ])
            ->setDataMapper(function (ModelSet $payments) {
                return $payments->map(function (MerchantPayments $payment) {
                    return [
                        $payment->id,
                        $payment->merchant,
                        $payment->account ?? 'NULL',
                        $payment->type,
                        $payment->status,
                        $payment->blockchain_txid ? Clipboard::withParams($payment->blockchain_txid, 16) : 'NULL',
                        $payment->merchant_txid ?? 'NULL',
                        NumberFormat::withParams($payment->amount, $payment->currency),
                        $payment->reservation_id ?? 'NULL',
                        $payment->card_id ?? 'NULL',
                        Time::withParams($payment->created_at_timestamp)
                    ];
                });
            })
            ->setSearchForm(function () {
                return [
                    Input::withParams('merchant_txid', 'Merchant txid'),
                    Input::withParams('blockchain_txid', 'Blockchain txid'),
                ];
            })
            ->setFiltering(function (array $filters, Where $where) {
                if (isset($filters['merchant_txid']) && $filters['merchant_txid']) {
                    $where->set(Where::equal('merchant_txid', $filters['merchant_txid']));
                }
                if (isset($filters['blockchain_txid']) && $filters['blockchain_txid']) {
                    $where->set(Where::equal('blockchain_txid', $filters['blockchain_txid']));
                }
                if (isset($filters['card_id']) && $filters['card_id']) {
                    $where->set(Where::equal('card_id', $filters['card_id']));
                }
                return $where;
            });
    }
}
