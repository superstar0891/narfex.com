<?php

use Phinx\Migration\AbstractMigration;

class AddTxidAndReservationIdFields extends AbstractMigration
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
            $this->table('reserved_merchant_cards')
                ->addColumn('txid', 'string', [
                    'null' => true,
                    'limit' => 255,
                    'collation' => 'utf8mb4_unicode_ci',
                    'encoding' => 'utf8mb4',
                ])
                ->addColumn('photo_name', 'string', [
                    'null' => true,
                    'limit' => 255,
                    'collation' => 'utf8mb4_unicode_ci',
                    'encoding' => 'utf8mb4',
                ])
                ->update();

            $this->table('merchant_payments')
                ->addColumn('reservation_id', 'integer', [
                    'null' => true,
                    'signed' => false,
                    'limit' => '10',
                ])
                ->addIndex('reservation_id')
                ->update();
        });
    }
}
