<?php

use Phinx\Migration\AbstractMigration;

class AddBitcoinovnetUserCards extends AbstractMigration
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
        $this->table('bitcoinovnet_user_cards')
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
            ])
            ->addColumn('card_number', 'string', [
                'null' => false,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('card_owner', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('validated', 'integer', [
                'null' => false,
                'limit' => '1',
                'signed' => false,
                'default' => 0
            ])
            ->addColumn('created_at_timestamp', 'integer', [
                'null' => true,
                'limit' => '10',
            ])
            ->addColumn('updated_at_timestamp', 'integer', [
                'null' => true,
                'limit' => '10',
            ])
            ->addColumn('deleted_at', 'integer', [
                'null' => true,
                'limit' => '10',
            ])
            ->addIndex(['user_id', 'card_number'])
            ->addIndex('user_id')
            ->create();
    }
}
