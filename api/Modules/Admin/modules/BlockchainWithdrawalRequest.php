<?php

namespace Admin\modules;

use Admin\common\UserLogCommon;
use Admin\helpers\ActionRequest;
use Admin\helpers\DataManager;
use Admin\helpers\PageContainer;
use Admin\helpers\TabsManager;
use Admin\layout\Action;
use Admin\layout\ActionSheet;
use Admin\layout\ActionSheetItem;
use Admin\layout\Block;
use Admin\layout\Clipboard;
use Admin\layout\DropDown;
use Admin\layout\Input;
use Admin\layout\NumberFormat;
use Admin\layout\Tab;
use Admin\layout\Time;
use Admin\layout\Toast;
use Admin\serializers\UserLogSerializer;
use Blockchain\Exception\CallListenerMethodException;
use Db\Model\ModelSet;
use Db\Where;
use Models\Logs\BlockhainWithdrawalRequestLog;
use Models\UserLogModel;
use Models\UserModel;
use Models\WithdrawalRequest;
use Modules\BlockchainWithdrawalModule;

class BlockchainWithdrawalRequest extends PageContainer {

    /* @var DataManager $requests */
    private $requests;

    /* @var DataManager $pending_requests */
    private $pending_requests;

    /* @var DataManager $rejected_requests */
    private $rejected_requests;

    /* @var DataManager $paused_requests */
    private $paused_requests;

    /* @var DataManager $logs */
    private $logs;

    /* @var Action $reject */
    private $reject;

    /* @var Action $process */
    private $process;

    /* @var Action $pause */
    private $pause;

    /* @var Action $pause */
    private $start;

    /* @var TabsManager */
    private $tabs;

    public function registerActions() {
        $this->createProcessAction();
        $this->createRejectAction();
        $this->createPauseAction();
        $this->createStartAction();

        $this->requests = $this->createTable(
            Where::equal('status', WithdrawalRequest::STATUS_DONE),
            WithdrawalRequest::STATUS_DONE
        );

        $this->pending_requests = $this->createTable(
            Where::in('status', [WithdrawalRequest::STATUS_PENDING, WithdrawalRequest::STATUS_BOOST]),
            WithdrawalRequest::STATUS_PENDING
        );

        $this->paused_requests = $this->createTable(
            Where::equal('status', WithdrawalRequest::STATUS_PAUSED),
            WithdrawalRequest::STATUS_PAUSED
        );

        $this->rejected_requests = $this->createTable(
            Where::equal('status', WithdrawalRequest::STATUS_REJECTED),
            'rejected'
        );

        $this->logs = $this->createLogsTable();

        $this->tabs = $this->createTabsManager()
            ->setTabs(
                Tab::withParams('Pending Requests')->setRenderer(function () {
                    return $this->pending_requests->build();
                }),
                Tab::withParams('Rejected Requests')->setRenderer(function () {
                    return $this->rejected_requests->build();
                }),
                Tab::withParams('Paused Requests')->setRenderer(function () {
                    return $this->paused_requests->build();
                }),
                Tab::withParams('Done Requests')->setRenderer(function () {
                    return $this->requests->build();
                }),
                Tab::withParams('Logs')->setRenderer(function () {
                    return $this->logs->build();
                })
            );
    }

    public function createLogsTable(): DataManager {
        return $this
            ->createManagedTable(
                UserLogModel::class,
                ['Id', 'User ID', 'Action', 'Created', 'Info', 'Ip', 'Device', 'Browser'],
                Where::in('action', BlockhainWithdrawalRequestLog::$actions)
            )
            ->setDataMapper(function (ModelSet $logs)  {
                return UserLogSerializer::rows($logs);
            })
            ->setSearchForm(function () {
                return UserLogCommon::searchForm();
            })
            ->setFiltering(function (array $filters, Where $where) {
                return UserLogCommon::dataFiltering($filters, $where);
            })
            ->setOrderBy(['created_at_timestamp' => 'DESC']);
    }

