<?php

use Db\Where;
use Models\LangsModel;
use Phinx\Migration\AbstractMigration;

class AddPromocodeLogickDependenciesInDb extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        \Db\Transaction::wrap(function () {
            $this->table('settings')
                ->addColumn('name', 'string', [
                    'null' => false,
                    'limit' => 255,
                ])
                ->addColumn('group_name', 'string', [
                    'null' => false,
                    'limit' => 255,
                ])
                ->addColumn('value', 'string', [
                    'null' => false,
                    'limit' => 255,
                ])
                ->addColumn('description', 'text', [
                    'null' => false,
                    'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_REGULAR,
                ])
                ->addColumn('created_at_timestamp', 'integer', [
                    'null' => true,
                    'limit' => '10',
                    'after' => '_delete',
                ])
                ->addColumn('updated_at_timestamp', 'integer', [
                    'null' => true,
                    'limit' => '10',
                    'after' => 'created_at_timestamp',
                ])
                ->addColumn('deleted_at', 'integer', [
                    'null' => true,
                    'limit' => '10',
                    'after' => 'updated_at_timestamp',
                ])
                ->addIndex('name', [
                    'unique' => true
                ])
                ->addIndex('group_name')
                ->create();

            $lang = LangsModel::first(
                Where::and()
                    ->set(Where::equal('name', 'invalid_promo_code'))
                    ->set(Where::equal('lang', 'en'))
                    ->set(Where::equal('type', \Models\LangsModel::BACKEND_LANG)), false
            );

            if ($lang === null) {
                $lang = new \Models\LangsModel();
                $lang->lang = 'en';
                $lang->type = \Models\LangsModel::BACKEND_LANG;
                $lang->value = 'Invalid promo code';
                $lang->name = 'invalid_promo_code';
                $lang->save();
            }

            $periods = [
                0 => [
                    'coin_promo_first_period_from' => (new \DateTime('15-09-2020'))->getTimestamp(),
                    'coin_promo_first_period_to' => (new \DateTime('15-10-2020'))->getTimestamp(),
                    'coin_promo_first_period' => 0.25,
                    'coin_promo_first_period_balance' => 30700000,
                ],
                1 => [
                    'coin_promo_second_period_from' => (new \DateTime('15-10-2020'))->getTimestamp(),
                    'coin_promo_second_period_to' => (new \DateTime('15-11-2020'))->getTimestamp(),
                    'coin_promo_second_period' => 0.15,
                    'coin_promo_second_period_balance' => 25000000,
                ],
                2 => [
                    'coin_promo_third_period_from' => (new \DateTime('15-11-2020'))->getTimestamp(),
                    'coin_promo_third_period_to' => (new \DateTime('15-12-2020'))->getTimestamp(),
                    'coin_promo_third_period' => 0.05,
                    'coin_promo_third_period_balance' => 18000000,
                ],
            ];

            foreach ($periods as $period) {
                foreach ($period as $key => $value) {
                    if (
                        \Models\SettingsModel::select(Where::equal('name', $key))->isEmpty()
                    ) {
                        $setting = new \Models\SettingsModel();
                        $setting->name = $key;
                        $setting->value = $value;
                        $setting->group_name = 'promo_code';
                        $setting->description = '';

                        $setting->save();
                    }
                }
            }

            $setting = \Models\SettingsModel::first(Where::equal('name', 'coin_promo_referral_reward'));
            if ($setting === null) {
                $setting = new \Models\SettingsModel();
                $setting->name = 'coin_promo_referral_reward';
                $setting->value = 0.05;
                $setting->group_name = 'promo_code';
                $setting->description = '';
                $setting->save();
            }

            $setting = \Models\SettingsModel::first(Where::equal('name', 'coin_promo_buy_with_code_reward'));
            if ($setting === null) {
                $setting = new \Models\SettingsModel();
                $setting->name = 'coin_promo_buy_with_code_reward';
                $setting->value = 0.05;
                $setting->group_name = 'promo_code';
                $setting->description = '';
                $setting->save();
            }
        });
    }
}
