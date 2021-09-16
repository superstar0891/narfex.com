<?php

use Phinx\Migration\AbstractMigration;

class AddCloseDateToHedgingStackTable extends AbstractMigration
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
            ->addColumn('close_at_timestamp', 'integer', [
                'null' => true,
                'limit' => '10',
                'signed' => false,
                'after' => 'close_long_currency',
            ])
            ->update();

        try {
            \Db\Transaction::wrap(function () {
                $stacks = \Models\StackModel::select();
                if ($stacks->isEmpty()) {
                    return;
                }

                foreach ($stacks as $stack) {
                    $stack->close_at_timestamp = $stack->updated_at_timestamp;
                    $stack->save();
                }
            });
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

    }
}
