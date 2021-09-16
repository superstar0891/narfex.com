<?php

use Phinx\Migration\AbstractMigration;

class HedgingAddFields extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html
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
        $this->table('external_exchange_position')
            ->addColumn('buy_currency', 'string', [
                'null' => true,
                'limit' => 12,
                'after' => 'close_rate',
            ])
            ->addColumn('exchange_rate', 'double', [
                'null' => false,
                'default' => 0,
                'after' => 'buy_currency',
            ])
            ->addColumn('fiat_amount', 'double', [
                'null' => false,
                'default' => 0,
                'after' => 'exchange_rate',
            ])
            ->addColumn('buy_rate', 'double', [
                'null' => false,
                'default' => 0,
                'after' => 'exchange_rate',
            ])
            ->addColumn('fiat_currency', 'string', [
                'null' => false,
                'limit' => 12,
                'after' => 'fiat_amount',
            ])
            ->update();
    }
}
