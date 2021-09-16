<?php

use Phinx\Migration\AbstractMigration;

class AddNewBalanceBitcoinovnetSettings extends AbstractMigration
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
            ->addColumn('bitcoinovnet_max_transaction_amount', 'double', [
                'null' => false,
                'default' => 150000,
            ])
            ->addColumn('bitcoinovnet_min_transaction_amount', 'double', [
                'null' => false,
                'default' => 10000,
            ])
            ->addColumn('bitcoinovnet_btc_balance', 'decimal', [
                'null' => false,
                'precision' => '22',
                'scale' => '8',
                'default' => 0
            ])
            ->update();
    }
}
