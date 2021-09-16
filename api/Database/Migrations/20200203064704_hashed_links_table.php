<?php

use Phinx\Migration\AbstractMigration;

class HashedLinksTable extends AbstractMigration
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
        $table = $this->table('hashed_links');
        $table
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'after' => 'id',
            ])
            ->addColumn('hash', 'string', ['null' => false, 'limit' => 150])
            ->addColumn('type', 'string', [
                'null' => false,
                'limit' => 100,
                'after' => 'hash',
            ])
            ->addColumn('expired_at', 'integer', [
                'null' => false,
                'limit' => '10',
                'after' => 'type',
            ])->addColumn('created_at_timestamp', 'integer', [
                'null' => true,
                'limit' => '10',
                'after' => 'commerce',
            ])
            ->addColumn('updated_at_timestamp', 'integer', [
                'null' => true,
                'limit' => '10',
                'after' => 'created_at_timestamp',
            ])
            ->addColumn('deleted_at', 'integer', [
                'null' => true,
                'limit' => '10',
                'after' => 'updated_at_timestamp',
            ])
            ->create();

    }
}
