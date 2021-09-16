<?php

namespace Admin\modules;

use Admin\common\SearchFilters;
use Admin\common\UserLogCommon;
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
use Admin\layout\Clipboard;
use Admin\layout\ClientAction;
use Admin\layout\DropDown;
use Admin\layout\Group;
use Admin\layout\InfoList;
use Admin\layout\InfoListItem;
use Admin\layout\Input;
use Admin\layout\Layout;
use Admin\layout\NumberFormat;
use Admin\layout\Select;
use Admin\layout\Tab;
use Admin\layout\TableRow;
use Admin\layout\Time;
use Admin\layout\Title;
use Admin\layout\Toast;
use Admin\layout\Wrapper;
use Admin\serializers\UserLogSerializer;
use Core\Services\Log\UserRoleLog;
use Db\Model\Field\Exception\InvalidValueException;
use Core\Services\Redis\RedisAdapter;
use Db\Model\Field\PasswordFiled;
use Db\Model\Field\RandomHashField;
use Db\Model\Model;
use Db\Model\ModelSet;
use Db\Transaction;
use Db\Where;
use Models\BalanceModel;
use Models\BannedUserModel;
use Models\DepositModel;
use Models\ExOrderModel;
use Models\InternalTransactionModel;
use Models\Logs\AssignUserPermissionLog;
use Models\Logs\AssignUserRoleLog;
use Models\PaymentModel;
use Models\PlanModel;
use Models\ProfitModel;
use Models\RefillModel;
use Models\SwapModel;
use Models\TransactionModel;
use Models\TransferModel;
use Models\UserBalanceHistoryModel;
use Models\UserLogModel;
use Models\UserModel;
use Models\UserPermissionModel;
use Models\UserRoleModel;
use Models\WalletModel;
use Models\WithdrawalModel;
use Modules\BalanceHistoryModule;
use Modules\BalanceModule;
use Modules\InvestmentModule;
use Modules\PartnerModule;
use Modules\ProfileModule;
use Modules\UserLogModule;
use Modules\WalletModule;
use Serializers\InvestmentSerializer;
use Serializers\PartnerSerializer;

class Users extends PageContainer {

    /* @var DataManager */
    private $users_table;

    /* @var FormManager */
    private $add_form;

    /** @var TabsManager */
    private $tabs;

    /* @var FormManager */
    private $edit_form;

    /** @var Action */
    private $add_action;

    /** @var Action */
    private $edit_action;

    /** @var Action */
    private $sign_in_action;

    /** @var Action */
    private $delete_action;

    /** @var Action */
    private $restore_action;

    /** @var Action */
    private $show_action;

    /** @var Action */
    private $reset_secret_key_action;

    /** @var Action */
    private $reset_ga_action;

    /** @var Action */
    private $reset_password_action;

    /** @var Action */
    private $partner_detail_action;

    /** @var ModelSet */
    private $all_roles;

    /** @var ModelSet */
    private $all_permissions;

    /** @var DataManager */
    private $user_investments;

    /** @var DataManager */
    private $user_payments;

    /** @var DataManager */
    private $user_profits;

    /** @var DataManager */
    private $user_partners;

    /** @var DataManager */
    private $user_partner_deposits;

    /** @var DataManager */
    private $user_logs;

    /** @var DataManager */
    private $user_referral_withdrawals;

    /** @var DataManager */
    private $user_transactions;

    /** @var DataManager */
    private $user_transfers;

    /** @var DataManager */
    private $user_exchnge;

    /** @var DataManager */
    private $user_refill_and_withdrawal;

    /** @var DataManager */
    private $user_swap_orders;

    /** @var FormManager */
    private $delete_form;

    public function registerActions() {
        $this->createShowAction();
        $this->createResetGaAction();
        $this->createResetPasswordAction();
        $this->createResetSecretKeyAction();
        $this->createAddAction();
        $this->createEditAction();
        $this->createDeleteAction();
        $this->createRestoreAction();
        $this->createPartnerDetailAction();
        $this->createDeleteForm();
        $this->createSignInAction();

        $this->user_investments = $this->userInvestments();
        $this->user_payments = $this->userPayments();
        $this->user_profits = $this->userProfits();
        $this->user_logs = $this->userLogs();
        $this->user_partners = $this->userPartners();
        $this->user_partner_deposits = $this->userPartnerDeposits();
        $this->user_referral_withdrawals = $this->userReferralWithdrawals();
        $this->user_transactions = $this->userTransactions();
        $this->user_transfers = $this->userTransfers();
        $this->user_exchnge = $this->userExchange();
        $this->user_refill_and_withdrawal = $this->userRefillAndWithdrawal();
        $this->user_swap_orders = $this->userSwapOrders();

        $this->tabs = $this->createUserInfoTabs();

        $roles = [];
        $permissions = [];
        $this->all_roles = getRoles();
        $this->all_permissions = getPermissions();

        foreach ($this->all_roles as $role_model) {
            /** @var UserRoleModel $role_model */
            $roles[$role_model->role_name] = $role_model->role_name;
        }
        foreach ($this->all_permissions as $permission_model) {
            /** @var UserPermissionModel $permission_model */
            $permissions[$permission_model->name] = $permission_model->name;
        }

        $this->edit_form = $this->createForm($roles, $permissions, true);
        $this->add_form = $this->createForm($roles, $permissions);

        $this->users_table = $this->createUserTable();
    }

    public function build() {
        $button = Button::withParams('Add user')
            ->onClick($this->add_action);

        $this->layout->push(Block::withParams('Actions', $button));
        $this->layout->push(Block::withParams('Users', $this->users_table->build()));
    }

