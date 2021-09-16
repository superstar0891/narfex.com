<?php

namespace Admin\modules;

use Admin\helpers\PageContainer;
use Admin\layout\Block;
use Admin\layout\NumberFormat;
use Admin\layout\Tab;
use Db\Model\ModelSet;
use Db\Where;
use Models\ExOrderModel;
use Models\UserModel;

class ExchangeOrders extends PageContainer {

    /* @var \Admin\helpers\TabsManager */
    private $tabs;

    /* @var \Admin\helpers\DataManager */
    private $table;

    public function registerActions() {
        $headers = ['ID', 'User', 'Status', 'Side', 'Type', 'Amount', 'Price', 'Market', 'Filled', 'Date'];
        $this->table = $this
            ->createManagedTable(ExOrderModel::class, $headers)
            ->setDataMapper(function (ModelSet $orders) {
                $users = UserModel::select(Where::in('id', $orders->column('user_id')));
                return $orders->map(function (ExOrderModel $order) use ($users) {
                    /* @var UserModel $user */
                    $user = $users->getItem($order->user_id);
                    return [
                        $order->id,
                        $user ? $user->fullName() : 'Unknown',
                        ucfirst($order->status),
                        ucfirst($order->action),
                        ucfirst($order->type),
                        NumberFormat::withParams($order->amount, $order->primary_coin, ['hidden_currency' => true]),
                        NumberFormat::withParams($order->getAvgPrice(), $order->secondary_coin, ['hidden_currency' => true]),
                        $order->getMarket(),
                        NumberFormat::withParams(floor($order->filled / $order->amount * 100), null, ['percent' => true]),
                        date('d/m/Y', $order->created_at_timestamp),
                    ];
                });
            });

        $this->tabs = $this->createTabsManager()
            ->setTabs(
                Tab::withParams('All')->setRenderer(function () {
                    return $this->table->build();
                }),
                Tab::withParams('Active')->setRenderer(function () {
                    $where = $this->table->getWhere()->set(Where::equal('status', 'working'));
                    return $this->table->setWhere($where)->build();
                })
            );
    }

    public function build() {
        $this->layout->push(Block::withParams('Orders', $this->tabs->build()));
    }
}
