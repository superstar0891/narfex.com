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
use Admin\layout\InfoList;
use Admin\layout\InfoListItem;
use Admin\layout\Input;
use Admin\layout\NumberFormat;
use Admin\layout\Time;
use Admin\layout\Toast;
use Core\Services\Log\UserRoleLog;
use Db\Model\Exception\ModelNotFoundException;
use Db\Model\ModelSet;
use Db\Where;
use Models\BalanceModel;
use Models\ProfitModel;
use Models\UserModel;
use Models\UserRoleModel;

class Agents extends PageContainer {

    /** @var DataManager $table */
    private $table;

    /** @var Action $info_action */
    private $info_action;

    /** @var Action $remove_agent */
    private $remove_agent;

    /** @var Action $adding_agent */
    private $adding_agent_modal;

    private $adding_agent_form;

    public function registerActions() {
        $this->createRemoveAgentAction();
        $this->createInfoAction();
        $this->createAddingAgentAction();
        $this->createAddingAgentForm();

        $this->buildTable();
    }

    public function build() {
        $button = Button::withParams('Add agent')
            ->onClick($this->adding_agent_modal);
        $this->layout->push(Block::withParams('Actions', $button));
        $this->layout->push(Block::withParams('Agents', $this->table->build()));
    }

    public function buildTable() {
        $headers = ['User', 'Registered at', 'Actions'];
        $where = Where::and()
            ->set('roles', Where::OperatorLike, '%agent%');
        $this->table = $this
            ->createManagedTable(UserModel::class, $headers, $where)
            ->setDataMapper(function(ModelSet $items) {
                return $items->map(function (UserModel $user) {
                   $actions = ActionSheet::withItems(
                       ActionSheetItem::withParams('Info')->onClick($this->info_action->use(['user_id' => $user->id])),
                       ActionSheetItem::withParams('Remove')->onClick($this->remove_agent->use(['user_id' => $user->id]))
                   );
                   return [
                       $user->fullName() . " ({$user->login})",
                       $user->created_at_timestamp ? Time::withParams($user->created_at_timestamp) : '',
                       $actions
                   ];
                });
            })
            ->setUserFilters('id');
    }

    private function createRemoveAgentAction() {
        $this->remove_agent = $this->createAction(function (ActionRequest $request) {
            $user_id = $request->getParam('user_id');
            $user = UserModel::get($user_id);
            /** @var UserModel $user */
            $user->removeRole(UserRoleModel::AGENT_ROLE);
            $user->save();

            return [
                $this->showToast("Role successfully deleted"),
                $this->table->getReloadAction($request->getParams(), $request->getValues())

            ];
        })->setConfirm(true, 'Remove role', true);
    }

    private function createInfoAction() {
        $this->info_action = $this->createAction(function (ActionRequest $request){
            /** @var UserModel $user */
            $user = UserModel::get($request->getParam('user_id'));
            return $this->showModal('Agent info', $this->buildInfoList($user));
        });
    }

    private function buildInfoList(UserModel $user) {
        $agent_balances = BalanceModel::select(
            Where::and()
            ->set('user_id', Where::OperatorEq, $user->id)
            ->set('category', Where::OperatorEq, BalanceModel::CATEGORY_PARTNERS)
        );
        $items = [];
        $items[] = InfoListItem::withParams('Agent', $user->fullName());
        if (!$agent_balances->isEmpty()) {
            foreach ($agent_balances as $balance) {
                /** @var BalanceModel $balance */
                $items[] = InfoListItem::withParams(
                    strtoupper($balance->currency) . " (balance)",
                    NumberFormat::withParams($balance->amount, $balance->currency, ['hidden_currency' => true])
                );
            }
        }

        $agent_profits = ProfitModel::queryBuilder()
            ->where(
                Where::and()
                    ->set(Where::equal('user_id', $user->id))
                    ->set(Where::in('type', ProfitModel::REFERRAL_PROFITS))
            )
            ->columns(['currency', 'SUM(amount)' => 'amount'], true)
            ->groupBy(['currency'])
            ->select();

        foreach ($agent_profits as $profit) {
            $items[] = InfoListItem::withParams(
                strtoupper($profit['currency']) . " (profit)",
                NumberFormat::withParams($profit['amount'], $profit['currency'], ['hidden_currency' => true])
            );
        }

        return InfoList::withItems(
            ...$items
        );
    }

    private function createAddingAgentAction() {
        $this->adding_agent_modal = $this->createAction(function(ActionRequest $request) {
            return $this->showModal('Add agent', $this->adding_agent_form->build());
        });
    }

    private function createAddingAgentForm() {
        $this->adding_agent_form = $this->createFormManager()
            ->setItems(function($params){
                return [
                    Input::withParams('user_id', 'User ID')
                ];
            })->onSubmit(function(ActionRequest $request) {
                try {
                    /** @var UserModel $user */
                    $user = UserModel::get($request->getValue('user_id'));
                } catch (ModelNotFoundException $e) {
                    return [
                        $this->showToast('User not found', Toast::TYPE_ERROR),
                        $this->closeModal()
                    ];
                }

                $user->addRole(UserRoleModel::AGENT_ROLE);
                $user->save();
                UserRoleLog::log($user, [UserRoleModel::AGENT_ROLE], [], true, self::getAdmin());

                return [
                    $this->showToast('Role successfully added'),
                    $this->table->getReloadAction($request->getParams(), $request->getValues()),
                    $this->closeModal()
                ];

            });
    }
}