    private function createTable(Where $where, string $tab_name) {
        $headers = ['ID', 'User', 'User Address', 'To Address', 'Amount', 'Status', 'Date'];
        if (!in_array($tab_name, ['done', 'rejected'])) {
            array_push($headers, 'Actions');
        }

        return $this->createManagedTable(WithdrawalRequest::class, $headers, $where)
            ->setDataMapper(function (ModelSet $payments) use ($tab_name) {
                $users = null;
                $user_ids = array_unique($payments->column('user_id'));
                if (!empty($user_ids)) {
                    $users = UserModel::select(Where::in('id', $user_ids));
                }
                return $payments->map(function (WithdrawalRequest $payment) use ($tab_name, $users) {
                    $row = [];
                    $user_info = '';
                    if (!is_null($users)) {
                        /** @var UserModel|null $user */
                        $user = $users->getItem($payment->user_id);
                        $user_info = ($user ? $user->login : '') . " ($payment->user_id)";
                    }
                    switch ($tab_name) {
                        case WithdrawalRequest::STATUS_BOOST:
                        case WithdrawalRequest::STATUS_PENDING:
                            $row = [
                                $payment->id,
                                $user_info,
                                Clipboard::withParams($payment->user_address, 32),
                                Clipboard::withParams($payment->to_address, 32),
                                NumberFormat::withParams($payment->amount, $payment->currency),
                                $payment->status,
                                $payment->created_at_timestamp ? Time::withParams($payment->created_at_timestamp): '',
                                ActionSheet::withItems(
                                    ActionSheetItem::withParams('Process now')->onClick($this->process->use([
                                        'id' => $payment->id,
                                    ])),
                                    ActionSheetItem::withParams('Reject')->onClick($this->reject->use([
                                        'id' => $payment->id,
                                    ])),
                                    ActionSheetItem::withParams('Pause')->onClick($this->pause->use([
                                        'id' => $payment->id,
                                    ]))
                                )
                            ];
                            break;
                        case WithdrawalRequest::STATUS_PAUSED:
                            $row = [
                                $payment->id,
                                $payment->user_id,
                                Clipboard::withParams($payment->user_address, 32),
                                Clipboard::withParams($payment->to_address, 32),
                                NumberFormat::withParams($payment->amount, $payment->currency),
                                $payment->status,
                                $payment->created_at_timestamp ? Time::withParams($payment->created_at_timestamp): '',
                                ActionSheet::withItems(
                                    ActionSheetItem::withParams('Start')->onClick($this->start->use([
                                        'id' => $payment->id,
                                    ]))
                                )
                            ];
                            break;
                        case WithdrawalRequest::STATUS_DONE:
                        case WithdrawalRequest::STATUS_REJECTED:
                            $row = [
                                $payment->id,
                                $payment->user_id,
                                Clipboard::withParams($payment->user_address, 32),
                                Clipboard::withParams($payment->to_address, 32),
                                NumberFormat::withParams($payment->amount, $payment->currency),
                                $payment->status,
                                $payment->created_at_timestamp ? Time::withParams($payment->created_at_timestamp): '',
                            ];
                            break;
                    }

                    return $row;
                });
            })
            ->setSearchForm(function () {
                $currencies = [];
                foreach (currencies() as $currency) {
                    $currencies[] = [$currency['curr'], ucfirst($currency['name'])];
                }
                return [
                    Input::withParams('user_id', 'Enter user id'),
                    Input::withParams('user_address', 'Enter user address'),
                    DropDown::withParams('currency', 'Choice currency', $currencies)
                ];
            })
            ->setFiltering(function (array $filters, Where $where) {
                if (isset($filters['user_id'])) {
                    $user_id = positive($filters['user_id']);
                    if ($user_id > 0) {
                        $where->set('user_id', Where::OperatorEq, $user_id);
                    }
                }
                if (isset($filters['user_address'])) {
                    $where->set('user_address', Where::OperatorEq, $filters['user_address']);
                }
                if (isset($filters['currency'])) {
                    $where->set('currency', Where::OperatorEq, $filters['currency']);
                }
                return $where;
            })
            ->setOrderBy(['id' => 'DESC']);
    }

    public function build() {
        $this->layout->push(Block::withParams('Payments', $this->tabs->build()));
    }

    private function createProcessAction() {
        $this->process = $this->createAction(function (ActionRequest $request) {
            try {
                $withdrawal = $this->getWithdrawalById($request);
                BlockchainWithdrawalModule::processNow($withdrawal);
            } catch (CallListenerMethodException $e) {
                $message = $e->getMessage() ?: 'Failed to get response from blockchain';
                return [
                    $this->showToast($message, Toast::TYPE_ERROR)
                ];
            } catch (\Exception $e) {
                return [
                    $this->showToast($e->getMessage(), Toast::TYPE_ERROR)
                ];
            }

            return [
                $this->showToast('Withdrawal request process'),
                $this->pending_requests->getReloadAction($request->getParams(), $request->getValues()),
            ];
        })->setConfirm(true, 'Process withdrawal request', true)->needGa();
    }

    private function createRejectAction() {
        $this->reject = $this->createAction(function (ActionRequest $request) {
            try {
                $withdrawal = $this->getWithdrawalById($request);
                BlockchainWithdrawalModule::reject($withdrawal);
            } catch (\Exception $e) {
                return [
                    $this->showToast($e->getMessage(), Toast::TYPE_ERROR)
                ];
            }

            return [
                $this->showToast('Withdrawal request rejected'),
                $this->pending_requests->getReloadAction($request->getParams(), $request->getValues()),
            ];
        })->setConfirm(true, 'Reject withdrawal request', true)->needGa();
    }

    private function createPauseAction() {
        $this->pause = $this->createAction(function (ActionRequest $request) {
            try {
                $withdrawal = $this->getWithdrawalById($request);
                BlockchainWithdrawalModule::pause($withdrawal);
            } catch (\Exception $e) {
                return [
                    $this->showToast($e->getMessage(), Toast::TYPE_ERROR)
                ];
            }

            return [
                $this->showToast('Withdrawal request paused'),
                $this->pending_requests->getReloadAction($request->getParams(), $request->getValues()),
            ];
        })->setConfirm(true, 'Pause withdrawal request', true)->needGa();
    }

    private function createStartAction() {
        $this->start = $this->createAction(function (ActionRequest $request) {
            try {
                $withdrawal = $this->getWithdrawalById($request);
                BlockchainWithdrawalModule::start($withdrawal);
            } catch (\Exception $e) {
                return [
                    $this->showToast($e->getMessage(), Toast::TYPE_ERROR)
                ];
            }

            return [
                $this->showToast('Withdrawal request started'),
                $this->pending_requests->getReloadAction($request->getParams(), $request->getValues()),
            ];
        })->setConfirm(true, 'Start withdrawal request', true)->needGa();
    }

    /**
     * @param ActionRequest $request
     * @return WithdrawalRequest
     * @throws \Db\Exception\DbAdapterException
     * @throws \Db\Exception\InvalidSelectQueryException
     * @throws \Db\Exception\InvalidWhereOperatorException
     * @throws \Db\Model\Exception\ModelNotFoundException
     * @throws \Db\Model\Exception\TableNameUndefinedException
     * @throws \Exceptions\InvalidKeyException
     */
    private function getWithdrawalById(ActionRequest $request): WithdrawalRequest {
        return WithdrawalRequest::get($request->getParam('id'));
    }
}
