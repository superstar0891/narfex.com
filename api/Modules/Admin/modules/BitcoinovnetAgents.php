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
use Admin\layout\InfoList;
use Admin\layout\InfoListItem;
use Admin\layout\Input;
use Admin\layout\NumberFormat;
use Admin\layout\Select;
use Admin\layout\Tab;
use Admin\layout\TableRow;
use Admin\layout\Text;
use Admin\layout\Time;
use Db\Model\ModelSet;
use Db\Transaction;
use Db\Where;
use Exceptions\InvalidKeyException;
use Models\AgentModel;
use Models\AgentPromoCodeModel;
use Models\ProfitModel;
use Models\UserModel;
use Models\UserPermissionModel;
use Modules\BitcoinovnetModule;

class BitcoinovnetAgents extends PageContainer {

    public static $permission_list = [
        UserPermissionModel::AGENT_BITCOINOVNET
    ];

    /** @var DataManager */
    private $profits;

    /** @var DataManager */
    private $agent_profits;

    /** @var DataManager */
    private $agents;

    /** @var TabsManager */
    private $tabs;

    /** @var Action */
    private $info_agent_action;

    /** @var Action */
    private $edit_agent_action;

    /** @var Action */
    private $delete_agent_action;

    /** @var Action */
    private $add_agent_action;

    /** @var Action */
    private $restore_action;

    /** @var FormManager */
    private $agent_form;

