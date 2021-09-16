<?php

use Phinx\Migration\AbstractMigration;

class AddIs2FaEnabledToUsers extends AbstractMigration
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
        $table = $this->table('users');
        $table->addColumn('is_2fa_enabled', 'integer', [
            'limit' => 1,
            'default' => 0,
            'after' => 'ga_hash'
        ]);
        $table->update();
        $this->query("UPDATE users SET is_2fa_enabled = 1 WHERE ga_hash IS NOT NULL");
        $this->query("UPDATE users SET ga_hash = REPLACE(ga_hash, '/1', ''), is_2fa_enabled = 0 WHERE ga_hash LIKE '%/1'");
    }
}
