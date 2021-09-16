<?php

namespace Admin\modules;

use Admin\helpers\ActionRequest;
use Admin\helpers\DataManager;
use Admin\helpers\PageContainer;
use Admin\layout\Action;
use Admin\layout\ActionSheet;
use Admin\layout\ActionSheetItem;
use Admin\layout\Block;
use Admin\layout\Button;
use Admin\layout\Clipboard;
use Admin\layout\Group;
use Admin\layout\InfoList;
use Admin\layout\InfoListItem;
use Admin\layout\Input;
use Admin\layout\NumberFormat;
use Admin\layout\Select;
use Admin\layout\TableRow;
use Admin\layout\Image;
use Admin\layout\Time;
use Admin\SubModules\ManageReservations;
use Core\Services\Storage\FileManager;
use Core\Services\Storage\FileNotFoundException;
use Db\Model\ModelSet;
use Db\Transaction;
use Db\Where;
use Models\CardModel;
use Models\ManualSessionModel;
use Models\ReservedCardModel;
use Models\UserModel;
use Models\UserPermissionModel;
use Modules\BitcoinovnetModule;

class BitcoinovnetReservations extends PageContainer {
    use ManageReservations;

    /* @var Action */
    private $start_session;
    /* @var Action */
    private $validate_action;
    /* @var DataManager */
    private $reservations_table;
    /** @var Action */
    private $stop_session;
    /** @var Action */
    private $manage_action;

    public static $permission_list = [
        UserPermissionModel::RESERVATION_BITCOINOVNET
    ];

    public function registerActions() {
        parent::registerActions();

        $this->initCardActions();
        $this->reservations_table = $this->reservationTable();
    }

    public function build() {
        $blocks = [];
        $session = ManualSessionModel::getCurrentSession();
        if ($session instanceof ManualSessionModel) {
            $user = UserModel::get($session->user_id);
            $layouts = [];
            if ($this->getAdmin()->id === $session->user_id) {
                $layouts[] = Button::withParams('Stop session')
                    ->onClick($this->stop_session->setParams(['session_id' => $session->id]));
            }
            $layouts[] = InfoList::withItems(
                InfoListItem::withParams('User', $user->email),
                InfoListItem::withParams('Session start', Time::withParams($session->session_start)),
                InfoListItem::withParams(
                    'Session end',
                    $session->session_end ? Time::withParams($session->session_end) : ''
                )
            );

            $blocks[] = Block::withParams('Session info', ...$layouts);
        } else {
            $blocks[] = Block::withParams('Actions', Button::withParams('Start session')->onClick($this->start_session));
        }

        $blocks[] = Block::withParams('Bitcoinovnet reservations', $this->reservations_table->build());

        foreach ($blocks as $block) {
            $this->layout->push($block);
        }
    }

