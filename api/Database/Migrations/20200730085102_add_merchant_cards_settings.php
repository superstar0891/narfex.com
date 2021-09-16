<?php

use Phinx\Migration\AbstractMigration;

class AddMerchantCardsSettings extends AbstractMigration
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
            ->addColumn('bitcoinovnet_max_change_course', 'double', [
                'null' => false,
                'default' => 3,
            ])
            ->addColumn('bitcoinovnet_rate_update', 'integer', [
                'null' => false,
                'default' => 120,
            ])
            ->addColumn('bitcoinovnet_book_time', 'integer', [
                'null' => false,
                'default' => 1800,
            ])
            ->addColumn('bitcoinovnet_card_max_amount', 'double', [
                'null' => false,
                'default' => 200000,
            ])
            ->update();
    }
}
