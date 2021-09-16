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
use Admin\layout\InfoList;
use Admin\layout\InfoListItem;
use Admin\layout\Input;
use Admin\layout\NumberFormat;
use Admin\layout\Tab;
use Admin\layout\Time;
use Admin\layout\Toast;
use Core\Exceptions\Withdrawal\InsufficientFundsException;
use Core\Services\Telegram\SendService;
use DateTime;
use Db\Model\Exception\ModelNotFoundException;
use Db\Model\ModelSet;
use Db\Where;
use Exception;
use Exceptions\WithdrawalRequests\IncorrectStatusException;
use LogicException;
use Models\UserBalanceHistoryModel;
use Models\UserModel;
use Models\UserPermissionModel;
use Models\WithdrawalModel;
use Modules\FiatWalletModule;
use Xendit\Exceptions\ApiException;

class WithdrawalRequests extends PageContainer {
    public static $permission_list = [
        UserPermissionModel::WITHDRAWAL_FIAT_PERMISSION,
    ];

    /** @var DataManager table */
    private $all_table;

    /** @var DataManager table */
    private $confirmation_table;

    /** @var DataManager table */
    private $pending_table;

    /** @var DataManager table */
    private $failed_table;

    /** @var TabsManager table */
    private $tabs;

    /** @var FormManager reject_form */
    private $reject_form;

    /** @var Action $approve_action */
    private $approve_action;

    /** @var Action reject_action */
    private $reject_action;

    /** @var Action reject_action */
    private $info_action;

    public function registerActions() {
        $this->buildRejectForm();
        $this->approve_action = $this->createAction(function(ActionRequest $request) {
            $withdrawal_id = positive($request->getParam('withdrawal_id'));
            if (!$withdrawal_id) {
                return $this->showToast('Withdrawal not found', Toast::TYPE_ERROR);
            }
            try {
                /**  @var WithdrawalModel $withdrawal */
                $withdrawal = WithdrawalModel::get($withdrawal_id);

                $current_tab = null;
                switch ($withdrawal->status) {
                    case UserBalanceHistoryModel::STATUS_CONFIRMATION:
                        $current_tab = $this->confirmation_table;
                        break;
                    case UserBalanceHistoryModel::STATUS_PENDING:
                    case UserBalanceHistoryModel::STATUS_COMPLETED:
                        $current_tab = $this->pending_table;
                        break;
                    case UserBalanceHistoryModel::STATUS_FAILED:
                        $current_tab = $this->failed_table;
                        break;
                }

                FiatWalletModule::approveWithdrawal($withdrawal, $this->getAdmin());

                /** @var UserModel $user */
                $user = UserModel::get($withdrawal->user_id);
                $telegram_service = new SendService();
                $telegram_service->sendMessage('#manual_fiat_withdrawal_approve' . PHP_EOL . sprintf('ID: %s, %s(%s), Date: %s, %s',
                        $withdrawal->id,
                        $user->login,
                        $user->id,
                        (new DateTime())->setTimestamp($withdrawal->created_at_timestamp)->format('d.m.Y H:i:s'),
                        formatNum($withdrawal->amount, 2) . ' ' . $withdrawal->currency
                    ));

                $return = [
                    $this->closeModal(),
                    $this->showToast('Withdrawal has been approved.'),
                    $this->all_table->getReloadAction($request->getParams(), $request->getValues()),
                ];

                if (!is_null($current_tab)) {
                    $return[] = $current_tab->getReloadAction($request->getParams(), $request->getValues());
                }

                return $return;
            } catch (InsufficientFundsException $e) {
                $telegram_service = new SendService();
                $telegram_service->sendMessage('#ERROR Xendit: Insufficient Funds');
                return $this->showErrorToast($e->getMessage());
            }  catch (ModelNotFoundException $e) {
                return $this->showErrorToast('Withdrawal not found');
            } catch (LogicException $exception) {
                $this->showErrorToast($exception->getMessage());
            } catch (ApiException $api_exception) {
                return $this->showErrorToast('Xendit API error: ' . $api_exception->getMessage() . ' Code: ' . $api_exception->getErrorCode());
            } catch (Exception $e) {
                return $this->showErrorToast('Something went wrong. ' . $e->getMessage());
            }
        })->setConfirm(true, "Confirm withdrawal")->needGa();

        $this->reject_action = $this->createAction(function(ActionRequest $request){
            return $this->showModal('Reject withdrawal', $this->reject_form->setParams($request->getParams())->build());
        });

        $this->info_action = $this->createAction(function(ActionRequest $request){
            $withdrawal_id = positive($request->getParam('withdrawal_id'));
            /**
             * @var WithdrawalModel $withdrawal
             */
            $withdrawal = WithdrawalModel::get($withdrawal_id);
            return $this->showModal('Withdrawal Information', $this->buildInfoList($withdrawal));
        });

        $where = $this->mainWhere();
        $this->all_table = $this->createTable($where);

        $where = $this->mainWhere()->set('status', Where::OperatorEq, UserBalanceHistoryModel::STATUS_CONFIRMATION);
        $this->confirmation_table = $this->createTable($where);

        $where = $this->mainWhere()->set('status', Where::OperatorIN, [
                UserBalanceHistoryModel::STATUS_PENDING,
                UserBalanceHistoryModel::STATUS_COMPLETED,
            ]
        );
        $this->pending_table = $this->createTable($where);

        $where = $this->mainWhere()->set('status', Where::OperatorEq, UserBalanceHistoryModel::STATUS_FAILED);
        $this->failed_table = $this->createTable($where);

        $this->tabs = $this->createTabsManager()->setTabs(
            Tab::withParams('All')->setRenderer(function () {
                return $this->all_table->build();
            }),
            Tab::withParams('Confirmation')->setRenderer(function () {
                return $this->confirmation_table->build();
            }),
            Tab::withParams('Pending and Completed')->setRenderer(function () {
                return $this->pending_table->build();
            }),
            Tab::withParams('Failed')->setRenderer(function () {
                return $this->failed_table->build();
            })
        );
    }

