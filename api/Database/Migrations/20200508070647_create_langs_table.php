<?php

use Phinx\Migration\AbstractMigration;

class CreateLangsTable extends AbstractMigration
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
        $table = $this->table('langs');
        $table->addColumn('name', 'string')
            ->addColumn('value', 'text', [
            'null' => false,
            'limit' => 65535,
            'after' => 'key',
            ])
            ->addColumn('lang', 'string', [
            'null' => false,
            'limit' => 2,
            'after' => 'value',
            ])
            ->addColumn('type', 'string', [
                'null' => false
            ])
            ->addColumn('created_at_timestamp', 'integer', [
                'null' => true,
                'limit' => '10',
                'after' => 'parent',
            ])
            ->addColumn('updated_at_timestamp', 'integer', [
                'null' => true,
                'limit' => '10',
                'after' => 'created_at',
            ])
            ->addColumn('deleted_at', 'integer', [
                'null' => true,
                'limit' => '10',
                'after' => 'updated_at',
            ])
            ->addIndex(['name', 'lang', 'type'], [
                'name' => 'name',
                'unique' => true,
            ])
            ->addIndex(['lang'], [
                'name' => 'lang',
                'unique' => false,
            ])
            ->addIndex(['type'], [
                'name' => 'type',
                'unique' => false,
            ])
            ->create();
    }
}