    private function initCardActions() {
        $this->manage_action = $this->createAction(function (ActionRequest $request) {
                $reservation = ReservedCardModel::get($request->getParam('reservation_id'));
                $card = CardModel::get($reservation->card_id);

                $layouts = [];

                $layouts[] = Block::withParams('Основная информация', InfoList::withItems(
                    InfoListItem::withParams(
                        'Сумма обмена в рублях',
                        NumberFormat::withParams($reservation->amount, CURRENCY_RUB)
                    ),
                    InfoListItem::withParams(
                        'Номер карты',
                        $reservation->card_number
                    ),
                    InfoListItem::withParams(
                        'Имя владельца карты',
                        $reservation->card_owner_name
                    ),
                    InfoListItem::withParams(
                        'Карта подтверждена',
                        $reservation->validate() ? 'Да' : 'Нет'
                    ),
                    InfoListItem::withParams(
                        'Курс обмена',
                        NumberFormat::withParams($reservation->current_rate, CURRENCY_RUB)
                    ),
                    InfoListItem::withParams(
                        'Дата создания заявки',
                        NumberFormat::withParams($reservation->created_at_timestamp)
                    ),
                    InfoListItem::withParams(
                        'Карта получателя',
                        $card->name ?? ''
                    ),
                    InfoListItem::withParams(
                        'Номер карты получателя',
                        $card->card_number ?? ''
                    )
                ));

                if ($reservation->photo_name !== null) {
                    $file_manager = new FileManager(FileManager::STORAGE_LOCAL);
                    try {
                        $content = $file_manager->getStorage()->getContent('cards/' .  $reservation->photo_name);
                    } catch (FileNotFoundException $e) {
                        return [
                            $this->showErrorToast($e->getMessage())
                        ];
                    }
                    $extension = pathinfo($reservation->photo_name, PATHINFO_EXTENSION);

                    $layouts[] = Block::withParams(
                        'Card photo',
                        Image::withParams("data:image/{$extension};base64," . base64_encode($content))
                    );
                }

                $actions = [];

                if (!$reservation->validate()) {
                    $actions[] = Button::withParams('Подтвердить карту')
                        ->onClick($this->validate_action->use(['reservation_id' => $reservation->id]));
                } elseif (in_array($reservation->status, [
                    ReservedCardModel::STATUS_WAIT_FOR_PAY,
                    ReservedCardModel::STATUS_WAIT_FOR_SEND,
                ])) {
                    $actions[] = Button::withParams('Подтвердить платеж')
                        ->onClick($this->confirmed_action->use(['reservation_id' => $reservation->id]));
                    $actions[] = Button::withParams('Отклонить заявку', Button::TYPE_SECONDARY)
                        ->onClick($this->reject_action->use(['reservation_id' => $reservation->id]));
                }

                if (!empty($actions)) {
                    $layouts[] = Block::withParams('Действия', Group::withItems(...$actions));
                }

                return [
                    $this->showModal('Details', ...$layouts)
                ];
        });

        $this->start_session = $this->createAction(function(ActionRequest $request) {
            $session = ManualSessionModel::getCurrentSession();

            if ($session instanceof ManualSessionModel) {
                return [
                    $this->showErrorToast('Session already exist'),
                ];
            } else {

                $now = time();

                $session = new ManualSessionModel();
                $session->session_start = $now;
                $session->session_end = null;
                $session->user_id = (int) $this->getAdmin()->id;
                $session->save();

                return [
                    $this->openPage($this->id())
                ];
            }
        });

        $this->stop_session = $this->createAction(function(ActionRequest $request) {
            $session = ManualSessionModel::get($request->getParam('session_id'));
            $now = time();

            if ($session->user_id !== (int) $this->getAdmin()->id) {
                return [
                    $this->showErrorToast('Access denied')
                ];
            }
            if ($session->session_start <= $now && $session->session_end === null) {
                $session->session_end = time();
                $session->save();
            }
            return [
                $this->openPage($this->id())
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
        })->setConfirm(true, 'Validate request?');
    }

    private function reservationTable(): DataManager {
        $headers = ['ID', 'Request ID', 'Card', 'Status', 'Customer card number', 'Amount', 'Rate', 'Total', 'Validated', 'Txid', 'Date', 'Actions'];

        $context = $this;

        return $this->createManagedTable(
            ReservedCardModel::class,
            $headers
        )
            ->setDataMapper(function (ModelSet $reservations) {
                $cards = CardModel::select(Where::in('id', $reservations->column('card_id')));
                return $reservations->map(function (ReservedCardModel $reservation) use ($cards) {
                    $card = $cards->getItem($reservation->card_id);
                    /** @var CardModel $card */
                    $row = TableRow::withParams(
                        $reservation->id,
                        $reservation->getRequestId(),
                        $card ? "{$card->name} ($card->card_number)" : $reservation->card_id,
                        $reservation->status,
                        $reservation->card_number,
                        NumberFormat::withParams($reservation->amount, CURRENCY_RUB),
                        NumberFormat::withParams($reservation->current_rate, CURRENCY_RUB),
                        NumberFormat::withParams($reservation->amount / $reservation->current_rate, CURRENCY_BTC),
                        $reservation->validate ? 'Yes' : 'No',
                        $reservation->txid ? Clipboard::withParams($reservation->txid, 10) : 'NULL',
                        Time::withParams($reservation->created_at_timestamp),
                        ActionSheet::withItems(
                            ActionSheetItem::withParams('Manage')
                                ->onClick($this->manage_action->use(['reservation_id' => $reservation->id]))
                        )
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
                    Input::withParams('card_info', 'Card number or name'),
                    Select::withParams('status', 'Select status', array_merge(
                        ['all' => 'all'],
                        ReservedCardModel::$statuses
                    )),
                    Input::withParams('date_from', 'Date from d/m/Y'),
                    Input::withParams('date_to', 'Date to d/m/Y'),
                ];
            })
            ->setFiltering(function (array $filters, Where $where) use ($context) {
                $now = time();

                $session = ManualSessionModel::first(Where::and()
                    ->set(Where::equal('user_id', $context->getAdmin()->id))
                    ->set('session_start', Where::OperatorLower, $now)
                    ->set('session_end', Where::OperatorIs, NULL)
                );

                if (is_null($session)) {
                    $where->set('id', Where::OperatorLower, 0);
                    return $where;
                }

                $where->set(Where::equal('session_id', $session->id));

                if (isset($filters['status']) && $filters['status'] !== 'all') {
                    $where->set(Where::equal('status', $filters['status']));
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
}
