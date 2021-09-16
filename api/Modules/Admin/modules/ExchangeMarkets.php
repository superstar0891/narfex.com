<?php

namespace Admin\modules;

use Admin\helpers\ActionRequest;
use Admin\helpers\FormManager;
use Admin\helpers\PageContainer;
use Admin\layout\Action;
use Admin\layout\Block;
use Admin\layout\Button;
use Admin\layout\Checkbox;
use Admin\layout\Input;
use Admin\layout\NumberFormat;
use Admin\layout\Time;
use Admin\layout\Toast;
use Db\Model\ModelSet;
use Models\ExMarketModel;
use Models\UserModel;

class ExchangeMarkets extends PageContainer {
    /* @var \Admin\helpers\DataManager */
    private $table;

    /** @var Action */
    private $edit_action;

    /** @var FormManager */
    private $edit_form;

    public function registerActions() {
        $headers = ['ID', 'Primary', 'Secondary', 'Pair', 'Max Amount', 'Min amount', 'Maker Volume', 'Decimals', 'Internal', 'Date'];
        $this->table = $this
            ->createManagedTable(ExMarketModel::class, $headers)
            ->setDataMapper(function (ModelSet $markets) {
                return $markets->map(function (ExMarketModel $market) {
                    return [
                        $market->id,
                        $market->primary_coin,
                        $market->secondary_coin,
                        "$market->primary_coin/$market->secondary_coin",
                        NumberFormat::withParams($market->max_amount, $market->primary_coin, ['hidden_currency' => true]),
                        NumberFormat::withParams($market->min_amount, $market->primary_coin, ['hidden_currency' => true]),
                        $market->maker_volume,
                        $market->decimals,
                        $market->is_internal ? 'Yes' : 'No',
                        Time::withParams($market->created_at_timestamp),
                        Button::withParams('Edit')
                            ->onClick($this->edit_action->setParam('id', $market->id))
                    ];
                });
            });

        $this->edit_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal('Edit market', $this->edit_form->setParams($request->getParams())->build());
        });

        $this->edit_form = $this->createFormManager()
            ->setItems(function ($params) {
                $id = array_get_val($params, 'id');

                $market = ExMarketModel::get($id);

                return [
                    Input::withParams('primary_coin', 'Primary coin', $market->primary_coin),
                    Input::withParams('secondary_coin', 'Secondary coin', $market->secondary_coin),
                    Input::withParams('max_amount', 'Max amount', $market->max_amount),
                    Input::withParams('min_amount', 'Min amount', $market->min_amount),
                    Input::withParams('decimals', 'Decimals', $market->decimals),
                    Checkbox::withParams('is_internal', 'Internal', $market->is_internal),
                ];
            })
            ->onSubmit(function (ActionRequest $request) {
                $values = $request->getValues([
                    'primary_coin' => ['required'],
                    'secondary_coin' => ['required'],
                    'max_amount' => ['required'],
                    'min_amount' => ['required'],
                    'decimals' => ['required'],
                    'is_internal' => ['bool'],
                ]);

                $params = $request->getParams();
                $id = $request->getParam('id');
                try {
                    $market = ExMarketModel::get($id);
                    $market->primary_coin = $values['primary_coin'];
                    $market->secondary_coin = $values['secondary_coin'];
                    $market->max_amount = $values['max_amount'];
                    $market->min_amount = $values['min_amount'];
                    $market->decimals = $values['decimals'];
                    $market->is_internal = array_get_val($values, 'is_internal', 0);
                    $market->save();
                } catch (\Exception $e) {
                    return $this->showToast($e->getMessage(), Toast::TYPE_ERROR);
                }

                return [
                    $this->closeModal(),
                    $this->showToast('ExMarket saved'),
                    $this->table->getReloadAction($params, $values)
                ];
            }, true);
    }

    public function build() {
        $this->layout->push(Block::withParams('Markets', $this->table->build()));
    }
}
