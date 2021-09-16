<?php

namespace Models;

use Db\Model\Field\BooleanField;
use Db\Model\Field\CharField;
use Db\Model\Model;

/**
 * @property string url
 * @property string lang
 * @property string title
 * @property string content
 * @property string category
 * @property bool header
 * @property string meta_description
 * @property string meta_keyword
 * @property int is_api_page
 */
class PageModel extends Model {
    const WELCOME_PAGE_URL = 'introduction';
    protected static $table_name = 'pages';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'url' => CharField::init(),
            'lang' => CharField::init()->setLength(2),
            'title' =>  CharField::init(),
            'content' =>  CharField::init(),
            'category' =>  CharField::init(),
            'header' =>  BooleanField::init(),
            'meta_description' => CharField::init()->setNull(true),
            'meta_keyword' => CharField::init()->setNull(true),
            'is_api_page' => BooleanField::init()->setDefault(0),
        ];
    }
}
