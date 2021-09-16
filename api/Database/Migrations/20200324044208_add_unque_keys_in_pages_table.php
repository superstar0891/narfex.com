<?php

use Phinx\Migration\AbstractMigration;

class AddUnqueKeysInPagesTable extends AbstractMigration
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
        $data = [];

        $pages = \Models\PageModel::select();

        foreach ($pages as $page) {
            /** @var \Models\PageModel $page */
            $uniqueKey = $page->url . '-' . $page->lang;
            if (isset($data[$uniqueKey])) {
                $page->url = $page->url . '_renamed';
                $page->save();
            } else {
                $data[$uniqueKey] = true;
            }
        }

        $this->table('pages')
            ->addIndex(['url', 'lang'], [
                'unique' => true,
            ])
            ->update();
    }
}
