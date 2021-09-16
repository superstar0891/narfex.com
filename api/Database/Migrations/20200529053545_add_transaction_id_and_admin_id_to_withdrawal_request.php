<?php

use Phinx\Migration\AbstractMigration;

class AddTransactionIdAndAdminIdToWithdrawalRequest extends AbstractMigration
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
        $table = $this->table('withdrawal_requests');

        if (!$table->hasColumn('transaction_id') && !$table->hasColumn('admin_id')) {
            $table
                ->addColumn('transaction_id', 'integer', [
                'null' => true,
                'after' => 'to_address'
            ])->addColumn('admin_id', 'integer', [
                    'null' => true,
                    'after' => 'transaction_id'
                ])->update();
        }
    }
}
