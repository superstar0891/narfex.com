<?php

use Phinx\Migration\AbstractMigration;

class ChangeFieldsTypeInOrderAndBalances extends AbstractMigration
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
            $this->table('balances')
                ->changeColumn('amount',  'decimal', [
                    'null' => false,
                    'precision' => '22',
                    'scale' => '8',
                ])
                ->update();
            $this->table('ex_orders')
                ->changeColumn('amount',  'decimal', [
                    'null' => false,
                    'precision' => '22',
                    'scale' => '8',
                ])
                ->changeColumn('filled',  'decimal', [
                    'null' => false,
                    'precision' => '22',
                    'scale' => '8',
                ])
                ->changeColumn('price',  'decimal', [
                    'null' => false,
                    'precision' => '22',
                    'scale' => '8',
                ])
                ->changeColumn('fee',  'decimal', [
                    'null' => false,
                    'precision' => '22',
                    'scale' => '8',
                ])
                ->changeColumn('avg_price',  'decimal', [
                    'null' => false,
                    'precision' => '22',
                    'scale' => '8',
                ])
                ->changeColumn('avg_price_count', 'integer', [
                    'null' => false,
                    'limit' => '10',
                ])
                ->update();
        });
    }
}
