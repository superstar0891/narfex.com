<?php

use Db\Where;
use Phinx\Migration\AbstractMigration;

class AddPromoPeriodsCoinBankValue extends AbstractMigration
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
            $setting = \Models\SettingsModel::first(Where::equal('name', 'coin_promo_first_period_bank'));
            if ($setting === null) {
                $setting = new \Models\SettingsModel();
                $setting->name = 'coin_promo_first_period_bank';
                $setting->value = 30700000;
                $setting->group_name = 'promo_code';
                $setting->description = 'Общее количество токенов выделенных на первый период продаж';
                $setting->save();
            }
            $setting = \Models\SettingsModel::first(Where::equal('name', 'coin_promo_second_period_bank'));
            if ($setting === null) {
                $setting = new \Models\SettingsModel();
                $setting->name = 'coin_promo_second_period_bank';
                $setting->value = 25000000;
                $setting->group_name = 'promo_code';
                $setting->description = 'Общее количество токенов выделенных на второй период продаж';
                $setting->save();
            }
            $setting = \Models\SettingsModel::first(Where::equal('name', 'coin_promo_third_period_bank'));
            if ($setting === null) {
                $setting = new \Models\SettingsModel();
                $setting->name = 'coin_promo_third_period_bank';
                $setting->value = 18000000;
                $setting->group_name = 'promo_code';
                $setting->description = 'Общее количество токенов выделенных на третий период продаж';
                $setting->save();
            }
        });
    }
}
