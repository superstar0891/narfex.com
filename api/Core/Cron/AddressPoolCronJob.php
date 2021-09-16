<?php

namespace Cron;

use Core\Blockchain\Factory;
use Db\Model\Field\RandomHashField;
use Db\Where;
use Models\AddressModel;

class AddressPoolCronJob implements CronJobInterface {

    const POOL_SIZE = 300;
    const ADDRESS_ID_PREFIX = 10000;

    public function exec() {
        foreach (array_keys(currencies()) as $currency) {
            if ($currency !== 'eth') {
                $last_address = AddressModel::queryBuilder()
                    ->columns([])
                    ->where(Where::equal('currency', $currency))
                    ->orderBy(['id' => 'DESC'])
                    ->limit(1)
                    ->get();
                $last_address = self::ADDRESS_ID_PREFIX + $last_address['id'];
            } else {
                $last_address = 0;
            }

            $inst = Factory::getInstance($currency);

            $count = AddressModel::queryBuilder()
                ->columns(['COUNT(id)' => 'cnt'], true)
                ->where(Where::and()
                    ->set('currency', Where::OperatorEq, $currency)
                    ->set('user_id', Where::OperatorEq, -1)
                )
                ->get();
            $need_generate = self::POOL_SIZE - $count['cnt'];

            if ($need_generate <= 0) {
                continue;
            }

            for ($i = 0; $i < $need_generate; $i++) {
                $options = [];

                if ($currency === 'eth') {
                    $extra = RandomHashField::init()->fill();
                    $options['passphrase'] = $extra;
                } else {
                    $extra = ++$last_address;
                }

                $address = new AddressModel();
                $address->currency = $currency;
                $address->options = empty($options) ? '' : json_encode($options);
                $address->user_id = -1;
                $address->address = $inst->genAddress($extra);
                $address->created_at = date('Y-m-d H:i:s');
                $address->save();
            }
        }
    }
}
