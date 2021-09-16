<?php

use Phinx\Migration\AbstractMigration;

class AddSwapLimitsInSettings extends AbstractMigration
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
        $this->table('site_settings')
            ->addColumn('swap_usd_daily_limit', 'double', [
                'null' => false,
                'default' => 20000
            ])
            ->addColumn('swap_min_fiat_wallet_transaction_in_usd', 'double', [
                'null' => false,
                'default' => 20
            ])
            ->update();
    }
}