    public function registerActions() {
        $this->profits = $this->createManagedTable(
            ProfitModel::class,
            ['ID', 'Wallet ID', 'Amount', 'User', 'Percent reward', 'Rate', 'Date'],
            Where::and()->set(Where::equal('type', ProfitModel::TYPE_BITCOINOVNET_PROFIT))
        )
            ->setDataMapper(function (ModelSet $profits) {
                $users = UserModel::select(
                    Where::in('id', array_unique($profits->column('user_id')))
                );
                return $profits->map(function (ProfitModel $profit) use ($users) {
                    $user = $users->getItem($profit->user_id);
                    /** @var UserModel $user */
                    return [
                        $profit->id,
                        $profit->wallet_id,
                        NumberFormat::withParams($profit->amount, $profit->currency),
                        $user ? "{$user->email} ({$user->id})" : $user->id,
                        NumberFormat::withParams($profit->agent_percent_profit, null, ['percent' => true]),
                        $profit->rate ? NumberFormat::withParams($profit->rate, CURRENCY_RUB) : 'NULL',
                        Time::withParams($profit->created_at_timestamp),
                    ];
                });
            })
            ->setSearchForm(function () {
                return [
                    Input::withParams('user', 'Enter user login/name/email'),
                    Input::withParams('date_from', 'Date from d/m/Y'),
                    Input::withParams('date_to', 'Date to d/m/Y'),
                ];
            })
            ->setFiltering(function (array $filters, Where $where) {
                $where = DataManager::applyDateFilters($filters, $where);
                $where = DataManager::applyUserFilters($filters, $where, ['user_id'], PLATFORM_BITCOINOVNET);
                return $where;
            });

        $this->agent_profits = $this->createManagedTable(
            ProfitModel::class,
            ['ID', 'Wallet ID', 'Amount', 'User', 'Percent reward', 'Rate', 'Date'],
            Where::and()->set(Where::equal('type', ProfitModel::TYPE_BITCOINOVNET_PROFIT))
        )
            ->setDataMapper(function (ModelSet $profits) {
                $users = UserModel::select(
                    Where::in('id', array_unique($profits->column('user_id')))
                );
                return $profits->map(function (ProfitModel $profit) use ($users) {
                    $user = $users->getItem($profit->user_id);
                    /** @var UserModel $user */
                    return [
                        $profit->id,
                        $profit->wallet_id,
                        NumberFormat::withParams($profit->amount, $profit->currency),
                        $user ? "{$user->email} ({$user->id})" : $user->id,
                        NumberFormat::withParams($profit->agent_percent_profit, null, ['percent' => true]),
                        $profit->rate ? NumberFormat::withParams($profit->rate, CURRENCY_RUB) : 'NULL',
                        Time::withParams($profit->created_at_timestamp),
                    ];
                });
            })
            ->setSearchForm(function () {
                return [
                    Input::withParams('date_from', 'Date from d/m/Y'),
                    Input::withParams('date_to', 'Date to d/m/Y'),
                ];
            })
            ->setFiltering(function (array $filters, Where $where) {
                if (isset($filters['user_id']) && $filters['user_id']) {
                    $where->set(Where::equal('user_id', $filters['user_id']));
                }
                $where = DataManager::applyDateFilters($filters, $where);
                return $where;
            });

        $this->tabs = $this->createTabsManager()
            ->setTabs(
                Tab::withParams('Agents')->setRenderer(function () {
                    return $this->agents->build();
                }),
                Tab::withParams('Profits')->setRenderer(function () {
                    return $this->profits->build();
                })
            );

        $this->agents = $this->createManagedTable(
            AgentModel::class,
            ['ID', 'Platform', 'User id', 'Actions'],
            null,
            false
        )
            ->setDataMapper(function (ModelSet $agents) {
                $users = UserModel::select(
                    Where::in('id', array_unique($agents->column('user_id')))
                );
                return $agents->map(function (AgentModel $agent) use ($users) {
                    if (is_null($agent->deleted_at)) {
                        $deleted = false;
                        $actions = ActionSheet::withItems(
                            ActionSheetItem::withParams('Info')
                                ->onClick($this->info_agent_action->use(['agent_id' => $agent->id])),
                            ActionSheetItem::withParams('Edit')
                                ->onClick($this->edit_agent_action->use(['agent_id' => $agent->id])),
                            ActionSheetItem::withParams('Delete', ActionSheetItem::TYPE_DESTRUCTIVE)
                                ->onClick($this->delete_agent_action->use(['agent_id' => $agent->id]))
                        );
                    } else {
                        $deleted = true;
                        $actions = ActionSheet::withItems(
                            ActionSheetItem::withParams('Restore')
                                ->onClick($this->restore_action->use(['agent_id' => $agent->id]))
                        );
                    }

                    /** @var UserModel $user */
                    $user = $users->getItem($agent->user_id);

                    $row = TableRow::withParams(...[
                        $agent->id,
                        $agent->platform,
                        $user ? "{$user->email} ({$user->id})" : $agent->user_id,
                        $actions,
                    ]);

                    if ($deleted) {
                        $row->danger();
                    }

                    return $row;
                });
            })
            ->setSearchForm(function () {
                return [
                    Input::withParams('user', 'Enter user login/name/email'),
                ];
            })
            ->setFiltering(function (array $filters, Where $where) {
                $where = DataManager::applyUserFilters($filters, $where, ['user_id'], PLATFORM_BITCOINOVNET);
                return $where;
            });

        $this->info_agent_action = $this->createAction(function(ActionRequest $request) {
            /** @var AgentModel $agent */
            $agent = AgentModel::get($request->getParam('agent_id'));

            $promo_codes = BitcoinovnetModule::getOrCreatePromoCodes($agent);
            $info_list = InfoList::withItems(
                ...$promo_codes->map(function (AgentPromoCodeModel $promo_code) {
                    return InfoListItem::withParams("Promo code ({$promo_code->percent}%)", $promo_code->promo_code);
                })
            );

            return $this->showModal('Agent info', $info_list);
        });

        $this->add_agent_action = $this->createAction(function(ActionRequest $request) {
            return $this->showModal(
                'Add agent',
                $this->agent_form->build()
            );
        });

        $this->edit_agent_action = $this->createAction(function(ActionRequest $request) {
            return $this->showModal(
                'Edit agent',
                $this->agent_form->setParams($request->getParams())->build()
            );
        });

        $this->delete_agent_action = $this->createAction(function(ActionRequest $request) {
            Transaction::wrap(function () use ($request) {
                $agent = AgentModel::get($request->getParam('agent_id'));
                UserModel::get($agent->user_id)
                    ->removePermission(UserPermissionModel::AGENT_BITCOINOVNET)
                    ->save();
                $agent->delete();
            });

            return [
                $this->agents->getReloadAction([], []),
                $this->showToast('Agent deleted')
            ];
        })->setConfirm(true, 'Delete agent?');

        $this->restore_action = $this->createAction(function(ActionRequest $request) {
            Transaction::wrap(function () use ($request) {
                $agent = AgentModel::get($request->getParam('agent_id'));
                UserModel::get($agent->user_id)
                    ->addPermission(UserPermissionModel::AGENT_BITCOINOVNET)
                    ->save();
                $agent->restore();
            });

            return [
                $this->agents->getReloadAction([], []),
                $this->showToast('Agent restored')
            ];
        })->setConfirm(true, 'Restore agent?');

        $this->agent_form = $this->createFormManager()
            ->setItems(function ($params) {
                $agent_id = array_get_val($params, 'agent_id');
                $platform = AgentModel::PLATFORM_BITCOINOVNET;

                if ($agent_id) {
                    $agent = AgentModel::get($agent_id);
                    $platform = $agent->platform;
                }

                $inputs = [
                    Select::withParams('platform', 'Platform', [
                        AgentModel::PLATFORM_BITCOINOVNET => AgentModel::PLATFORM_BITCOINOVNET,
                    ], $platform, 'Platform'),
                ];

                if (is_null($agent_id)) {
                    $inputs[] = Input::withParams('user_id', 'User id', '', '', 'User id');
                }

                return $inputs;
            })
            ->onSubmit(function (ActionRequest $request) {
                $filters = [
                    'platform' => ['required', 'maxLen' => 32, 'minLen' => 2],
                ];
                $new = false;
                try {
                    $agent = AgentModel::get($request->getParam('agent_id'));
                } catch (InvalidKeyException $e) {
                    $new = true;
                    $agent = new AgentModel();
                    $filters['user_id'] = ['required', 'positive'];
                } catch (\Exception $e) {
                    return $this->showErrorToast($e->getMessage());
                }

                /**
                 * @var string $platform
                 * @var int $user_id
                 */
                extract($request->getValues($filters));

                if (isset($user_id)) {
                    $agent->user_id = $user_id;
                    UserModel::get($user_id)
                        ->addPermission(UserPermissionModel::AGENT_BITCOINOVNET)
                        ->save();
                }
                $agent->platform = $platform;
                $agent->save();

                return [
                    $this->agents->getReloadAction([], []),
                    $this->closeModal(),
                    $this->showToast($new ? 'Agent added' : 'Agent edited')
                ];
            });
    }

