<?php

use Phinx\Migration\AbstractMigration;

class AddVerificationFieldsToUser extends AbstractMigration
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
        $table->addColumn('verification_result', 'text', [
            'null' => true,
            'after' => 'verification',
        ]);
        $table->addColumn('birthday', 'integer', [
            'null' => true,
            'after' => 'verification_result',
        ]);
        $table->addColumn('applicant_id', 'text', [
            'null' => true,
            'after' => 'birthday',
        ]);
        $table->addColumn('verification_request_at', 'integer', [
            'null' => true,
            'after' => 'applicant_id',
        ]);
        $table->addColumn('country', 'string', [
            'null' => true,
            'after' => 'verification_result',
        ]);
        $table->addColumn('city', 'string', [
            'null' => true,
            'after' => 'country',
        ]);
        $table->save();
    }
}
