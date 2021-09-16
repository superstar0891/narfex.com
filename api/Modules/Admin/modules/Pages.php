<?php

namespace Admin\modules;

use Admin\helpers\ActionRequest;
use Admin\helpers\DataManager;
use Admin\helpers\FormManager;
use Admin\helpers\PageContainer;
use Admin\helpers\TabsManager;
use Admin\layout\Action;
use Admin\layout\ActionSheet;
use Admin\layout\ActionSheetItem;
use Admin\layout\Block;
use Admin\layout\Button;
use Admin\layout\Checkbox;
use Admin\layout\DropDown;
use Admin\layout\Input;
use Admin\layout\Tab;
use Admin\layout\Toast;
use Admin\layout\Wysiwyg;
use Db\Model\ModelSet;
use Db\Where;
use Models\PageModel;
use Modules\LangModule;
use Serializers\PageSerializer;

class Pages extends PageContainer {

    public static $permission_list = [
        'docs_editor'
    ];

    /* @var DataManager $pages */
    private $pages;

    /* @var DataManager $static_pages */
    private $static_pages;

    /* @var Action $edit_action */
    private $edit_action;

    /* @var Action $edit_action */
    private $add_action;

    /* @var Action $delete_action */
    private $delete_action;

    /* @var FormManager */
    private $edit_form;

    /* @var FormManager */
    private $add_form;

    /* @var TabsManager */
    private $tabs;

    public function registerActions() {
        $this->createEditAction();
        $this->createAddAction();

        $this->delete_action = $this->createAction(function (ActionRequest $request) {
            /* @var PageModel $item */
            $item = PageModel::get($request->getParam('id'));
            $item->delete(true);
            return [
                $this->showToast('Page deleted'),
                $this->static_pages->getReloadAction($request->getParams(), $request->getValues()),
                $this->pages->getReloadAction($request->getParams(), $request->getValues()),
            ];
        })->setConfirm(true, 'Delete Page', true);

        $this->pages = $this->createPagesTable();
        $this->static_pages = $this->createPagesTable(true);

        $this->tabs = $this->createTabsManager()
            ->setTabs(
                Tab::withParams('Pages')->setRenderer(function () {
                    return $this->pages->build();
                }),
                Tab::withParams('Api pages')->setRenderer(function () {
                    return $this->static_pages->build();
                })
            );

        $this->add_form = $this->getPageCreateOrEditForm();
        $this->edit_form = $this->getPageCreateOrEditForm();
    }

    private function getPageCreateOrEditForm() : FormManager {
        $langs = LangModule::languages();
        return $this->createFormManager()
            ->setItems(function ($params) use ($langs) {
                $page = isset($params['id']) ? PageModel::get($params['id']) : $this->getDefaultPageModel();
                $params = array_merge($params, PageSerializer::moreDetail($page));
                return [
                    Input::withParams('url', 'Url', array_get_val($params, 'url', ''))
                        ->setRequired(true),
                    Input::withParams('title', 'Title',  array_get_val($params, 'title', ''))
                        ->setRequired(true),
                    Wysiwyg::withParams('content', 'Content', array_get_val($params, 'content', ''))
                        ->setRequired(true),
                    Input::withParams('meta_description', 'meta_description', array_get_val($params, 'Meta description', ''))
                        ->setMultiLine(true)
                        ->setRequired(true),
                    Input::withParams('meta_keyword', 'Meta keyword',array_get_val($params, 'meta_keyword', ''))
                        ->setMultiLine(true)
                        ->setRequired(true),
                    Checkbox::withParams('is_api_page', 'Is api page', boolval(array_get_val($params, 'is_api', 0))),
                    DropDown::withParams('lang', 'Language', $langs, array_get_val($params, 'lang', ''))
                        ->setRequired(true),
                ];
            })
            ->onSubmit(function (ActionRequest $request) use ($langs) {
                $values = $request->getValues([
                    'url' => ['required'],
                    'title' => ['required'],
                    'content' => ['required', 'json'],
                    'is_api_page' => ['bool'],
                    'lang' => ['required', 'oneOf' => array_map(function ($lang) { return $lang[0]; }, $langs)]
                ]);

                $params = $request->getParams();
                try {
                    $page = isset($params['id']) ? PageModel::get($params['id']) : new PageModel();
                    $page->header = 0;
                    $page->category = 'global';
                    $page->url = $values['url'];
                    $page->title = $values['title'];
                    $page->content = json_encode($values['content']);
                    $page->is_api_page = array_get_val($values, 'is_api_page', 0);
                    $page->meta_description = array_get_val($values, 'meta_description', '');
                    $page->meta_keyword = array_get_val($values, 'meta_keyword', '');
                    $page->lang = $values['lang'];
                    $page->save();
                } catch (\Exception $e) {
                    return $this->showToast($e->getMessage(), Toast::TYPE_ERROR);
                }

                $reload_tables = isset($params['is_api']) ? [$this->getReloadTab($params, $values)] : [
                    $this->static_pages->getReloadAction($params, $values),
                    $this->pages->getReloadAction($params, $values),
                ];

                return array_merge($reload_tables, [
                    $this->closeModal(),
                    $this->showToast('Page saved'),
                ]);
            });
    }

    private function getDefaultPageModel(): PageModel {
        $page = new PageModel();
        $page->url = '';
        $page->title = '';
        $page->meta_keyword = '';
        $page->meta_description = '';
        $page->content = '';
        $page->lang = '';
        return $page;
    }

    public function build() {
        $button = Button::withParams('Add page')
            ->onClick($this->add_action);

        $this->layout->push(Block::withParams('Actions', $button));
        $this->layout->push(Block::withParams('Pages', $this->tabs->build()));
    }

    private function getReloadTab($params, $values) {
        return $params['is_api'] ?
            $this->static_pages->getReloadAction($params, $values) :
            $this->pages->getReloadAction($params, $values);
    }

    private function createPagesTable(bool $is_api_page = false) {
        return $this
            ->createManagedTable(
                PageModel::class,
                ['ID', 'Title', 'Url', 'Lang', 'Actions'],
                Where::equal('is_api_page', (int)$is_api_page))
            ->setDataMapper(function (ModelSet $items) use ($is_api_page) {
                return $items->map(function (PageModel $item) use ($is_api_page) {
                    return [
                        $item->id,
                        $item->title,
                        $item->url,
                        $item->lang,
                        ActionSheet::withItems(
                            ActionSheetItem::withParams('Edit')->onClick($this->edit_action->use([
                                'is_api' => $is_api_page,
                                'id' => $item->id,
                            ])),
                            ActionSheetItem::withParams('Delete')->onClick($this->delete_action->use([
                                'id' => $item->id,
                                'is_api' => $is_api_page
                            ]))
                        ),
                    ];
                });
            })->setOrderBy(['id' => 'ASC']);
    }

    private function createEditAction() {
        $this->edit_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal('Edit page', $this->edit_form->setParams($request->getParams())->build());
        });
    }

    private function createAddAction() {
        $this->add_action = $this->createAction(function (ActionRequest $request) {
            return $this->showModal('Add page', $this->add_form->setParams($request->getParams())->build());
        });
    }
}