    public function build() {
        if ($this->getAdmin()->isAdmin()) {
            $this->buildAdmin();
        } elseif($this->getAdmin()->hasPermission(UserPermissionModel::AGENT_BITCOINOVNET)) {
            $this->buildAgent();
        }
    }

    private function buildAdmin() {
        $button = Button::withParams('Add agent')->onClick($this->add_agent_action);

        $this->layout->push(Block::withParams('Actions', $button));
        $this->layout->push(Block::withParams('Main info', $this->tabs->build()));
    }

    private function buildAgent() {
        try {
            $agent = AgentModel::first(Where::and()
                ->set(Where::equal('user_id', $this->getAdmin()->id))
                ->set(Where::equal('platform', AgentModel::PLATFORM_BITCOINOVNET))
            );
        } catch (\Exception $e) {
            $this->layout->push(Text::withParams('Access denied'));
            return;
        }

        $promo_codes = BitcoinovnetModule::getOrCreatePromoCodes($agent);
        $info_list = InfoList::withItems(
            ...$promo_codes->map(function (AgentPromoCodeModel $promo_code) {
                return InfoListItem::withParams("Promo code ({$promo_code->percent}%)", $promo_code->promo_code);
            })
        );

        $this->layout->push(Block::withParams('Info', $info_list));
        $this->layout->push(Block::withParams('Profitts', $this->agent_profits->setFilters(['user_id' => $agent->user_id])->build()));
    }
}
