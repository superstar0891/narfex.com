<?php

namespace Api\Admin;

use Admin\modules\Menu;
use Core\Response\JsonResponse;
use Engine\Request;
use Modules\Admin\AdminModule;
use Serializers\AdminSerializer;

class Admin {

    const ACTION_SHOW_TOAST = 'show_toast';
    const ACTION_CLOSE_MODAL = 'close_modal';
    const ACTION_SHOW_PAGE = 'show_page';
    const ACTION_SHOW_CUSTOM_PAGE = 'show_custom_page';
    const ACTION_SHOW_MODAL = 'show_modal';
    const ACTION_SHOW_TAB = 'show_tab';
    const ACTION_RELOAD_TABLE_ROWS = 'reload_table_rows';
    const ACTION_RELOAD_TABLE = 'reload_table';

    public static function retrieve() {
        $menu = new Menu(Request::getUser());

        $menu->addItem('Test', [], [
            [
                'title' => 'test',
                'params' => [
                    'action' => AdminModule::showPage('Test')
                ],
            ],
        ]);
        $menu->addItem('Bitcoinovnet', [], [
            [
                'title' => 'Main info',
                'params' => [
                    'action' => AdminModule::showPage('BitcoinovnetInfo')
                ],
            ],
            [
                'title' => 'Requests and cards',
                'params' => [
                    'action' => AdminModule::showPage('Bitcoinovnet')
                ],
            ],
            [
                'title' => 'Agents',
                'params' => [
                    'action' => AdminModule::showPage('BitcoinovnetAgents')
                ],
            ],
            [
                'title' => 'Stack',
                'params' => [
                    'action' => AdminModule::showPage('BitcoinovnetStack')
                ],
            ],
            [
                'title' => 'Withdrawals',
                'params' => [
                    'action' => AdminModule::showPage('BitcoinovnetWithdrawals')
                ],
            ],
            [
                'title' => 'Users',
                'params' => [
                    'action' => AdminModule::showPage('BitcoinovnetUsers')
                ],
            ],
            [
                'title' => 'Reviews',
                'params' => [
                    'action' => AdminModule::showPage('BitcoinovnetReviews')
                ],
            ],
            [
                'title' => 'Manual Sessions',
                'params' => [
                    'action' => AdminModule::showPage('BitcoinovnetSessions')
                ],
            ],
            [
                'title' => 'Reservations',
                'params' => [
                    'action' => AdminModule::showPage('BitcoinovnetReservations')
                ],
            ],
        ]);
        $menu->addItem('Site', [], [
            [
                'title' => 'Settings',
                'params' => [
                    'action' => AdminModule::showPage('SiteSettings')
                ],
            ],
            [
                'title' => 'Key-value settings',
                'params' => [
                    'action' => AdminModule::showPage('Settings')
                ],
            ],
            [
                'title' => 'Langs',
                'params' => [
                    'action' => AdminModule::showCustomPage('Translations')
                ],
            ],
        ]);
        $menu->addItem('Pages', [], [
            [
                'title' => 'Pages',
                'params' => [
                    'action' => AdminModule::showPage('Pages')
                ],
            ],
        ]);
        $menu->addItem('Users', [], [
            [
                'title' => 'Users',
                'params' => [
                    'action' => AdminModule::showPage('Users')
                ],
            ],
            [
                'title' => 'Roles',
                'params' => [
                    'action' => AdminModule::showPage('Roles')
                ],
            ],
            [
                'title' => 'Permissions',
                'params' => [
                    'action' => AdminModule::showPage('Permissions')
                ],
            ],
            [
                'title' => 'Logs',
                'params' => [
                    'action' => AdminModule::showPage('UserLogs')
                ],
            ],
            [
                'title' => 'Agents',
                'params' => [
                    'action' => AdminModule::showPage('Agents')
                ],
            ],
            [
                'title' => 'Withdrawal disabled',
                'params' => [
                    'action' => AdminModule::showPage('WithdrawalDisabledUsers')
                ],
            ],
        ]);
        $menu->addItem('Deposits', [], [
            [
                'title' => 'Pool',
                'params' => [
                    'action' => AdminModule::showPage('Pools')
                ],
            ],
        ]);
        $menu->addItem('Fiat', [], [
            [
                'title' => 'Payments',
                'params' => [
                    'action' => AdminModule::showPage('FiatPayments')
                ],
            ],
            [
                'title' => 'Invoices',
                'params' => [
                    'action' => AdminModule::showPage('FiatInvoices')
                ],
            ],
            [
                'title' => 'Refills',
                'params' => [
                    'action' => AdminModule::showPage('Refills')
                ],
            ],
            [
                'title' => 'Withdrawals',
                'params' => [
                    'action' => AdminModule::showPage('WithdrawalRequests')
                ],
            ],
            [
                'title' => 'Hedging',
                'params' => [
                    'action' => AdminModule::showPage('Hedging')
                ],
            ],
            [
                'title' => 'Hedging Stacks',
                'params' => [
                    'action' => AdminModule::showPage('HedgingStacks')
                ],
            ],
            [
                'title' => 'Swaps',
                'params' => [
                    'action' => AdminModule::showPage('Swaps')
                ],
            ],
            [
                'title' => 'Cards',
                'params' => [
                    'action' => AdminModule::showPage('BankCards')
                ],
            ],
        ]);
        $menu->addItem('Exchange', [], [
            [
                'title' => 'Orders',
                'params' => [
                    'action' => AdminModule::showPage('ExchangeOrders')
                ],
            ],
            [
                'title' => 'Markets',
                'params' => [
                    'action' => AdminModule::showPage('ExchangeMarkets')
                ],
            ],
        ]);
        $menu->addItem('Blockchain', [], [
            [
                'title' => 'Information',
                'params' => [
                    'action' => AdminModule::showPage('BlockchainInfo')
                ],
            ],
            [
                'title' => 'Transactions',
                'params' => [
                    'action' => AdminModule::showPage('Transactions')
                ],
            ],
            [
                'title' => 'Withdrawals',
                'params' => [
                    'action' => AdminModule::showPage('BlockchainWithdrawalRequest')
                ],
            ],
            [
                'title' => 'Transfers',
                'params' => [
                    'action' => AdminModule::showPage('BlockchainTransfers')
                ],
            ],
        ]);
        $menu->addItem('Investments', [], [
            [
                'title' => 'Profits',
                'params' => [
                    'action' => AdminModule::showPage('InvestmentProfits')
                ],
            ],
            [
                'title' => 'Deposits',
                'params' => [
                    'action' => AdminModule::showPage('InvestmentDeposits')
                ],
            ],
            [
                'title' => 'Payments',
                'params' => [
                    'action' => AdminModule::showPage('InvestPayments')
                ],
            ],
        ]);

        JsonResponse::ok(['menu' => $menu->getItems()]);
    }

//    public static function action($request) {
//        /* @var string $action
//         * @var array $params
//         * @var array $values
//        */
//        extract($request['params']);
//
//        $user = getUser($request);
//
//        $result = [];
//        switch ($action) {
//            case self::ACTION_SHOW_PAGE:
//                $result[] = AdminSerializer::action(self::ACTION_SHOW_PAGE, [
//                    'page' => $params['page'],
//                    'layout' => AdminModule::page($params, $user),
//                ]);
//                break;
//            case self::ACTION_SHOW_TAB:
//                $result[] = AdminSerializer::action(self::ACTION_SHOW_TAB, [
//                    'layout' => AdminModule::tabs($params, $user),
//                    'id' => $params['tab'],
//                ]);
//                break;
//            case self::ACTION_SHOW_MODAL:
//                $result[] = AdminSerializer::action(self::ACTION_SHOW_MODAL, [
//                    'modal' => $params['modal'],
//                    'layout' => AdminModule::modals($params, $user),
//                ]);
//                break;
//            default:
//                if (substr($action, 0, 5) === 'core.') {
//                    $result = array_merge($result, AdminModule::coreActions(explode('.', $action)[1], $params));
//                } else {
//                    $result = array_merge($result, AdminModule::customActions($action, $params, $values, $user));
//                }
//                break;
//        }
//
//        JsonResponse::ok($result);
//    }