    public function createUserTable() {
        return $this
            ->createManagedTable(UserModel::class, ['Id', 'Email', 'Login', 'Fullname', 'Register date', 'Actions'], null, false)
            ->setDataMapper(function (ModelSet $users)  {
                return $users->map(function (UserModel $user) {
                    if (!$user->isBanned()) {
                        $delete_or_restore_action = Button::withParams('Ban')->onClick($this->delete_action->use(['user_id' => $user->id]));
                    } else {
                        $delete_or_restore_action = ActionSheetItem::withParams('Unban')->onClick($this->restore_action->use([
                            'id' => $user->id,
                        ]));
                    }

                    $style = $user->isBanned() ? TableRow::STYLE_DANGER : TableRow::STYLE_DEFAULT;

                    $items = [
                        $user->id,
                        $user->email,
                        $user->login ?? '',
                        $user->fullName(),
                        Time::withParams($user->created_at_timestamp),
                        ActionSheet::withItems(
                            ActionSheetItem::withParams('Show')->onClick($this->show_action->use([
                                'id' => $user->id,
                            ])),
                            ActionSheetItem::withParams('Edit')->onClick($this->edit_action->use([
                                'id' => $user->id,
                            ])),
                            ActionSheetItem::withParams('Sign in as user')->onClick($this->sign_in_action->use([
                                'id' => $user->id,
                            ])),
                            ActionSheetItem::withParams('Reset secret key')->onClick($this->reset_secret_key_action->use([
                                'id' => $user->id,
                            ])),
                            ActionSheetItem::withParams('Reset GA')->onClick($this->reset_ga_action->use([
                                'id' => $user->id,
                            ])),
                            ActionSheetItem::withParams('Reset password')->onClick($this->reset_password_action->use([
                                'id' => $user->id,
                            ])),
                            $delete_or_restore_action
                        ),
                    ];

                    return TableRow::withParams(...$items)->setStyle($style);
                });
            })
            ->setSearchForm(function () {
                $options = [];
                $options[] = ['all', 'All roles'];
                foreach ($this->all_roles as $role) {
                    /** @var UserRoleModel $role */
                    $options[] = [$role->role_name, $role->role_name];
                }
                return [
                    Input::withParams('id', 'Enter User id'),
                    Input::withParams('user', 'Enter user login/email/name'),
                    DropDown::withParams('role', 'All roles', $options),
                    DropDown::withParams('banned', 'All users', [
                        ['all', 'All users'],
                        ['not_banned', 'Not banned'],
                        ['banned', 'Banned'],
                    ]),
                ];
            })
            ->setFiltering(function (array $filters, Where $where) {
                if (isset($filters['id'])) {
                    $user_id = positive($filters['id']);
                    if ($user_id > 0) {
                        $where->set('id', Where::OperatorEq, $user_id);
                    }
                }
                if (isset($filters['banned'])) {
                    if ($filters['banned'] === 'not_banned') {
                        $where->set('ban_id', Where::OperatorIs, null);
                    }
                    if ($filters['banned'] === 'banned') {
                        $where->set('ban_id', Where::OperatorIsNot, null);
                    }
                }
                if (isset($filters['role'])) {
                    if ($filters['role'] !== 'all') {
                        $where->set('roles', Where::OperatorLike, '%' . $filters['role'] . '%');
                    }
                }
                $where = SearchFilters::user($filters, $where, 'id');
                return $where;
            })
            ->setOrderBy(['id' => 'DESC']);
    }

    public function createForm(array $roles, array $permissions, $is_edit_form = false): FormManager {
        return $this->createFormManager()
            ->setItems(function ($params) use ($roles, $permissions, $is_edit_form) {
                $id = array_get_val($params, 'id');
                $user = !is_null($id) ? UserModel::get($id) : new UserModel();

                $default_roles = [];
                $default_permissions = [];
                $email = is_null($id) ? '' : $user->email;
                $login = is_null($id) ? '' : ($user->login ?? '');
                $first_name = is_null($id) ? '' : ($user->first_name ?: '');
                $last_name = is_null($id) ? '' : ($user->last_name ?: '');
                $refer = is_null($id) ? '' : ($user->refer ?: '');

                if (!is_null($id)) {
                    $default_roles = $user->rolesAsArray();
                    $default_permissions = $user->permissionsAsArray();
                }

                $inputs = [
                    Input::withParams('first_name', 'First name', $first_name),
                    Input::withParams('last_name', 'Last name',  $last_name),
                    Input::withParams('email', 'Email', $email),
                    Input::withParams('login', 'Login', $login),
                ];

                if (!$is_edit_form) {
                    $inputs[] = Input::withParams('password', 'Password');
                }

                $inputs = array_merge($inputs, [
                    Input::withParams('refer', 'Referral ID', $refer),
                    Select::withParams('roles', 'Roles', $roles, $default_roles, 'Roles')
                        ->setMultiple(true),
                    Select::withParams('permissions', 'Permissions', $permissions, $default_permissions, 'Permissions')
                        ->setMultiple(true),
                ]);

                return $inputs;
            })
            ->onSubmit(function (ActionRequest $request) {
                $params = $request->getParams();
                $id = array_get_val($params, 'id', null);

                $first_name = $request->getValue('first_name');
                $last_name = $request->getValue('last_name');
                $login = $request->getValue('login', ['username']);

                $filters = [
                    'email' => ['required', 'email'],
                    'roles' => ['required', 'json'],
                    'permissions' => ['required', 'json'],
                ];

                if (is_null($id)) {
                    $filters['password'] = ['required', 'minLen' => 6];
                }

                $values = $request->getValues($filters);
                try {
                    Transaction::wrap(function () use ($id, $values, $first_name, $last_name, $login) {
                        $user = $id ? UserModel::get($id) : new UserModel();
                        if (is_null($id)) {
                            $user->mail_hash = RandomHashField::init()->fill();
                            $user->ip = ipAddress();
                            $user->join_date = date('Y-m-d H:i:s');
                        }

                        $refer = null;

                        if ($refer_id = trim($values['refer'])) {
                            $refer = UserModel::get($refer_id);
                        }

                        $roles = array_filter($values['roles'], function ($role) {
                            return boolval($role);
                        });
                        $permissions = array_filter($values['permissions'], function ($role) {
                            return boolval($role);
                        });

                        if (!is_null($id)) {
                            UserRoleLog::log($user, $roles, $permissions, true, self::getAdmin());
                        }

                        $user->first_name = $first_name;
                        $user->last_name = $last_name;
                        $user->login = $login;
                        $user->email = $values['email'];
                        if ($refer) {
                            if ($user->existsInDatabase()) {
                                if ($user->id > $refer->id) {
                                    $user->refer = $refer->id;
                                } else {
                                    throw new InvalidValueException();
                                }
                            } else {
                                $user->refer = $refer->id;
                            }
                        } else {
                            $user->refer = null;
                        }
                        $user->roles = implode(',', $roles);
                        $user->permissions = implode(',', $permissions);
                        $user->platform = PLATFORM_FINDIRI;
                        $user->save();

                        if (is_null($id)) {
                            $this->addLogAfterCreateUser($user);
                        }
                    });
                } catch (InvalidValueException $e) {
                    return [
                        $this->closeModal(),
                        $this->showToast('Invalid referral ID', Toast::TYPE_ERROR),
                    ];
                }

                return [
                    $this->closeModal(),
                    $this->showToast('User saved'),
                    $this->users_table->getReloadAction($params, $request->getValues())
                ];
            }, true);
    }

    private function addLogAfterCreateUser(UserModel $user): void {
        foreach ($user->rolesAsArray() as $role) {
            UserLogModule::addLog(
                AssignUserRoleLog::ASSIGN_ROLE_ACTION,
                new AssignUserRoleLog([
                    'user_id' => $user->id,
                    'role_id' => $this->getRoleByName($role)->id,
                    'role_name' => $role
                ]),
                true,
                $this->getAdmin()
            );
        }
        foreach ($user->permissionsAsArray() as $permission) {
            UserLogModule::addLog(
                AssignUserPermissionLog::ASSIGN_PERMISSION_ACTION,
                new AssignUserPermissionLog([
                    'user_id' => $user->id,
                    'permission_id' => $this->getPermissionByName($permission)->id,
                    'permission_name' => $permission
                ]),
                true,
                $this->getAdmin()
            );
        }
    }

