<?php

use Phinx\Migration\AbstractMigration;

class SetShortRateNullable extends AbstractMigration
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
        $this->table('hedging_stack')
            ->changeColumn('short_rate', 'decimal', [
                'null' => true,
                'precision' => '16',
                'scale' => '4',
            ])
            ->changeColumn('short_fee', 'decimal', [
                'null' => true,
                'precision' => '16',
                'scale' => '4',
            ])
            ->addColumn('account_id','integer', [
                'null' => true,
                'limit' => '10',
                'signed' => false,
            ])
            ->update();

        $this->table('hedging_stack_history')
            ->addColumn('account_id','integer', [
                'null' => true,
                'limit' => '11',
                'signed' => false,
            ])
            ->update();
    }
}
