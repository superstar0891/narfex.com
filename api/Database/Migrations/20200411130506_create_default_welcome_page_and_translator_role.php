<?php

use Db\Where;
use Models\PageModel;
use Phinx\Migration\AbstractMigration;

class CreateDefaultWelcomePageAndTranslatorRole extends AbstractMigration
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
            $url = 'introduction';

            $pages = PageModel::select(Where::and()
                ->set('url', Where::OperatorEq, $url)
                ->set('lang', Where::OperatorEq, 'en')
            );

            if ($pages->isEmpty()) {
                $page = new PageModel();
                $page->url = $url;
                /** @var PageModel $page */
                $page->lang = 'en';
                $page->title = 'Introduction';
                $page->content = '{"blocks":[{"key":"6noc4","text":"Welcome to Narfex trader and developer documentation. These documents outline exchange functionality, market details, and APIs.","type":"unstyled","depth":0,"inlineStyleRanges":[],"entityRanges":[],"data":[]}],"entityMap":[]}';
                $page->header = 0;
                $page->category = 'global';
                $page->is_api_page = 1;


                $page->meta_description = 'introduction';

                $page->meta_keyword = 'introduction';
                $page->save();
            }

            $translator = \Models\UserRoleModel::select(Where::equal('role_name', \Models\UserRoleModel::TRANSLATOR_ROLE));
            $docs_editor = \Models\UserPermissionModel::select(Where::equal('name', \Models\UserPermissionModel::DOCS_EDITOR_PERMISSION));
            if ($translator->isEmpty()) {
                $translator = new \Models\UserRoleModel();
                $translator->role_name = \Models\UserRoleModel::TRANSLATOR_ROLE;
                $translator->permissions = '';
                $translator->save();
            } else {
                $translator = $translator->first();
            }
            if ($docs_editor->isEmpty()) {
                $docs_editor = new \Models\UserPermissionModel();
                $docs_editor->name = \Models\UserRoleModel::TRANSLATOR_ROLE;
                $docs_editor->save();
            }

            if (!in_array(\Models\UserPermissionModel::DOCS_EDITOR_PERMISSION, $translator->permissionsAsArray())) {
                $permissions = $translator->permissionsAsArray();
                $permissions[] = \Models\UserPermissionModel::DOCS_EDITOR_PERMISSION;
                $translator->permissions = implode(',', $permissions);
                $translator->save();
            }
        });
    }
}
