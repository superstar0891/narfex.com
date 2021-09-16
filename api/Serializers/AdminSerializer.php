<?php

namespace Serializers;

use Admin\layout\DropDown;
use Admin\layout\Input;
use Admin\layout\Select;
use Admin\layout\TableRow;
use Admin\layout\Wysiwyg;
use Api\Admin\Admin;

class AdminSerializer {
    public static function menuItem(string $title, array $params = [], array $sub_items = []): array {
        return [
            'title' => $title,
            'params' => $params,
            'sub_items' => $sub_items,
        ];
    }

    public static function wrapper(string $id, string $title, ...$items): array {
        return [
            'type' => 'wrapper',
            'id' => $id,
            'title' => $title,
            'items' => $items,
        ];
    }

    public static function action(string $type, array $params = [], array $values = [], array $options = []): array {
        $result = [
            'type' => $type,
            'params' => $params,
            'values' => $values,
        ];

        if (isset($options['confirm']) && $options['confirm']) {
            $result['confirm'] = true;
        }

        if (isset($options['confirm_title']) && $options['confirm_title']) {
            $result['confirm_title'] = $options['confirm_title'];
        }

        if (isset($options['confirm_destructive']) && $options['confirm_destructive']) {
            $result['confirm_type'] = 'destructive';
        }

        if (isset($options['need_ga_code']) && $options['need_ga_code']) {
            $result['need_ga_code'] = true;
        }

        return $result;
    }

    public static function group() {
        return [
            'type' => 'group',
            'items' => func_get_args(),
        ];
    }

    public static function button(string $title, string $type, string $size, array $params = []): array {
        return [
            'type' => 'button',
            'title' => $title,
            'button_type' => $type,
            'params' => $params,
            'size' => $size,
        ];
    }

    public static function image(string $content) {
        return [
            'type' => 'image',
            'content' => $content,
        ];
    }

    public static function text(string $text) {
        return [
            'type' => 'text',
            'text' => $text,
        ];
    }

    public static function clipboard(string $text, ?int $length) {
        return [
            'type' => 'clipboard',
            'text' => $text,
            'length' => $length,
        ];
    }

    public static function time($time) {
        return [
            'type' => 'time',
            'time' => $time,
        ];
    }

    public static function numberFormat(array $fields) {
        $result = ['type' => 'number_format'];
        foreach ($fields as $field => $value) {
            if (!is_null($value)) {
                $result[$field] = $value;
            }
        }
        return $result;
    }

    public static function title(string $title, int $level) {
        return [
            'type' => 'title',
            'title' => $title,
            'level' => $level,
        ];
    }

    public static function input(Input $input): array {
        return [
            'type' => 'input',
            'id' => $input->getName(),
            'placeholder' => $input->getPlaceholder(),
            'value' => $input->getValue(),
            'indicator' => $input->getIndicator(),
            'multiline' => $input->getMultiLine(),
            'title' => $input->getTitle(),
            'required' => $input->isRequired(),
        ];
    }

    public static function checkbox(string $id, string $title = '', $value = ''): array {
        return [
            'type' => 'checkbox',
            'id' => $id,
            'title' => $title,
            'value' => $value,
        ];
    }

    public static function wysiwyg(Wysiwyg $wysiwyg): array {
        return [
            'type' => 'wysiwyg',
            'id' => $wysiwyg->getName(),
            'title' => $wysiwyg->getTitle(),
            'value' => json_decode($wysiwyg->getValue(), true) ?? $wysiwyg->getValue(),
            'required' => $wysiwyg->isRequired(),
        ];
    }

    public static function json(string $text): array {
        return [
            'type' => 'json',
            'body' => $text,
        ];
    }

    public static function dropDown(DropDown $dropDown): array {
        $options = $dropDown->prepareOptions();
        return [
            'type' => 'drop_down',
            'id' => $dropDown->getName(),
            'placeholder' => $dropDown->getPlaceholder(),
            'options' => $options,
            'value' => $dropDown->getValue(),
            'required' => $dropDown->isRequired(),
        ];
    }

    public static function select(Select $select): array {
        $options = $select->prepareOptions();
        return [
            'type' => 'select',
            'id' => $select->getName(),
            'placeholder' => $select->getPlaceholder(),
            'title' => $select->getTitle(),
            'options' => $options,
            'value' => $select->getValue(),
            'required' => $select->isRequired(),
            'multiple' => $select->isMultiple(),
            'empty_result' => $select->getEmptyResult(),
        ];
    }