    public static function action($request) {
        /* @var string $action
         * @var array $params
         * @var array $values
         */
        extract($request['params']);

        $user = getUser($request);
        $result = [];

        if (!Request::isAdminApplication() || !$user->hasAdminAccess()) {
            JsonResponse::accessDeniedError();
        }

        switch ($action) {
            case self::ACTION_SHOW_PAGE:
            case self::ACTION_SHOW_CUSTOM_PAGE:
                $class_name = "\Admin\modules\\" . $params['page'];
                /* @var \Admin\helpers\PageContainer $inst */
                $inst = new $class_name();

                if (
                    (empty($inst::$permission_list) && !$user->isAdmin())
                    ||
                    !$user->hasPermissions($inst::$permission_list)
                ) {
                    JsonResponse::accessDeniedError();
                }
                $inst->setAdmin($user);
                $inst->registerActions();
                $inst->build();


                $result[] = AdminSerializer::action($action, [
                    'page' => $params['page'],
                    'layout' => $inst->layout->build()
                ]);
                break;
            default:
                [$module] = explode('_', $action);

                $class_name = "\Admin\modules\\" . $module;
                /* @var \Admin\helpers\PageContainer $inst */
                $inst = new $class_name();
                if (
                    (empty($inst::$permission_list) && !$user->isAdmin())
                    ||
                    !$user->hasPermissions($inst::$permission_list)
                ) {
                    JsonResponse::accessDeniedError();
                }
                $inst->setAdmin($user);
                $inst->registerActions();
                $inst->buildAction($action, $params, $values);
                $result = $inst->layout->build();
                break;
        }

        JsonResponse::ok($result);
    }
}
