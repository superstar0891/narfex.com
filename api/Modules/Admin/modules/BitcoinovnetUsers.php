<?php

namespace Admin\modules;

use Admin\helpers\ActionRequest;
use Admin\helpers\DataManager;
use Admin\helpers\PageContainer;
use Admin\layout\Action;
use Admin\layout\ActionSheet;
use Admin\layout\ActionSheetItem;
use Admin\layout\Block;
use Admin\layout\Image;
use Admin\layout\Input;
use Admin\layout\Time;
use Db\Model\ModelSet;
use Db\Where;
use Google\Cloud\Storage\StorageClient;
use Models\BitcoinovnetUserCardModel;
use Models\UserModel;

class BitcoinovnetUsers extends PageContainer {
    /** @var DataManager */
    private $users_table;
    /** @var DataManager */
    private $cards_table;
    /** @var Action */
    private $see_cards_action;
    /** @var Action */
    private $validate_card_action;
    /** @var Action */
    private $unvalidate_card_action;
    /** @var Action */
    private $delete_card_action;
    /** @var Action */
    private $see_photo_action;

    public function registerActions() {
        $this->users_table = $this->createManagedTable(
            UserModel::class,
            ['ID', 'Email', 'Join date', 'Actions'],
            Where::and()->set(Where::equal('platform', PLATFORM_BITCOINOVNET))
        )
            ->setDataMapper(function (ModelSet $users) {
                return $users->map(function (UserModel $user) {
                    return [
                        $user->id,
                        $user->email,
                        Time::withParams($user->created_at_timestamp),
                        ActionSheet::withItems(
                            ActionSheetItem::withParams('Show cards')
                                ->onClick($this->see_cards_action->use(['user_id' => $user->id]))
                        )
                    ];
                });
            })
            ->setSearchForm(function () {
                return [
                    Input::withParams('user', 'Enter user login/name/email'),
                ];
            })
            ->setFiltering(function (array $filters, Where $where) {
                $where = DataManager::applyUserFilters($filters, $where, ['id'], PLATFORM_BITCOINOVNET);
                return $where;
            });

        $this->see_cards_action = $this->createAction(function (ActionRequest $request) {
            return [
                $this->showModal('Cards', $this->cards_table->setFilters($request->getParams())->build()),
            ];
        });

        $this->delete_card_action = $this->createAction(function (ActionRequest $request) {
            $card = BitcoinovnetUserCardModel::get($request->getParam('card_id'));
            $card->delete(true);
            return [
                $this->cards_table
                    ->setFilters(['user_id' => $card->user_id])
                    ->getReloadAction($request->getParams(), $request->getValues())
            ];
        });

        $this->validate_card_action = $this->createAction(function (ActionRequest $request) {
            $card = BitcoinovnetUserCardModel::get($request->getParam('card_id'));
            $card->validate();
            return [
                $this->cards_table
                    ->setFilters(['user_id' => $card->user_id])
                    ->getReloadAction($request->getParams(), $request->getValues())
            ];
        });

        $this->unvalidate_card_action = $this->createAction(function (ActionRequest $request) {
            $card = BitcoinovnetUserCardModel::get($request->getParam('card_id'));
            $card->unvalidate();
            return [
                $this->cards_table
                    ->setFilters(['user_id' => $card->user_id])
                    ->getReloadAction($request->getParams(), $request->getValues())
            ];
        });

        $this->see_photo_action = $this->createAction(function(ActionRequest $request) {
            $card = BitcoinovnetUserCardModel::get($request->getParam('card_id'));

            $storage = new StorageClient([
                'projectId' => 'narfex-com'
            ]);
            $bucket = $storage->bucket('narfex-com.appspot.com');
            $content = $bucket->object('cards/' .  $card->photo_name)->downloadAsStream()->getContents();
            $extension = pathinfo($card->photo_name, PATHINFO_EXTENSION);

            return [
                $this->showModal(
                    'Photo url',
                    Image::withParams("data:image/{$extension};base64," . base64_encode($content))
                )
            ];
        });

        $this->cards_table = $this->createManagedTable(
            BitcoinovnetUserCardModel::class,
            ['ID', 'Card owner', 'Card number', 'Validated', 'Date', 'Actions']
        )
            ->setDataMapper(function (ModelSet $cards) {
                return $cards->map(function (BitcoinovnetUserCardModel $card) {
                    $actions = [
                        ActionSheetItem::withParams('Delete card')
                            ->onClick($this->delete_card_action->use(['card_id' => $card->id]))
                    ];

                    if (!$card->isValidated()) {
                        $actions[] = ActionSheetItem::withParams('Validate card')
                            ->onClick($this->validate_card_action->use(['card_id' => $card->id]));
                    } else {
                        $actions[] = ActionSheetItem::withParams('Unvalidate card')
                            ->onClick($this->unvalidate_card_action->use(['card_id' => $card->id]));
                    }

                    if ($card->photo_name !== null) {
                        $actions[] = ActionSheetItem::withParams('See card photo')
                            ->onClick($this->see_photo_action->use(['card_id' => $card->id]));
                    }

                    return [
                        $card->id,
                        $card->card_owner,
                        $card->card_number,
                        $card->isValidated() ? 'Yes' : 'No',
                        Time::withParams($card->created_at_timestamp),
                        ActionSheet::withItems(...$actions)
                    ];
                });
            })
            ->setFiltering(function (array $filters, Where $where) {
                if (isset($filters['user_id']) && $filters['user_id']) {
                    $where->set(Where::equal('user_id', $filters['user_id']));
                }
                return $where;
            });
    }

    public function build() {
        $this->layout->push(Block::withParams('Users', $this->users_table->build()));
    }
}