    #region ActionSheet

    public static function actionSheet($title, $actions) {
        return [
            'type' => 'action_sheet',
            'title' => $title,
            'items' => $actions,
        ];
    }

    /*
     * type:
     * default
     * destructive
     */
    public static function actionSheetItem(string $title, string $type, array $params = []) {
        return [
            'type' => 'action_sheet_item',
            'title' => $title,
            'action_type' => $type,
            'params' => $params,
        ];
    }

    #endregion

    #region Table
    public static function block(string $title, ...$items): array {
        return [
            'type' => 'block',
            'title' => $title,
            'items' => $items,
        ];
    }

    public static function table(string $id, array $header, array $rows, array $options = []) {
        $options += [
            'filters' => [],
            'paging' => null,
            'total_count' => null,
            'search' => null,
            'search_action' => null,
        ];

        $filters = [];
        foreach ($options['filters'] as $k => $v) {
            $_filters = $options['filters'];
            $_filters[$k] = '__unset';
            $filters[] = [
                'type' => 'table_filter',
                'name' => $k,
                'value' => $v,
                'params' => [
                    'action' => self::action($id . '_reload', [
                        'filters' => $_filters,
                    ])
                ]
            ];
        }

        $search = null;
        if ($options['search']) {
            $search = [
                'fields' => $options['search'],
                'action' => $options['search_action'],
            ];
        }

        return [
            'id' => $id,
            'type' => 'table',
            'header' => $header,
            'items' => $rows,
            'filters' => $filters,
            'paging' => $options['paging'],
            'total_count' => $options['total_count'],
            'search' => $search,
        ];
    }

    /*
     * params:
     * editable
     */
    public static function tableRow(string $id, string $style = TableRow::STYLE_DEFAULT, ...$items): array {
        return [
            'type' => 'table_row',
            'id' => $id,
            'items' => $items,
            'style' => $style
        ];
    }

    /*
    * params:
    * editable
    */
    public static function tableHeaderRow(): array {
        return [
            'type' => 'table_row',
            'items' => func_get_args(),
        ];
    }

    /*
     * params:
     * editable
     */
    public static function tableColumn($value, string $sub_value = '', array $params = []): array {
        return [
            'type' => 'table_column',
            'items' => $value,
            'sub_value' => $sub_value,
            'params' => $params,
        ];
    }
    #endregion

    #region Chart

    public static function chart(string $title, array $series, string $summary = null): array {
        return [
            'type' => 'chart',
            'title' => $title,
            'series' => $series,
            'summary' => $summary,
        ];
    }

    public static function chartData(string $type, array $data, string $name, string $color = null): array {
        return [
            'type' => 'chart_data',
            'chart_type' => $type,
            'data' => $data,
            'name' => $name,
            'color' => $color,
        ];
    }

    public static function chartDataItem($value, int $ts): array {
        return [
            'type' => 'chart_data_item',
            'value' => [$ts * 1000, $value]
        ];
    }

    #endregion

    #region List

    public static function list(...$items) {
        return [
            'type' => 'list',
            'items' => $items,
        ];
    }

    public static function listItem(string $label, ...$items): array {
        return [
            'type' => 'list_item',
            'label' => $label,
            'items' => $items,
        ];
    }

    #endregion

    #region Tabs

    public static function tabs() {
        return [
            'type' => 'tabs',
            'items' => func_get_args(),
        ];
    }

    public static function tabsItem(string $id, string $title, array $params = [], $content = null): array {
        if (!isset($params['action'])) {
            $params['action'] = AdminSerializer::action(Admin::ACTION_SHOW_TAB, [
                'tab' => $id,
            ]);
        }

        return [
            'type' => 'tabs_item',
            'id' => $id,
            'title' => $title,
            'items' => $content,
            'params' => $params,
        ];
    }

    #endregion

    /*
     * type - success or error
     */
    public static function toast($message, $type = 'success'): array {
        return [
            'type' => $type,
            'message' => $message,
        ];
    }

    public static function paging($items): array {
        return [
            'type' => 'paging',
            'items' => $items,
        ];
    }

    public static function pagingItem($text, $params = []): array {
        return [
            'type' => 'paging_item',
            'text' => $text,
            'params' => $params,
        ];
    }

    #region Responses


    #endregion
}