    private function mainWhere(): Where {
        return Where::and();
    }

    public function build() {
        $this->layout->push(Block::withParams('Withdrawals', $this->tabs->build()));
    }

    private function createTable(Where $where): DataManager {
        $headers = [
            'ID',
            'User',
            'Amount',
            'Status',
            'Date',
            'Actions'
        ];

        $confirmation = UserBalanceHistoryModel::STATUS_CONFIRMATION;
        return $this
            ->createManagedTable(WithdrawalModel::class, $headers, $where)
            ->setOrderBy(["FIELD(status, {$confirmation})" => 'DESC', 'id' => 'DESC'])
            ->setDataMapper(function (ModelSet $items) {
                $user_ids = $items->column('user_id');
                $users = UserModel::select(Where::in('id', $user_ids));

                return $items->map(function(WithdrawalModel $withdrawal) use ($users) {
                    /** @var UserModel $user */
                    $user = $users->getItem($withdrawal->user_id);

                    if ($withdrawal->status === UserBalanceHistoryModel::STATUS_CONFIRMATION) {
                        $actionSheet = ActionSheet::withItems(
                            ActionSheetItem::withParams('Approve', 'primary')
                                ->onClick($this->approve_action->use(['withdrawal_id' => $withdrawal->id])),
                            ActionSheetItem::withParams('Reject', 'destructive')
                                ->onClick($this->reject_action->use(['withdrawal_id' => $withdrawal->id])),
                            ActionSheetItem::withParams('Info')->onClick($this->info_action->use(['withdrawal_id' => $withdrawal->id]))
                        );
                    } else {
                        $actionSheet = ActionSheet::withItems(
                            ActionSheetItem::withParams('Info')->onClick($this->info_action->use(['withdrawal_id' => $withdrawal->id]))
                        );
                    }
                    return [
                        $withdrawal->id,
                        $user->fullName() . " ({$user->id})",
                        NumberFormat::withParams($withdrawal->amount, $withdrawal->currency),
                        UserBalanceHistoryModel::STATUSES_MAP[$withdrawal->status],
                        $withdrawal->created_at_timestamp ? Time::withParams($withdrawal->created_at_timestamp) : '',
                        $actionSheet
                    ];
                });
            })
            ->setUserFilters()
            ->setOrderBy(['id' => 'DESC']);
    }

    public function buildRejectForm() {
        return $this->reject_form = $this
            ->createFormManager()
            ->setItems(
                function ($params) {
                    return [
                        Input::withParams('reason', 'Enter reason')
                    ];
                })
            ->onSubmit(function(ActionRequest $request) {
                $withdrawal_id = positive($request->getParam('withdrawal_id'));
                if (!$withdrawal_id) {
                    return $this->showToast('Withdrawal not found', Toast::TYPE_ERROR);
                }
                $reason = $request->getValue('reason');
                try {
                    /** @var WithdrawalModel $withdrawal */
                    $withdrawal = WithdrawalModel::get($withdrawal_id);
                    FiatWalletModule::rejectWithdrawal($withdrawal, $reason);
                } catch (IncorrectStatusException $e) {
                    return $this->showToast($e->getMessage(), Toast::TYPE_ERROR);
                } catch (ModelNotFoundException $e) {
                    return $this->showToast('Withdrawal not found', Toast::TYPE_ERROR);
                } catch (\Exception $e) {
                    return $this->showToast('Something went wrong. ' . $e->getMessage(), Toast::TYPE_ERROR);
                }

                return [
                    $this->closeModal(),
                    $this->showToast('Withdrawal has been rejected.'),
                    $this->all_table->getReloadAction($request->getParams(), $request->getValues()),
                    $this->confirmation_table->getReloadAction($request->getParams(), $request->getValues())
                ];
            }, true);
    }

    public function buildInfoList(WithdrawalModel $withdrawal): InfoList {
        $admin = null;
        if (isset($withdrawal->admin_id)) {
            $admin = UserModel::get($withdrawal->admin_id);
        }

        return InfoList::withItems(
            InfoListItem::withParams('Bank', $withdrawal->bank_code),
            InfoListItem::withParams('Account Holder Name', $withdrawal->account_holder_name),
            InfoListItem::withParams('Account Number', $withdrawal->account_number),
            InfoListItem::withParams('Currency', $withdrawal->currency),
            InfoListItem::withParams('Fee', $withdrawal->fee),
            InfoListItem::withParams('Admin', $admin ? $admin->fullName() . " (ID {$admin->id})" : 'service'),
            InfoListItem::withParams('Rejected by', $withdrawal->isRejectedByAdmin() ? 'admin' : 'service'),
            InfoListItem::withParams('Fail reason', $withdrawal->reject_message),
            InfoListItem::withParams('External ID', $withdrawal->external_id)
        );
    }
}
