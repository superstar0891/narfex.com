<?php

use Phinx\Migration\AbstractMigration;

class News extends AbstractMigration
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
        if ($this->hasTable('news')) {
            return;
        }
        $this->table('news', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'comment' => '',
            'row_format' => 'COMPACT',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('title', 'string', [
                'null' => false,
                'limit' => 150,
                'collation' => 'utf8_unicode_ci',
                'encoding' => 'utf8',
                'after' => 'id',
            ])
            ->addColumn('content', 'text', [
                'null' => false,
                'limit' => 65535,
                'collation' => 'utf8_unicode_ci',
                'encoding' => 'utf8',
                'after' => 'title',
            ])
            ->addColumn('img', 'string', [
                'null' => false,
                'limit' => 150,
                'collation' => 'utf8_unicode_ci',
                'encoding' => 'utf8',
                'after' => 'content',
            ])
            ->addColumn('lang', 'string', [
                'null' => false,
                'limit' => 6,
                'collation' => 'utf8_unicode_ci',
                'encoding' => 'utf8',
                'after' => 'img',
            ])
            ->addColumn('url', 'string', [
                'null' => true,
                'limit' => 150,
                'collation' => 'utf8_unicode_ci',
                'encoding' => 'utf8',
                'after' => 'lang',
            ])
            ->addColumn('created_at', 'date', [
                'null' => false,
                'after' => 'url',
            ])
            ->addColumn('_delete', 'integer', [
                'null' => false,
                'limit' => '1',
                'after' => 'created_at',
            ])
            ->addColumn('meta_description', 'string', [
                'null' => true,
                'limit' => 150,
                'collation' => 'utf8_unicode_ci',
                'encoding' => 'utf8',
                'after' => '_delete',
            ])
            ->addColumn('meta_keyword', 'string', [
                'null' => true,
                'limit' => 150,
                'collation' => 'utf8_unicode_ci',
                'encoding' => 'utf8',
                'after' => 'meta_description',
            ])
            ->addIndex(['url'], [
                'name' => 'url',
                'unique' => true,
            ])
            ->create();

    }
}