    public function getRoleByName(string $role_name): ?UserRoleModel {
        $result = null;
        foreach ($this->all_roles as $role) {
            /** @var UserRoleModel $role */
            if ($role_name == $role->role_name) {
                $result = $role;
                break;
            }
        }

        return $result;
    }

    public function getPermissionByName(string $permission_name): ?UserPermissionModel {
        $result = null;
        foreach ($this->all_permissions as $permission) {
            /** @var UserPermissionModel $role */
            if ($permission_name == $permission->name) {
                $result = $permission;
                break;
            }
        }

        return $result;
    }

    private function createShowAction() {
        $this->show_action = $this->createAction(function (ActionRequest $request) {
            $user_id = $request->getParam('id');
            $this->tabs->setParams(['user_id' => $user_id]);

            $user = UserModel::get($user_id);
            return $this->showModal(
                'Информация о пользователе',
                $this->createUserInfoMain($user),
                $this->tabs->build()
            );
        });
    }

    private function createPartnerDetailAction() {
        $this->partner_detail_action = $this->createAction(function (ActionRequest $request) {
            $user_id = $request->getParam('user_id');
            $partner_id = $request->getParam('partner_id');

            $user = UserModel::get($user_id);
            /* @var UserModel|null $partner */
            $partner = UserModel::get($partner_id);

            $level = PartnerModule::getLevel($user);
            if (is_null($partner)) {
                return $this->showToast('Access denied, partner not found', Toast::TYPE_ERROR);
            }

            $refers = explode(',', $partner->refer);
            if ($partner->representative_id != $user->id && intval($refers[0]) != $user->id) {
                return $this->showToast('Access denied, partner is not a representative of this user', Toast::TYPE_ERROR);
            }

            $profits = ProfitModel::queryBuilder()
                ->columns([
                    'SUM(amount)' => 'total',
                    'currency',
                ], true)
                ->where(Where::and()
                    ->set('user_id', Where::OperatorEq, $user->id)
                    ->set('type', Where::OperatorEq, $level === 'representative' ? 'agent_profit' : 'referral_profit')
                    ->set('target_id', Where::OperatorEq, $partner->id)
                )->groupBy(['currency'])
                ->select();

            $profits_map = [];
            foreach ($profits as $profit) {
                $profits_map[$profit['currency']] = $profit;
            }

            $profits_result = [];
            foreach (array_keys(blockchain_currencies()) as $currency) {
                if (isset($profits_map[$currency])) {
                    $amount = $profits_map[$currency]['total'];
                } else {
                    $amount = 0;
                }
                $profits_result[] = PartnerSerializer::currencyProfitItem($currency, $amount);
            }

            $items = [];
            $profit_list_items = [];
            foreach ($profits_result as $profit) {
                $profit_list_items[] = InfoListItem::withParams(
                    strtoupper($profit['currency']),
                    NumberFormat::withParams(floatval($profit['amount']), '', ['hidden_currency' => true]));
            }
            if (!empty($profit_list_items)) {
                $items[] = InfoList::withItems(...$profit_list_items);
            }

            if ($level === 'agent') {
                $items[] = $this->user_partner_deposits->setFilters(['partner_id' => $partner->id])->build();
            }

            if (empty($items)) {
                $items[] = Title::withParams('Empty, there is no partner');
            }

            return $this->showModal($partner->login, ...$items);
        });

    }

