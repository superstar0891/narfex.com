<?php

use Phinx\Migration\AbstractMigration;

class CreateQueuesTables extends AbstractMigration
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
        if (!$this->table('queue_jobs')->exists()) {
            $this->table('queue_jobs')
                ->addColumn('class', 'string', [
                    'null' => false,
                    'limit' => 255,
                    'collation' => 'utf8mb4_unicode_ci',
                    'encoding' => 'utf8mb4',
                ])
                ->addColumn('serialized_queue', 'text', [
                    'null' => false,
                    'collation' => 'utf8mb4_unicode_ci',
                    'encoding' => 'utf8mb4',
                ])
                ->addColumn('tries', 'integer', [
                    'null' => true,
                    'limit' => '10',
                    'signed' => false,
                ])
                ->addColumn('current_try', 'integer', [
                    'null' => false,
                    'default' => 0,
                    'limit' => '10',
                    'signed' => false,
                ])
                ->addColumn('done', 'integer', [
                    'null' => false,
                    'limit' => '1',
                    'signed' => false,
                    'default' => 0
                ])
                ->addColumn('failed', 'integer', [
                    'null' => false,
                    'limit' => '1',
                    'signed' => false,
                    'default' => 0
                ])
                ->addColumn('is_working', 'integer', [
                    'null' => false,
                    'limit' => '1',
                    'signed' => false,
                    'default' => 0
                ])
                ->addColumn('created_at_timestamp', 'integer', [
                    'null' => false,
                    'limit' => '10',
                    'signed' => false,
                    'after' => 'id',
                ])
                ->addColumn('updated_at_timestamp', 'integer', [
                    'null' => false,
                    'limit' => '10',
                    'signed' => false,
                    'after' => 'created_at_timestamp',
                ])
                ->addColumn('deleted_at', 'integer', [
                    'null' => true,
                    'limit' => '10',
                    'signed' => false,
                    'after' => 'updated_at_timestamp',
                ])
                ->addIndex(['failed', 'done', 'is_working'])
                ->addIndex('class')
                ->create();
        }

        if (!$this->table('queue_errors')->exists()) {
            $this->table('queue_errors')
                ->addColumn('queue_id', 'integer', [
                    'null' => false,
                    'limit' => '10',
                    'signed' => false,
                    'after' => 'id',
                ])
                ->addColumn('class', 'string', [
                    'null' => false,
                    'limit' => 255,
                    'collation' => 'utf8mb4_unicode_ci',
                    'encoding' => 'utf8mb4',
                ])
                ->addColumn('error_message', 'text', [
                    'null' => false,
                    'collation' => 'utf8mb4_unicode_ci',
                    'encoding' => 'utf8mb4',
                ])
                ->addColumn('error_trace', 'text', [
                    'null' => false,
                    'collation' => 'utf8mb4_unicode_ci',
                    'encoding' => 'utf8mb4',
                ])
                ->addColumn('created_at_timestamp', 'integer', [
                    'null' => false,
                    'limit' => '10',
                    'signed' => false,
                    'after' => 'id',
                ])
                ->addColumn('updated_at_timestamp', 'integer', [
                    'null' => false,
                    'limit' => '10',
                    'signed' => false,
                    'after' => 'created_at_timestamp',
                ])
                ->addColumn('deleted_at', 'integer', [
                    'null' => true,
                    'limit' => '10',
                    'signed' => false,
                    'after' => 'updated_at_timestamp',
                ])
                ->addIndex('class')
                ->addIndex('queue_id')
                ->create();
        }
    }
}
