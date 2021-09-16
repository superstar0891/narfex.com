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
use Admin\layout\Input;
use Admin\layout\TableRow;
use Admin\layout\Time;
use Db\Model\ModelSet;
use Db\Transaction;
use Db\Where;
use Models\ReviewModel;
use Models\UserPermissionModel;

class BitcoinovnetReviews extends PageContainer {
    /** @var DataManager */
    private $table;
    /** @var Action */
    private $approve;
    /** @var Action */
    private $delete;
    /** @var Action */
    private $edit;
    /** @var FormManager */
    private $edit_form;

    public static $permission_list = [
        UserPermissionModel::REVIEW_BITCOINOVNET
    ];

    public function registerActions() {
        $this->table = $this->createManagedTable(
            ReviewModel::class,
            ['ID', 'Status', 'Name', 'Content', 'Date', 'Actions'],
            Where::equal('platform', PLATFORM_BITCOINOVNET)
        )
            ->setDataMapper(function (ModelSet $items) {
                return $items->map(function (ReviewModel $review) {
                    $actions = [
                        ActionSheetItem::withParams('Edit')
                            ->onClick($this->edit->use(['review_id' => $review->id])),
                        ActionSheetItem::withParams('Delete')
                            ->onClick($this->delete->use(['review_id' => $review->id]))
                    ];

                    if ($review->status === ReviewModel::STATUS_MODERATION) {
                        $actions[] = ActionSheetItem::withParams('Approve')
                            ->onClick($this->approve->use(['review_id' => $review->id]));
                    }

                    $row = TableRow::withParams(...[
                        $review->id,
                        $review->status ? 'published' : 'moderation',
                        $review->name ?? '',
                        mb_strimwidth($review->content, 0, 50, '...'),
                        Time::withParams($review->created_at_timestamp),
                        ActionSheet::withItems(...$actions)
                    ]);

                    if ($review->status === ReviewModel::STATUS_MODERATION) {
                        $row->accent();
                    }

                    return $row;
                });
            });

        $this->delete = $this->createAction(function (ActionRequest $request) {
            try {
                $review = ReviewModel::get($request->getParam('review_id'));
                $review->delete(true);
            } catch (\Exception $e) {
                return $this->showErrorToast($e->getMessage());
            }
            return [
                $this->table->getReloadAction($request->getParams(), $request->getValues())
            ];
        });

        $this->approve = $this->createAction(function (ActionRequest $request) {
            try {
                $review = ReviewModel::get($request->getParam('review_id'));
                $review->status = ReviewModel::STATUS_PUBLIC;
                $review->save();
            } catch (\Exception $e) {
                return $this->showErrorToast($e->getMessage());
            }
            return [
                $this->table->getReloadAction($request->getParams(), $request->getValues())
            ];
        });

        $this->edit = $this->createAction(function (ActionRequest $request) {
            return [
                $this->showModal('Edit review', $this->edit_form->setParams($request->getParams())->build())
            ];
        });

        $this->edit_form = $this->createFormManager()
            ->setItems(function ($params) {
                $review = ReviewModel::get($params['review_id']);
                return [
                    Input::withParams('name', 'Name', $review->name, 'Name'),
                    Input::withParams('content', 'Content', $review->content, 'Content'),
                ];
            })
            ->onSubmit(function (ActionRequest $request) {
                try {
                    Transaction::wrap(function () use ($request) {
                        /**
                         * @var string $name
                         * @var string $content
                         */
                        extract($request->getValues([
                            'name' => ['required', 'minLen' => 2, 'maxLen' => 255],
                            'content' => ['required'],
                        ]));
                        $review = ReviewModel::get($request->getParam('review_id'));
                        $review->name = $name;
                        $review->content = $content;
                        $review->save();

                        return $review;
                    });
                } catch (\Exception $e) {
                    return $this->showErrorToast($e->getMessage());
                }

                return [
                    $this->table->getReloadAction([], []),
                    $this->closeModal(),
                ];
            });
    }

    public function build() {
        $this->layout->push(Block::withParams('Reviews', $this->table->build()));
    }
}
