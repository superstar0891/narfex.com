<?php

namespace Admin\modules;

use Admin\helpers\ActionRequest;
use Admin\helpers\PageContainer;
use Admin\layout\Action;
use Admin\layout\ActionSheet;
use Admin\layout\ActionSheetItem;
use Admin\layout\Block;
use Admin\layout\Button;
use Admin\layout\Input;
use Admin\layout\Time;
use Admin\layout\Toast;
use Db\Model\ModelSet;
use Db\Where;
use Models\UserModel;
use Models\WithdrawDisabledModel;

class WithdrawalDisabledUsers extends PageContainer {

    /* @var \Admin\helpers\DataManager $table */
    private $table;

    /* @var Action $ban_action */
    private $ban_action;

    /* @var \Admin\helpers\FormManager */
    private $ban_form;

    public function registerActions() {

        $this->createBanAction();
        $unban = $this->createAction(function (ActionRequest $request) {
            $user_id = positive($request->getParam('user_id'));
            $exist = WithdrawDisabledModel::select(Where::equal('user_id', $user_id));
            if ($exist->isEmpty()) {
                return $this->showToast('User not banned', Toast::TYPE_ERROR);
            }

            /* @var \Models\WithdrawDisabledModel $item */
            foreach ($exist as $item) {
                $item->delete();
            }

            return [
                $this->showToast('User unbanned'),
                $this->table->getReloadAction($request->getParams(), $request->getValues()),
            ];
        });

        $headers = ['ID', 'User', 'Banner', 'Reason', 'Date', 'Actions'];
        $this->table = $this
            ->createManagedTable(WithdrawDisabledModel::class, $headers)
            ->setDataMapper(function (ModelSet $items) use ($unban) {
                $user_ids = array_merge($items->column('user_id'), $items->column('banner_id'));
                $users = UserModel::select(Where::in('id', $user_ids), false);
                return $items->map(function (WithdrawDisabledModel $item) use ($users, $unban) {
                    /* @var UserModel $user */
                    $user = $users->getItem($item->user_id);

                    /* @var UserModel $banner */
                    $banner = $users->getItem($item->banner_id);

                    return [
                        $item->id,
                        $user->fullName() . ' (' . $user->id . ')',
                        $banner->fullName() . ' (' . $banner->id . ')',
                        $item->reason,
                        $item->created_at_timestamp ? Time::withParams($item->created_at_timestamp) : '',
                        ActionSheet::withItems(
                            ActionSheetItem::withParams('Unban')->onClick($unban->use(['user_id' => $user->id]))
                        ),
                    ];
                });
            })
            ->setUserFilters();

        $this->ban_form = $this->createFormManager()
            ->setItems(
                function ($params) {
                    return [
                        Input::withParams('user_id', 'Enter user id'),
                        Input::withParams('reason', 'Enter reason')
                    ];
                })
            ->onSubmit(function (ActionRequest $request) {
                $values = $request->getValues();
                $params = $request->getParams();

                $user_id = positive($values['user_id']);
                $reason = trim($values['reason']);

                try {
                    $user = UserModel::get($user_id);
                } catch (\Exception $e) {
                    return $this->showToast('User not found', Toast::TYPE_ERROR);
                }

                $exist = WithdrawDisabledModel::select(Where::equal('user_id', $user->id));
                if (!$exist->isEmpty()) {
                    return $this->showToast('User already banned', Toast::TYPE_ERROR);
                }

                $w = new WithdrawDisabledModel();
                $w->user_id = $user->id;
                $w->banner_id = $this->getAdmin()->id;
                $w->reason = $reason;
                $w->save();

                return [
                    $this->closeModal(),
                    $this->table->getReloadAction($params, $values),
                    $this->showToast('User banned'),
                ];
            });
    }

    private function createBanAction() {
        $this->ban_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal('Ban new user', $this->ban_form->build());
        });
    }

    public function build() {

        $button = Button::withParams('Ban new user')
            ->onClick($this->ban_action);

        $this->layout->push(Block::withParams('Actions', $button));
        $this->layout->push(Block::withParams('Banned users', $this->table->build()));
    }
}
