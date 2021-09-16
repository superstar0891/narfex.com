<?php

$root = __DIR__;

/*
 * Init Kernel
 *
 * Create kernel instance:
 * - set configurations,
 * - set autoloader,
 * - set db connection,
 * - set router
 */
require_once $root . '/vendor/autoload.php';
require_once $root . '/Core/Engine/Kernel.php';
$kernel = new Engine\Kernel($root,  'config.php');

$lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr(trim($_SERVER['HTTP_ACCEPT_LANGUAGE']), 0, 2) : 'en';
\Modules\LangModule::load($lang);

require_once 'Utils.php';