    private function createEditAction() {
        $this->edit_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal('Edit user', $this->edit_form->setParams($request->getParams())->build());
        });
    }

    private function createSignInAction() {
        $this->sign_in_action = $this->createAction(function (ActionRequest $request) {
            $user_id = (int) $request->getParam('id');
            $user = UserModel::get($user_id);

            if ($user->isAdmin()) {
                return $this->showErrorToast('Пользователь администратор');
            }

            $token = getRandomString(32);
            if (!RedisAdapter::shared()->set('admin_tmp_token_' . $token, $user_id, 3600)) {
                return $this->showErrorToast('Неудалось сохранить токен');
            }

            return ClientAction::withParams(ClientAction::ACTION_SIGN_IN, [
                'token' => $token,
            ]);
        });
    }

    private function createAddAction() {
        $this->add_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal('Add user', $this->add_form->setParams($request->getParams())->build());
        });
    }

    private function createDeleteAction() {
        $this->delete_action = $this->createAction(function(ActionRequest $request) {
            return $this->showModal('Ban user', $this->delete_form->setParams($request->getParams())->build());
        });
    }

    private function createDeleteForm() {
        $this->delete_form = $this->createFormManager()
            ->setItems(function($params) {
                return [
                    Input::withParams('reason', 'Enter reason')
                ];
            })
            ->onSubmit(function(ActionRequest $request) {
                $user_id = $request->getParam('user_id');
                $reason = $request->getValue('reason');
                /** @var UserModel $user */
                $user = UserModel::get($user_id);
                if ($user->isBanned()) {
                    return [
                        $this->showToast('User already banned', Toast::TYPE_ERROR),
                        $this->closeModal(),
                    ];
                }
                Transaction::wrap(function() use ($user, $reason, $request) {
                    $ban_user = new BannedUserModel();
                    $ban_user->reason = $reason;
                    $ban_user->user_id = $user->id;
                    $ban_user->admin_id = $this->getAdmin()->id;
                    $ban_user->save();
                    $user->ban_id = $ban_user->id;
                    $user->save();
                });

                return [
                    $this->showToast('User has been deleted'),
                    $this->closeModal(),
                    $this->users_table->getReloadAction($request->getParams(), $request->getValues())
                ];
            }, true);
    }

    private function createRestoreAction() {
        $this->restore_action = $this->createAction(function (ActionRequest $request) {
            /* @var UserModel $item */
            $item = UserModel::get($request->getParam('id'), false);
            $item->ban_id = null;
            $item->save();

            return [
                $this->showToast('User has been restored'),
                $this->users_table->getReloadAction($request->getParams(), $request->getValues())
            ];
        })->setConfirm(true, 'Restore user?')->needGa();
    }

    private function createResetSecretKeyAction() {
        $this->reset_secret_key_action = $this->createAction(function (ActionRequest $request) {
            /* @var UserModel $item */
            $item = UserModel::get($request->getParam('id'));
            $item->resetSecretKey();

            return [
                $this->showToast('User deleted'),
                $this->users_table->getReloadAction($request->getParams(), $request->getValues())
            ];
        })->setConfirm(true, 'Reset user secret key?', true)->needGa();
    }

    private function createResetPasswordAction() {
        $this->reset_password_action = $this->createAction(function (ActionRequest $request) {
            /* @var UserModel $item */
            $item = UserModel::get($request->getParam('id'));
            ProfileModule::resetPasswordWebsite($item);
            return [
                $this->showToast('Reset user password. Send email restore link.'),
                $this->users_table->getReloadAction($request->getParams(), $request->getValues())
            ];
        })->setConfirm(true, 'Reset user ga?', true)->needGa();
    }

    private function createResetGaAction() {
        $this->reset_ga_action = $this->createAction(function (ActionRequest $request) {
            /* @var UserModel $item */
            $item = UserModel::get($request->getParam('id'));
            $item->disable2fa();

            return [
                $this->showToast('Reset user ga'),
                $this->users_table->getReloadAction($request->getParams(), $request->getValues())
            ];
        })->setConfirm(true, 'Reset user ga?', true)->needGa();
    }

    private function createUserInfoMain(UserModel $user): Layout {
        $banned_user = null;
        $admin = null;
        if ($user->isBanned()) {
            /** @var BannedUserModel|null $banned_user */
            $banned_user = BannedUserModel::first(Where::equal('id', $user->ban_id));
            if ($banned_user && $banned_user->admin_id) {
                /** @var UserModel $admin */
                $admin = UserModel::get($banned_user->admin_id);
            }
        }

        $items = [];
        $items[] = InfoList::withItems(
            InfoListItem::withParams('ID', $user->id),
            InfoListItem::withParams('Login', $user->login),
            InfoListItem::withParams('Email', $user->email),
            InfoListItem::withParams('Phone', $user->phone_code . ' ' . $user->phone_number),
            InfoListItem::withParams('Refer', $user->refer),
            InfoListItem::withParams('User', $user->fullName()),
            InfoListItem::withParams('Joined', $user->join_date)
        );
        $items[] = InfoList::withItems(
            InfoListItem::withParams('Secret key', $user->isSecretEnabled() ? 'true' : 'false'),
            InfoListItem::withParams('2FA', $user->is2FaEnabled() ?  'true' : 'false'),
            InfoListItem::withParams('Verification', UserModel::USER_VERIFICATION_MAP[$user->verification]),
            InfoListItem::withParams('Withdrawal disabled', $user->isWithdrawalDisabled() ?  'true' : 'false'),
            InfoListItem::withParams('Account banned', $user->isBanned()  ? 'true' : 'false'),
            InfoListItem::withParams('Banned by', $user->isBanned() ? $banned_user && $admin ? $admin->fullName() : '' : 'false'),
            InfoListItem::withParams('Reason', $user->isBanned() ? $banned_user ? $banned_user->reason : '' : 'false')
        );
        return Group::withItems(...$items);
    }

    private function createUserInfoTabs() {
        return $this->createTabsManager()->setTabs(
            Tab::withParams('Main')->setRenderer(function ($params) {
                $user_id = (int) $params['user_id'];
                $items = [];
                $items[] = Wrapper::withParams('Wallets', $this->wallets($user_id));
                $items[] = Wrapper::withParams('Referral balances', $this->referrerBalance($user_id));
                $items[] = Wrapper::withParams('Exchange balances', $this->exchangeBalance($user_id));
                $items[] = Wrapper::withParams('Fiat balances', $this->fiatBalance($user_id));

                return [Group::withItems(...$items), Wrapper::withParams('Invest balances', $this->investWallets($user_id))];
            }),
            Tab::withParams('Investments')->setRenderer(function ($params) {
                $user_id = (int) $params['user_id'];
                $items[] = Wrapper::withParams('Invest balances', $this->investWallets($user_id));
                $items[] = $this->user_investments->setFilters(['user_id' => $user_id])->build();
                return $items;
            }),
            Tab::withParams('Payments')->setRenderer(function ($params) {
                $user_id = (int) $params['user_id'];
                return $this->user_payments->setFilters(['user_id' => $user_id])->build();
            }),
            Tab::withParams('Profit')->setRenderer(function ($params) {
                $user_id = (int) $params['user_id'];
                return $this->user_profits->setFilters(['user_id' => $user_id])->build();
            }),
            Tab::withParams('Logs')->setRenderer(function ($params) {
                $user_id = (int) $params['user_id'];
                return $this->user_logs->setFilters(['user_id' => $user_id])->build();
            }),
            Tab::withParams('Partners')->setRenderer(function ($params) {
                $user_id = (int) $params['user_id'];
                return $this->user_partners->setFilters(['user_id' => $user_id])->build();
            }),
            Tab::withParams('Withdrawal (referral)')->setRenderer(function ($params) {
                $user_id = (int) $params['user_id'];
                return $this->user_referral_withdrawals->setFilters(['user_id' => $user_id])->build();
            }),
            Tab::withParams('Transactions')->setRenderer(function ($params) {
                $user_id = (int) $params['user_id'];

                $currencies = TransactionModel::queryBuilder()
                    ->columns(['currency'], true)
                    ->where(Where::equal('user_id', $user_id))
                    ->groupBy('currency')
                    ->select();

                $move_founds = $this->receiveAndSendInfoTransactions($user_id, $currencies);
                $items[] = InfoList::withItems(...$move_founds);

                $items[] = $this->user_transactions->setFilters(['user_id' => $user_id])->build();

                return $items;
            }),
            Tab::withParams('Transfers')->setRenderer(function ($params) {
                $user_id = (int) $params['user_id'];
                $currencies = TransferModel::queryBuilder()
                    ->columns(['currency'], true)
                    ->where(
                        Where::or()
                            ->set('from_user_id', Where::OperatorEq, $user_id)
                            ->set('to_user_id', Where::OperatorEq, $user_id)
                    )
                    ->groupBy('currency')
                    ->select();

                $move_founds = $this->receiveAndSendInfoTransfers($user_id, $currencies);

                $items = [];
                $items[] = InfoList::withItems(...$move_founds);
                $items[] =  $this->user_transfers->setFilters(['user_id' => $user_id])->build();
                return $items;
            }),
            Tab::withParams('Verification')->setRenderer(function ($params) {
                return Title::withParams('In developing');
            }),
            Tab::withParams('Exchange')->setRenderer(function ($params) {
                $user_id = (int) $params['user_id'];
                $items = [];
                $items[] = $this->exchangeBalance($user_id);
                $items[] = $this->user_exchnge->setFilters(['user_id' => $user_id])->build();
                return $items;
            }),
            Tab::withParams('Fiat wallets')->setRenderer(function ($params) {
                $user_id = (int) $params['user_id'];

                $items = [];
                $items[] = $this->fiatBalance($user_id);
                $refill_and_withdrawal_list = $this->refillAndWithdrawalTotalAmount($user_id);
                if ($refill_and_withdrawal_list !== null) {
                    $items = array_merge($items, $refill_and_withdrawal_list);
                }

                $items[] = $this->user_refill_and_withdrawal
                    ->setFilters(['user_id' => $user_id])
                    ->build();

                return $items;
            }),
            Tab::withParams('Swap orders')->setRenderer(function ($params) {
                $user_id = (int) $params['user_id'];
                return $this->user_swap_orders->setFilters(['user_id' => $user_id])->build();
            })
        );
    }

    private function investWallets(int $user_id): Group {
        [$wallets, $payments_result, $deposits_result] = InvestmentModule::investmentData($user_id);

        $items = [];
        foreach ($payments_result as $currency => $payment) {
            if ($currency === CURRENCY_FNDR) {
                continue;
            }
            $items[] = InfoList::withItems(
                InfoListItem::withParams('Currency', $currency),
                InfoListItem::withParams(
                    'Invest amount',
                    NumberFormat::withParams(
                        array_get_val($payment, 'invested_amount', 0),
                        $currency, ['hidden_currency' => true]
                    )
                ),
                InfoListItem::withParams(
                    'Profit',
                    NumberFormat::withParams(
                        array_get_val($payment, 'profit', 0),
                        $currency, ['hidden_currency' => true]
                    )
                ),
                InfoListItem::withParams(
                    'Available',
                    NumberFormat::withParams(
                        array_get_val($payment, 'available', 0),
                        $currency, ['hidden_currency' => true]
                    )
                ),
                InfoListItem::withParams(
                    'Total amount',
                    NumberFormat::withParams(
                        array_get_val($payment, 'total_invested_amount', 0),
                        $currency, ['hidden_currency' => true]
                    )
                ),
                InfoListItem::withParams(
                    'Total profit',
                    NumberFormat::withParams(
                        array_get_val($payment, 'total_profit', 0),
                        $currency, ['hidden_currency' => true]
                    )
                ),
                InfoListItem::withParams(
                    'Total paid',
                    NumberFormat::withParams(
                        array_get_val($payment, 'total_paid', 0),
                        $currency, ['hidden_currency' => true]
                    )
                )
            );
        }

        return Group::withItems(...$items);
    }

    private function wallets(int $user_id): InfoList {
        $wallets = WalletModel::select(Where::equal('user_id', $user_id));
        $items = [];
        foreach ($wallets as $wallet) {
            /** @var WalletModel $wallet */
            $items[] = InfoListItem::withParams(
                strtoupper($wallet->currency) . ' : ' . $wallet->address ?? '',
                NumberFormat::withParams($wallet->amount, '', ['hidden_currency' => true]));
        }

        return InfoList::withItems(...$items);
    }

    private function referrerBalance(int $user_id): InfoList {
        return $this->getBalanceList(BalanceModule::getBalances($user_id, BalanceModel::CATEGORY_PARTNERS));
    }

    private function exchangeBalance(int $user_id): InfoList {
        return $this->getBalanceList(BalanceModule::getBalances($user_id, BalanceModel::CATEGORY_EXCHANGE));
    }

    private function fiatBalance(int $user_id): InfoList {
        return $this->getBalanceList(BalanceModule::getBalances($user_id, BalanceModel::CATEGORY_FIAT));
    }

    private function getBalanceList(ModelSet $balances): InfoList {
        $items = [];
        if ($balances->isEmpty()) {
            return InfoList::withItems(InfoListItem::withParams('Empty', 'No one balance'));
        }
        foreach ($balances as $balance) {
            /** @var BalanceModel $balance */
            $items[] = InfoListItem::withParams(
                strtoupper($balance->currency),
                NumberFormat::withParams($balance->amount, $balance->currency, ['hidden_currency' => true])
            );
        }

        return InfoList::withItems(...$items);
    }

    private function userInvestments(): DataManager {
        return $this->createManagedTable(DepositModel::class,
            ['ID', 'Status|Type|Rate', 'Date start', 'Operation', 'Amount', 'Days', 'Profit']
        )
            ->setDataMapper(function (ModelSet $deposits)  {
                $plan_ids = $deposits->column('plan');
                $plans = PlanModel::select(Where::in('id', array_unique($plan_ids)), false);

                return $deposits->map(function (DepositModel $deposit) use ($plans) {
                    /* @var PlanModel $plan */
                    $plan = $plans->getItem($deposit->plan);
                    $result = InvestmentSerializer::listItem($deposit, $plan);

                    return [
                        $result['id'],
                        $result['status'] . ' | ' . $result['type'] . ' | ' . $result['description'],
                        $deposit->date_start,
                        $result['operation'],
                        NumberFormat::withParams(floatval($result['amount']), $result['currency']),
                        $result['passed_days'] . '/' . $result['days'],
                        NumberFormat::withParams($result['profit'], $result['currency']),
                    ];
                });
            })
            ->setFiltering(function (array $filters, Where $where) {
                $user_id = $filters['user_id'];
                $where->set('user_id', Where::OperatorEq, $user_id);
                return $where;
            });
    }

    private function userPayments(): DataManager {
        return $this->createManagedTable(
            PaymentModel::class,
            ['ID', 'Amount', 'Status', 'Date']
        )
            ->setDataMapper(function (ModelSet $payments)  {
                return $payments->map(function (PaymentModel $payment) {
                    $wallet = WalletModel::get($payment->wallet_id);
                    return [
                        $payment->id,
                        NumberFormat::withParams($payment->amount, $wallet->currency),
                        $payment->status,
                        $this->getDateByModel($payment)
                    ];
                });
            })
            ->setFiltering(function (array $filters, Where $where) {
                $user_id = $filters['user_id'];
                $where->set('user_id', Where::OperatorEq, $user_id);
                return $where;
            });
    }

    private function userProfits(): DataManager {
        return $this->createManagedTable(
            ProfitModel::class,
            ['ID', 'Amount', 'Operation', 'Дата']
        )
            ->setDataMapper(function (ModelSet $profits)  {
                return $profits->map(function (ProfitModel $profit) {
                    return [
                        $profit->id,
                        NumberFormat::withParams($profit->amount, $profit->currency),
                        $profit->type,
                        $this->getDateByModel($profit)
                    ];
                });
            })
            ->setSearchForm(function () {
                return [
                    DropDown::withParams('profit_type', 'Select profit type', [
                        ['all', 'All profits'],
                        [ProfitModel::TYPE_REFERRAL_PROFIT, 'Referral profit'],
                        [ProfitModel::TYPE_INVEST_PROFIT, 'Invest profit'],
                        [ProfitModel::TYPE_REINVEST_PROFIT, 'Reinvest profit'],
                        [ProfitModel::TYPE_TOKEN_PROFIT, 'Token profit'],
                        [ProfitModel::TYPE_AGENT_PROFIT, 'Agent profit'],
                        [ProfitModel::TYPE_POOL_PROFIT, 'Pool profit'],
                        [ProfitModel::TYPE_RETURN_DEPOSIT, 'Return deposit'],
                        [ProfitModel::TYPE_SAVING_PROFIT, 'Saving profit'],
                    ])
                ];
            })
            ->setFiltering(function (array $filters, Where $where)  {
                $user_id = $filters['user_id'];
                $where->set('user_id', Where::OperatorEq, $user_id);
                if (isset($filters['profit_type']) && $filters['profit_type'] !== 'all') {
                    $where->set('type', Where::OperatorEq, $filters['profit_type']);
                }
                return $where;
            });
    }

    private function userLogs(): DataManager {
        return $this->createManagedTable(
            UserLogModel::class,
            ['Action', 'Date', 'Browser', 'Platform', 'IP']
        )
            ->setDataMapper(function (ModelSet $logs)  {
                return UserLogSerializer::userLogRows($logs);
            })
            ->setSearchForm(function () {
                $search_form = UserLogCommon::searchForm();
                array_shift($search_form);
                return $search_form;
            })
            ->setFiltering(function (array $filters, Where $where) {
                return UserLogCommon::dataFiltering($filters, $where);
            })
            ->setOrderBy(['id' => 'DESC']);
    }

    private function userPartners(): DataManager {
        return $this->createManagedTable(UserModel::class, ['ID', 'Login', 'Income', 'Date', 'Action'])
            ->setDataMapper(function (ModelSet $clients) {

                /** @var UserModel|null $user */
                $user = null;

                if (!$clients->isEmpty()) {
                    /** @var UserModel $client */
                    $client = $clients->first();
                    $user_id = (int) current(explode(',', $client->refer));
                    $user = UserModel::get($user_id);
                }

                if (is_null($user)) {
                    return [];
                }

                $profits = ProfitModel::queryBuilder()
                    ->columns([
                        'SUM(amount)' => 'total',
                        'currency',
                        'target_id'
                    ], true)
                    ->where(Where::and()
                        ->set('user_id', Where::OperatorEq, $user->id)
                        ->set('type', Where::OperatorEq, 'agent_profit')
                        ->set('target_id', Where::OperatorIN, $clients->column('id'))
                    )
                    ->groupBy(['currency', 'target_id'])
                    ->select();
                $profits_map = [];
                foreach ($profits as $profit) {
                    if (!isset($profits_map[$profit['target_id']])) {
                        $profits_map[$profit['target_id']] = [];
                    }

                    $profits_map[$profit['target_id']][] = $profit;
                }

                $deposits = DepositModel::select(Where::and()->set('user_id', Where::OperatorIN, $clients->column('id')));
                $deposits_map = [];
                /* @var \Models\DepositModel $deposit */
                foreach ($deposits as $deposit) {
                    if (isset($deposits_map[$deposit->user_id])) {
                        continue;
                    }
                    $deposits_map[$deposit->user_id] = WalletModule::getUsdPrice($deposit->currency) * ($deposit->amount * DepositModel::REFERRAL_PROFIT);
                }

                return $clients->map(function (UserModel $client) use ($user, $profits_map, $deposits_map) {
                    $level = PartnerModule::getLevel($user);
                    $profits = isset($profits_map[$client->id]) ? $profits_map[$client->id] : [];

                    $total_profit = 0;

                    if ($level === 'representative' || $level === 'agent') {
                        foreach ($profits as $profit) {
                            $total_profit += WalletModule::getUsdPrice($profit['currency']) * $profit['total'];
                        }
                    } else {
                        $total_profit = isset($deposits_map[$client->id]) ? $deposits_map[$client->id] : 0;
                    }

                    return [
                        $client->id,
                        $client->login,
                        $total_profit . ' USD',
                        $client->join_date,
                        Button::withParams('detail')->onClick($this->partner_detail_action->use([
                            'partner_id' => $client->id,
                            'user_id' => $user->id,
                        ])),
                    ];
                });
            })
            ->setOrderBy(['join_date' => 'DESC'])
            ->setFiltering(function (array $filters, Where $where) {
                $user_id = $filters['user_id'];
                $user = UserModel::get($user_id);
                $level = PartnerModule::getLevel($user);

                if ($level === 'representative') {
                    return Where::equal('representative_id', $user->id);
                }

                return Where::and()->set(Where::or()
                    ->set('refer', Where::OperatorEq, $user_id)
                    ->set('refer', Where::OperatorLike, "{$user_id},%")
                )
                    ->set('active', Where::OperatorEq, 1);
            });
    }

    private function userPartnerDeposits(): DataManager {
        return $this->createManagedTable(DepositModel::class, ['ID', 'Type', 'Invested', 'Profit', 'Agent Profit'])
            ->setDataMapper(function (ModelSet $deposits) {
                if ($deposits->isEmpty()) {
                    return [];
                }

                /** @var UserModel $partner */
                $partner = UserModel::get($deposits->first()->user_id);
                $user_id = (int) current(explode(',', $partner->refer));

                $profits = ProfitModel::queryBuilder()
                    ->columns([
                        'SUM(amount)' => 'total',
                        'deposit_id',
                    ], true)
                    ->where(Where::and()
                        ->set('user_id', Where::OperatorEq, $user_id)
                        ->set('type', Where::OperatorEq, 'referral_profit')
                        ->set('deposit_id', Where::OperatorIN, $deposits->column('id'))
                    )
                    ->groupBy(['deposit_id'])
                    ->select();

                $plans = PlanModel::select(Where::in('id', $deposits->column('plan')), false);

                $plans_map = [];
                /* @var PlanModel $plan */
                foreach ($plans as $plan) {
                    $plans_map[$plan->id] = $plan;
                }

                $profits_map = [];
                foreach ($profits as $profit) {
                    $profits_map[$profit['deposit_id']] = $profit['total'];
                }

                return $deposits->map(function (DepositModel $deposit) use ($profits_map, $plans_map) {
                    $agent_profit = isset($profits_map[$deposit->id]) ? $profits_map[$deposit->id] : 0;
                    $plan = $plans_map[$deposit->plan];

                    $item = InvestmentSerializer::listItem($deposit, $plan);
                    $item['agent_profit'] = (double) $agent_profit;
                    $currency = strtoupper($item['currency']);

                    return [
                        $item['id'],
                        $item['type'],
                        formatNum($item['percent'], 2) . '% ' . $item['description'],
                        NumberFormat::withParams(floatval($item['amount']), $item['currency']),
                        sprintf(
                            '%s %s %s/%s Days',
                            $item['profit'] . '%', $currency, $item['passed_days'], $item['days']
                        ),
                        $item['agent_profit'] . ' ' . $currency,
                    ];
                });
            })
            ->setFiltering(function (array $filters, Where $where) {
                $partner_id = $filters['partner_id'];
                return Where::equal('user_id', $partner_id);
            });
    }

    private function getDateByModel(Model $model) {
        $date = $model->created_at_timestamp ?:
            ($model->created_at
                ?
                \DateTime::createFromFormat('Y-m-d H:i:s', $model->created_at)->getTimestamp()
                : null
            );

        return $date ? Time::withParams($date) : 'NULL';
    }

    private function userReferralWithdrawals(): DataManager {
        return $this->createManagedTable(
            InternalTransactionModel::class,
            ['ID', 'Amount', 'Date'],
            Where::equal('from_category', InternalTransactionModel::CATEGORY_PARTNERS)
        )
            ->setDataMapper(function (ModelSet $transactions) {
                return $transactions->map(function(InternalTransactionModel $model){
                    return [
                        $model->id,
                        NumberFormat::withParams($model->amount, $model->currency),
                        $model->created_at_timestamp ? Time::withParams($model->created_at_timestamp) : 'NULL',
                    ];
                });
            })
            ->setFiltering(function (array $filters, Where $where) {
                $user_id = $filters['user_id'];
                return Where::and()
                    ->set($where)
                    ->set('user_id', Where::OperatorEq, $user_id);
            });
    }

    private function userTransactions(): DataManager {
        return $this
            ->createManagedTable(
                TransactionModel::class,
                ['ID', 'User', 'Txid', 'Status', 'Confirmations', 'Amount', 'Date']
            )
            ->setDataMapper(function (ModelSet $transactions) {
                if ($transactions->isEmpty()) {
                    return [];
                }

                $user = UserModel::get($transactions->first()->user_id);
                return $transactions->map(function (TransactionModel $transaction) use ($user) {
                    return [
                        $transaction->id,
                        $user->fullName(),
                        Clipboard::withParams($transaction->txid ?? '', 32) ,
                        $transaction->status,
                        $transaction->confirmations,
                        NumberFormat::withParams($transaction->amount, $transaction->currency),
                        $transaction->created_at_timestamp ? Time::withParams($transaction->created_at_timestamp) : ''
                    ];
                });
            })
            ->setFiltering(function (array $filters, Where $where) {
                $user_id = $filters['user_id'];
                return Where::equal('user_id', $user_id);
            });
    }

    private function userTransfers(): DataManager {
        return $this
            ->createManagedTable(TransferModel::class, ['ID', 'From ID', 'To ID', 'Amount', 'Date'])
            ->setDataMapper(function (ModelSet $transfers)  {
                $ids = array_unique(
                    array_merge(
                        $transfers->column('from_user_id'),
                        $transfers->column('to_user_id')
                    )
                );

                $users = UserModel::select(Where::in('id', $ids), false);
                return $transfers->map(function (TransferModel $transfer) use ($users) {
                    $user_from = $users->getItem($transfer->from_user_id);
                    $user_to = $users->getItem($transfer->to_user_id);
                    return [
                        $transfer->id,
                        ($user_from ? $user_from->login . ' ' : ' ') . "($transfer->from_user_id)",
                        ($user_to ? $user_to->login . ' ' : ' ') . "($transfer->to_user_id)",
                        NumberFormat::withParams($transfer->amount, $transfer->currency),
                        $this->getDateByModel($transfer)
                    ];
                });
            })
            ->setFiltering(function (array $filters, Where $where) {
                $user_id = $filters['user_id'];
                return  Where::or()
                    ->set('from_user_id', Where::OperatorEq, $user_id)
                    ->set('to_user_id', Where::OperatorEq, $user_id);
            });
    }

    private function userExchange(): DataManager {
        $headers = ['ID', 'Status', 'Side', 'Type', 'Amount', 'Price', 'Market', 'Filled', 'Date'];
        return $this
            ->createManagedTable(ExOrderModel::class, $headers)
            ->setDataMapper(function (ModelSet $models)  {
                return $models->map(function (ExOrderModel $order) {
                    return [
                        $order->id,
                        $order->status,
                        $order->action,
                        $order->type,
                        NumberFormat::withParams($order->amount, $order->primary_coin, ['hidden_currency' => true]),
                        NumberFormat::withParams($order->price, $order->secondary_coin, ['hidden_currency' => true]),
                        strtoupper($order->primary_coin) . '/' . strtoupper($order->secondary_coin),
                        formatNum(floatval($order->filled / $order->amount) * 100, 2) . '%',
                        $this->getDateByModel($order)
                    ];
                });
            })
            ->setFiltering(function (array $filters, Where $where) {
                $user_id = $filters['user_id'];
                return Where::equal('user_id', $user_id);
            });
    }

    private function userRefillAndWithdrawal(): DataManager {
        $headers = ['ID', 'Amount', 'Status', 'Type', 'Date'];
        return $this
            ->createManagedTable(
                UserBalanceHistoryModel::class,
                $headers,
                Where::in('operation', [UserBalanceHistoryModel::OPERATION_REFILL, UserBalanceHistoryModel::OPERATION_WITHDRAWAL])
            )
            ->setDataMapper(function (ModelSet $models)  {
                $objects_sets = BalanceHistoryModule::getObjectsByModelSet($models);
                extract($objects_sets);
                /**
                 * @var ModelSet $swaps
                 * @var ModelSet $refills
                 * @var ModelSet $withdrawals
                 * @var ModelSet $transactions
                 * @var ModelSet $transfers
                 * @var ModelSet $withdrawal_requests
                 * @var ModelSet $transfers_users
                 * @var ModelSet $bank_card_operation
                 */

                return $models->map(function (UserBalanceHistoryModel $transaction) use ($withdrawals, $refills) {
                    if ($transaction->operation == UserBalanceHistoryModel::OPERATION_REFILL) {
                        $status = 'done';
                        /** @var RefillModel $model */
                        $model = $refills->getItem($transaction->object_id);
                        $amount = $model ? $model->amount : $transaction->to_amount;
                        $currency = $model ? $model->currency : $transaction->to_currency;
                    } else {
                        /** @var WithdrawalModel $model */
                        $model = $withdrawals->getItem($transaction->object_id);
                        $status = $model ? UserBalanceHistoryModel::STATUSES_MAP[$model->status] : '';
                        $amount = $model ? $model->amount : $transaction->from_amount;
                        $currency = $model ? $model->currency : $transaction->from_currency;
                    }
                    return [
                        $transaction->object_id,
                        NumberFormat::withParams($amount, $currency),
                        $status,
                        UserBalanceHistoryModel::OPERATIONS_MAP[$transaction->operation],
                        Time::withParams($transaction->created_at_timestamp)
                    ];
                });
            })
            ->setFiltering(function(array $filters, Where $where) {
                $user_id = $filters['user_id'];
                return Where::and()
                    ->set(
                        'operation',
                        Where::OperatorIN,
                        [UserBalanceHistoryModel::OPERATION_REFILL, UserBalanceHistoryModel::OPERATION_WITHDRAWAL]
                    )
                    ->set(Where::or()
                        ->set('from_user_id', Where::OperatorEq, $user_id)
                        ->set('to_user_id', Where::OperatorEq, $user_id)
                    );
            })
            ->setOrderBy(['created_at_timestamp' => 'DESC']);
    }

    private function getBalanceByHistoryModel(UserBalanceHistoryModel $balance_history, ModelSet $balances): ?Model {
        /** @var BalanceModel|null $balance */
        return $balance_history->operation == UserBalanceHistoryModel::OPERATION_WITHDRAWAL ?
            $balances->getItem($balance_history->from_id) :
            $balances->getItem($balance_history->to_id);
    }

    private function refillAndWithdrawalTotalAmount(int $user_id): ?array {
        $withdrawals = WithdrawalModel::select(Where::and()
            ->set('user_id', Where::OperatorEq, $user_id)
            ->set('status', Where::OperatorEq, UserBalanceHistoryModel::STATUS_COMPLETED)
        );
        $refills = RefillModel::select(Where::and()
            ->set('user_id', Where::OperatorEq, $user_id)
        );

        $move_founds = [
            'withdrawals' => [],
            'refills' => [],
        ];

        foreach ($withdrawals as $withdrawal) {
            /** @var WithdrawalModel $withdrawal */
            if (!isset($move_founds['withdrawals'][$withdrawal->currency])) {
                $move_founds['withdrawals'][$withdrawal->currency] = $withdrawal->amount;
            } else {
                $move_founds['withdrawals'][$withdrawal->currency] += $withdrawal->amount;
            }
        }

        foreach ($refills as $refill) {
            /** @var RefillModel $refill */
            if (!isset($move_founds['refills'][$refill->currency])) {
                $move_founds['refills'][$refill->currency] = $refill->amount;
            } else {
                $move_founds['refills'][$refill->currency] += $refill->amount;
            }
        }

        $items = [];
        foreach ($move_founds as $type => $found) {
            $items[] = Title::withParams(strtoupper($type));
            $list_items = [];
            foreach ($found as $currency => $amount) {
                $list_items[] = InfoListItem::withParams(
                    strtoupper($currency),
                    NumberFormat::withParams($amount, '', ['hidden_currency' => true]));
            }
            $items[] = InfoList::withItems(...$list_items);
        }

        if (empty($items)) {
            return null;
        }

        return $items;
    }

    private function userSwapOrders(): DataManager {
        $headers = ['ID', 'Gave', 'Got', 'Rate', 'Status', 'Date'];
        return $this
            ->createManagedTable(SwapModel::class, $headers)
            ->setDataMapper(function (ModelSet $models)  {
                return $models->map(function (SwapModel $swap) {
                    $crypto_currencies = array_keys(currencies());

                    $from_currency_is_crypto = in_array($swap->from_currency, $crypto_currencies);
                    $course_currency = $from_currency_is_crypto ? $swap->to_currency : $swap->from_currency;
                    return [
                        $swap->id,
                        NumberFormat::withParams($swap->from_amount, $swap->from_currency),
                        NumberFormat::withParams($swap->to_amount, $swap->to_currency),
                        NumberFormat::withParams($swap->rate, $course_currency),
                        UserBalanceHistoryModel::STATUSES_MAP[$swap->status],
                        Time::withParams($swap->created_at_timestamp)
                    ];
                });
            })
            ->setFiltering(function(array $filters, Where $where) {
                $user_id = $filters['user_id'];
                return Where::and()
                    ->set('user_id', Where::OperatorEq, $user_id);
            });
    }

    private function receiveAndSendInfoTransfers(int $user_id, array $currencies): array {
        $move_founds = [];
        foreach ($currencies as $currency) {
            $currency = $currency['currency'];
            $receive = $this->getReceiveTransfers($user_id, $currency);
            $send = $this->getSendTransfers($user_id, $currency);

            $move_founds = array_merge(
                $move_founds,
                $this->calcTotalReceiveAndSendAmount($receive, $send, $currency)
            );
        }

        return $move_founds;
    }

    private function receiveAndSendInfoTransactions(int $user_id, array $currencies): array {
        $move_founds = [];
        foreach ($currencies as $currency) {
            $currency = $currency['currency'];
            $receive = $this->getReceiveTransactions($user_id, $currency);
            $send = $this->getSendTransactions($user_id, $currency);

            $move_founds = array_merge(
                $move_founds,
                $this->calcTotalReceiveAndSendAmount($receive, $send, $currency)
            );
        }

        return $move_founds;
    }

    private function calcTotalReceiveAndSendAmount(array $receive, array $send, string $currency): array {
        $receive_sum = empty($receive) ? 0 : array_get_val(current($receive), 'total', 0);
        $send_sum = empty($send) ? 0 : array_get_val(current($send), 'total', 0);

        $currency = strtoupper($currency);
        $receive_sum = floatval($receive_sum);
        $send_sum = floatval($send_sum);

        $move_founds[] = InfoListItem::withParams(
            'Receive',
            NumberFormat::withParams($receive_sum, $currency));
        $move_founds[] = InfoListItem::withParams(
            'Send',
            NumberFormat::withParams($send_sum, $currency));

        return $move_founds;
    }

    private function getReceiveTransactions(int $user_id, string $currency): array {
        return TransactionModel::queryBuilder()
            ->columns(['SUM(amount) as total', 'currency'], true)
            ->where(Where::and()
                ->set('user_id', Where::OperatorEq, $user_id)
                ->set('currency', Where::OperatorEq, $currency)
                ->set('category', Where::OperatorEq, TransactionModel::RECEIVE_CATEGORY)
            )
            ->groupBy('user_id')
            ->select();
    }

    private function getSendTransactions(int $user_id, string $currency): array {
        return TransactionModel::queryBuilder()
            ->columns(['SUM(amount) as total', 'currency'], true)
            ->where(Where::and()
                ->set('user_id', Where::OperatorEq, $user_id)
                ->set('currency', Where::OperatorEq, $currency)
                ->set('category', Where::OperatorEq, TransactionModel::SEND_CATEGORY)
            )
            ->groupBy('user_id')
            ->select();
    }

    private function getReceiveTransfers(int $user_id, string $currency): array {
        return TransferModel::queryBuilder()
            ->columns(['SUM(amount) as total', 'currency'], true)
            ->where(Where::and()
                ->set('to_user_id', Where::OperatorEq, $user_id)
                ->set('currency', Where::OperatorEq, $currency)
            )
            ->groupBy('to_user_id')
            ->select();
    }

    private function getSendTransfers(int $user_id, string $currency): array {
        return TransferModel::queryBuilder()
            ->columns(['SUM(amount) as total', 'currency'], true)
            ->where(Where::and()
                ->set('from_user_id', Where::OperatorEq, $user_id)
                ->set('currency', Where::OperatorEq, $currency)
            )
            ->groupBy('from_user_id')
            ->select();
    }

}
