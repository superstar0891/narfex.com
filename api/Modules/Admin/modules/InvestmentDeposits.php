<?php


namespace Admin\modules;


use Admin\helpers\ActionRequest;
use Admin\helpers\DataManager;
use Admin\helpers\PageContainer;
use Admin\layout\Action;
use Admin\layout\ActionSheet;
use Admin\layout\ActionSheetItem;
use Admin\layout\Block;
use Admin\layout\DropDown;
use Admin\layout\InfoList;
use Admin\layout\InfoListItem;
use Admin\layout\Input;
use Admin\layout\NumberFormat;
use Admin\layout\Select;
use Admin\layout\Time;
use Db\Model\ModelSet;
use Db\Where;
use Models\DepositModel;
use Models\PlanModel;
use Models\ProfitModel;
use Models\UserModel;
use Models\WithdrawalModel;
use Modules\InvestmentModule;
use Serializers\InvestmentSerializer;

class InvestmentDeposits extends PageContainer {

    /* @var DataManager */
    private $table;

    /** @var Action */
    private $info_action;

    const AVAILABLE_TYPES = [
        'Comfort' => 'Comfort',
        'in_1_month' => 'in 1 month',
        'in_1_year' => 'in 1 year',
        'in_3_months' => 'in 3 months',
        'Optimal' => 'Optimal',
        'Pool' => 'Pool',
        'Special Plan' => 'Special Plan',
        'Standard' => 'Standard',
        'Standart' => 'Standart',
        'Test_drive' => 'Test drive',
    ];

    public function registerActions() {
        $this->createDepositsTable();
        $this->createDepositInfoAction();
    }

    public function build() {
        $this->layout->push(Block::withParams('Deposits', $this->table->build()));
    }

    public function createDepositsTable() {
        $headers = [
            'ID',
            'User (ID)',
            'Amount',
            'Type',
            'Period',
            'Deposit (tariff, type)',
            'Date',
            'Actions'
        ];

        $this->table = $this
            ->createManagedTable(DepositModel::class, $headers)
            ->setDataMapper(function(ModelSet $deposits){
                $users_ids = $deposits->column('user_id');
                $plans_ids = $deposits->column('plan');
                $users = UserModel::select(Where::in('id', $users_ids), false);
                $plans = PlanModel::select(Where::in('id', $plans_ids), false);

                return $deposits->map(function(DepositModel $deposit) use ($users, $plans){
                    /** @var UserModel $user */
                    $user = $users->getItem($deposit->user_id);
                    /** @var PlanModel $plan */
                    $plan = $plans->getItem($deposit->plan);

                    $actionSheet = ActionSheet::withItems(
                        ActionSheetItem::withParams('Information', 'primary')
                            ->onClick($this->info_action->use(['deposit_id' => $deposit->id]))
                    );

                    return [
                        $deposit->id,
                        $user ? $user->fullName() . ' (' . $user->id . ')' : '',
                        NumberFormat::withParams($deposit->amount, $deposit->currency),
                        $deposit->operation,
                        $deposit->days . ' / ' . $plan->days,
                        $plan->description . ', ' . InvestmentModule::getDepositType($deposit),
                        Time::withParams((int) strtotime($deposit->created_at)),
                        $actionSheet
                    ];
                });
            })
            ->setSearchForm(function () {
                return [
                    Input::withParams('user', 'Enter user login/name/email'),
                    Select::withParams('operation', 'Select type', [
                        'invest' => 'Invest',
                        'reinvest' => 'Reinvest',
                    ]),
                ];
            })
            ->setFiltering(function(array $filters, Where $where){
                if (isset($filters['operation'])) {
                    if ($type = $filters['operation']) {
                        $where->set(Where::equal('operation', $type));
                    }
                }
                $where = DataManager::applyUserFilters($filters, $where);
                return $where;
            });
    }

    public function createDepositInfoAction() {
        $this->info_action = $this->createAction(function(ActionRequest $request){
            $deposit_id = positive($request->getParam('deposit_id'));
            /** @var DepositModel $deposit */
            $deposit = DepositModel::get($deposit_id);
            return $this->showModal('Deposit Information', $this->buildInfoList($deposit));
        });
    }

    public function buildInfoList(DepositModel $deposit): InfoList {
        /** @var PlanModel $plan */
        $plan = PlanModel::get($deposit->plan, false);
        $deposit_info = InvestmentSerializer::listItem($deposit, $plan);
        return InfoList::withItems(
            InfoListItem::withParams('Plan', $deposit_info['description']),
            InfoListItem::withParams('Currency', $deposit_info['currency']),
            InfoListItem::withParams('Day percent', $deposit_info['day_percent']),
            InfoListItem::withParams('Percent', $deposit_info['percent']),
            InfoListItem::withParams('Current percent', $deposit_info['current_percent']),
            InfoListItem::withParams('Plan percent',$deposit_info['plan_percent']),
            InfoListItem::withParams('Days', $deposit_info['days']),
            InfoListItem::withParams('Passed days', $deposit_info['passed_days']),
            InfoListItem::withParams('Type', $deposit_info['operation']),
            InfoListItem::withParams('Amount',  NumberFormat::withParams($deposit_info['amount'], $deposit->currency)),
            InfoListItem::withParams('Profit', NumberFormat::withParams($deposit_info['profit'], $deposit->currency)),
            InfoListItem::withParams('Profit in USD', NumberFormat::withParams($deposit_info['usd_profit'], CURRENCY_USD))
        );
    }
}
