<?php

namespace Admin\modules;

use Admin\helpers\ActionRequest;
use Admin\helpers\FormManager;
use Admin\helpers\PageContainer;
use Admin\layout\Action;
use Admin\layout\Block;
use Admin\layout\Button;
use Admin\layout\Group;
use Admin\layout\InfoList;
use Admin\layout\InfoListItem;
use Admin\layout\Input;
use Admin\layout\Json;
use Admin\layout\Layout;
use Admin\layout\NumberFormat;
use Core\Blockchain\Factory;
use Db\Where;
use Models\AddressModel;
use Models\TransactionModel;
use Models\WalletModel;
use Modules\WalletModule;

class BlockchainInfo extends PageContainer {

    /* @var Action */
    private $withdraw;

    /* @var FormManager */
    private $withdraw_form;

    public function registerActions() {
        $this->withdraw_form = $this->createFormManager()
            ->setItems(function (array $params) {
                return [
                    Input::withParams('address', 'Address'),
                    Input::withParams('amount', 'Amount'),
                    Input::withParams('secret', 'Secret key')
                ];
            })->onSubmit(function (ActionRequest $request) {

                $admin = $this->getAdmin();
                if (!in_array($admin->id, [ID_NRADIONOV, ID_AGOGLEV])) {
                    return $this->showErrorToast('Access denied');
                }

                $currency = $request->getParam('currency');

                /* @var string $address
                 * @var int $amount
                 * @var string $secret
                 */
                extract($request->getValues([
                        'address' => ['required'],
                        'amount' => ['required', 'positive'],
                        'secret' => ['required']
                    ]
                ));

                $conf = KERNEL_CONFIG['admin_withdraw'];
                if ($secret !== $conf['secret']) {
                    return $this->showErrorToast('Secret is incorrect');
                }

                if (!isset($conf['whitelist'][$currency])) {
                    return $this->showErrorToast('Unsupported currency');
                }

                $white_list = explode(',', $conf['whitelist'][$currency]);
                $white_list = array_map('trim', $white_list);
                $white_list = array_filter($white_list);

                if (!in_array($address, $white_list, true)) {
                    return $this->showErrorToast('Address is incorrect');
                }

                $instance = Factory::getInstance($currency);

                if ($currency === CURRENCY_ETH) {
                    $address_model = AddressModel::select(Where::and()
                        ->set(Where::equal('address', KERNEL_CONFIG['eth_root_address']))
                        ->set(Where::equal('currency', CURRENCY_ETH))
                    );
                    if ($address_model->isEmpty()) {
                        return $this->showErrorToast('Root address not found');
                    }
                    $address_model = $address_model->first();
                    /* @var AddressModel $address */
                    $balance = $instance->getWalletInfo($address_model->address)['balance'];
                    $options = json_decode($address_model->options, true);
                    $password = $options['passphrase'];
                    $from_address = $address_model->address;
                } else {
                    $balance = $instance->getWalletInfo()['balance'];
                    $from_address = null;
                    $password = null;
                }

                if ($balance < $amount) {
                    return $this->showErrorToast('Not enough money to transfer founds');
                }

                try {
                    $txid = $instance->sendToAddress(
                        $from_address,
                        $address,
                        $amount,
                        $password
                    );
                } catch (\Exception $e) {
                    return $this->showErrorToast($e->getMessage());
                }

                WalletModule::createTransaction('send', $currency, $amount, [
                    'status' => TransactionModel::STATUS_UNCONFIRMED,
                    'txid' => $txid,
                    'to' => $address,
                    'user_id' => $admin->id,
                ]);

                return [
                    $this->closeModal(),
                    $this->showToast('Transaction created'),
                    $this->openPage($this->id())
                ];
            }, true);

        $this->withdraw = $this->createAction(function (ActionRequest $request) {
            return $this->showModal(
                'Withdraw ' . strtoupper($request->getParam('currency')),
                $this->withdraw_form->setParams($request->getParams())->build()
            );
        });
    }

    public function build() {
        $this->layout->push(Group::withItems(
            $this->getBalances(),
            $this->getWalletBalances()
        ));
        $this->layout->push($this->getBlockchainInfo());
    }

    private function getBalances(): Layout {
        $items = [];
        foreach (array_keys(currencies()) as $currency) {
            try {
                $inst = Factory::getInstance($currency);
                $info = $inst->getWalletInfo(KERNEL_CONFIG['eth_root_address']);
            } catch (\Exception $e) {
                $info = ['balance' => 0];
            }

            $items[] = InfoListItem::withParams(
                ucfirst($currency),
                Group::withItems(
                    NumberFormat::withParams(floatval($info['balance']), $currency, ['hidden_currency' => true]),
                    Button::withParams('Withdraw', Button::TYPE_OUTLINE, Button::SIZE_SMALL)
                        ->onClick($this->withdraw->use(['currency' => $currency]))
                )
            );
        }

        return Block::withParams('Balances', InfoList::withItems(...$items));
    }

    private function getWalletBalances(): Layout {
        $balances = WalletModel::queryBuilder()
            ->columns(['SUM(amount) as total', 'currency'], true)
            ->where(Where::and()->set('deleted_at', Where::OperatorIs,  null))
            ->groupBy('currency')
            ->select();

        $items = [];
        foreach ($balances as $item) {
            $total = formatNum($item['total']);
            $items[] = InfoListItem::withParams(
                ucfirst($item['currency']),
                NumberFormat::withParams(floatval($total), $item['currency'], ['hidden_currency' => true])
            );
        }

        return Block::withParams('Wallet balances', InfoList::withItems(...$items));
    }

    private function getBlockchainInfo(): Layout {
        $blocks = [];
        foreach (array_keys(currencies()) as $currency) {
            try {
                $inst = Factory::getInstance($currency);
                $info = $inst->getBlockchainInfo()['info'];
            } catch (\Exception $e) {
                $info = ['Unavailable'];
            }

            $blocks[] = Block::withParams(ucfirst($currency), Json::withPayload($info));
        }

        return Group::withItems(...$blocks);
    }
}
