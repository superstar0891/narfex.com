<?php

use Core\App;
use Core\Route\Route;
use Core\Services\DynamicConfig\DynamicConfig;
use Core\Services\GoogleAuth\GoogleAuth;
use Core\Services\Redis\RedisAdapter;
use Middlewares\Middlewares;
use Models\PermissionModel;
use Models\SettingsModel;
use Models\UserRoleModel;

/**
 * Get parameter from $request
 * Shorthand for $request['data']->get(...)
 *
 * @param array $request
 * @param string $name
 * @param array $filters
 *
 * @return mixed
 */
function getParam(array $request, string $name, array $filters = []) {
    return $request['data']->get($name, $filters);
}

/**
 * Get several parameters from $request
 *
 * @param array $request
 * @param array $params
 *
 * @return array
 */
function getParams(array $request, array $params) {
    $return_params = [];
    foreach ($params as $param_name => $param_filters) {
        $return_params[$param_name] = getParam($request, $param_name, $param_filters);
    }
    return $return_params;
}

/**
 * @param array $request
 *
 * @return \Models\UserModel|null
 */
function getUser(array $request) {
    return isset($request['user']) ? $request['user'] : null;
}

/**
 * Shorthand for creating CRUD routes with access permissions
 *
 * @param string $model
 * @param string $path
 * @param string $controller
 *
 * @return Route[]
 * @throws Exception
 */
function CRUDRoutes(string $model, string $path, string $controller): array {
    /**
     * @var $routes Route[]
     */
    $routes = Route::crud($path, $controller);

    return [
        $routes['create']->middleware(Middlewares::Permission,
                                      PermissionModel::permissionName($model, 'create')),

        $routes['read']->middleware(Middlewares::Permission,
                                    PermissionModel::permissionName($model, 'read')),

        $routes['list']->middleware(Middlewares::Permission,
                                    PermissionModel::permissionName($model, 'list')),

        $routes['update']->middleware(Middlewares::Permission,
                                      PermissionModel::permissionName($model, 'update')),

        $routes['delete']->middleware(Middlewares::Permission,
                                      PermissionModel::permissionName($model, 'delete')),
    ];
}

function maskPhoneNumber($code, $number) {
    return '+' . $code . substr($number, 0, 3) . '****' . substr($number, -3);
}

function maskEmail($email) {
    $em   = explode('@', $email);
    $name = implode(array_slice($em, 0, count($em)-1), '@');
    $len  = floor(strlen($name)/2);

    return substr($name,0, $len) . str_repeat('*', $len) . "@" . end($em);
}

function ipAddress(): string {
    if (isset($_SERVER['HTTP_X_APPENGINE_USER_IP'])) {
        return $_SERVER['HTTP_X_APPENGINE_USER_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $exp = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($exp[0]);
    }
    if (isset($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }

    return '127.0.0.1';
}

function lang($name, array $values = []): string {
    $lang_text = isset(\Modules\LangModule::$lang[$name]) ? htmlspecialchars_decode(\Modules\LangModule::$lang[$name]) : $name;

    foreach ($values as $key => $value) {
        $lang_text = str_replace("{{$key}}", $value, $lang_text);
    }

    return $lang_text;
}

function settings(): \Models\SiteSettingsModel {
    static $settings = null;

    if ($settings !== null) {
        return $settings;
    }

    return $settings = \Models\SiteSettingsModel::get(1);
}


function currencies(): array {
    static $currencies = null;

    if ($currencies !== null) {
        return $currencies;
    }

    $currencies = [];
    foreach (blockchain_currencies() as $c => $c_name) {
        $rate =  \Models\WalletModel::getRate('btc', $c);
        $currencies[$c] = [
            'curr' => $c,
            'name' => $c_name,
            'withdraw_daily_max' => settings()->wallet_withdraw_daily_max * $rate,
            'fast_withdraw_threshold' => settings()->deposit_fast_withdraw_threshold * $rate,
            'profit_drop' => $rate > 0 ? settings()->deposit_profit_drop / $rate : 0,
        ];
    }

    return $currencies;
}

function blockchain_currencies() {
    return ['btc' => 'bitcoin', 'eth' => 'ethereum', 'ltc' => 'litecoin'];
}

function fiat_currencies() {
    return [
        CURRENCY_IDR => ucfirst(CURRENCY_IDR), CURRENCY_USD => ucfirst(CURRENCY_USD),
        CURRENCY_RUB => ucfirst(CURRENCY_RUB), CURRENCY_EUR => ucfirst(CURRENCY_EUR)
    ];
}

function getDomain() {
    if (!isset($_SERVER['HTTP_HOST'])) {
        return null;
    }

    $parsed = parse_url($_SERVER['HTTP_HOST']);
    if (isset($parsed['host'])) {
        return $parsed['host'];
    } else {
        return $_SERVER['HTTP_HOST'];
    }
}

function isMobile() {
    return getUserAgentParser()->isMobile();
}

function getFloodControlPeriod(array $rules) {
    static $periods = [
        '1s' => 2,
        '2s' => 2,
        '15s' => 15,
        '1m' => 60,
        '15m' => 60 * 15,
        '1h' => 3600,
        'day' => 86400
    ];

    foreach ($rules as $period => $limit) {
        if (!isset($periods[$period])) {
            throw new Exception();
        }
    }

    $limit = array_filter($rules, function ($key) {return $key !== 'day';}, ARRAY_FILTER_USE_KEY);
    $key = current(array_keys($limit));
    $val = current($limit);

    return intdiv($periods[$key], $val);
}

/**
 * @param string $key
 * @param array $rules
 * @return bool|int
 * @throws Exception
 */
function floodControlWithExpiredAt(string $key, array $rules) {
    if (!App::isFloodControlEnabled()) {
        return true;
    }
    static $periods = [
        '1s' => 2,
        '2s' => 2,
        '15s' => 15,
        '1m' => 60,
        '15m' => 60 * 15,
        '1h' => 3600,
        'day' => 86400
    ];

    $key_prefix = 'flood:' . $key . ':attempts:';
    foreach ($rules as $period => $limit) {
        if (!isset($periods[$period])) {
            throw new Exception('Incorrect period');
        }

        $key_full = $key_prefix . ':' . $period . ':';

        if (count(RedisAdapter::shared()->keys($key_full . '*')) >= $limit) {
            $keys = RedisAdapter::shared()->keys($key_full . '*');
            return time() + RedisAdapter::shared()->ttl($keys[0]);
        }
        RedisAdapter::shared()->set($key_full . uniqid(), 1, $periods[$period]);
    }

    return true;
}

function floodControl(string $key, array $rules): bool {
    if (!App::isFloodControlEnabled()) {
        return true;
    }

    static $periods = [
        '1s' => 2,
        '2s' => 2,
        '15s' => 15,
        '1m' => 60,
        '15m' => 60 * 15,
        '1h' => 3600,
        'day' => 86400
    ];

    $key_prefix = 'flood:' . $key . ':attempts:';
    foreach ($rules as $period => $limit) {
        if (!isset($periods[$period])) {
            throw new Exception();
        }

        $key_full = $key_prefix . ':' . $period . ':';

       if (count(RedisAdapter::shared()->keys($key_full . '*')) >= $limit) {
           return false;
       }

        RedisAdapter::shared()->set($key_full . uniqid(), 1, $periods[$period]);
    }

    return true;
}

function positive($value) {
    if (is_numeric($value) && $value > 0) {
        return $value;
    }
    return 0;
}

function getUserAgentParser() {
    static $parser = null;

    if ($parser !== null) {
        return $parser;
    }

    return isset($_SERVER['HTTP_USER_AGENT']) ? new WhichBrowser\Parser($_SERVER['HTTP_USER_AGENT']) : null;
}

function getBrowserInfo() {
    $parser = getUserAgentParser();

    if (!$parser) {
        return [
            'browser_name'     => null,
            'browser_version'  => null,
            'platform_name'    => null,
            'platform_version' => null,
            'is_mobile'        => null,
            'user_agent'       => null
        ];
    }

    $browser_name = '';
    $browser_version = '';
    $os_name = '';
    $os_version = '';

    if (isset($parser->browser->name)) {
        $browser_name =  $parser->browser->name;
    }
    if (isset($parser->browser->version)) {
        $browser_version =  $parser->browser->version->value;
    }
    if (isset($parser->os->name)) {
        $os_name =  $parser->os->name;
    }
    if (isset($parser->os->version)) {
        $os_version =  $parser->os->version->value;
    }

    return [
        'browser_name'     => $browser_name,
        'browser_version'  => $browser_version,
        'platform_name'    => $os_name,
        'platform_version' => $os_version,
        'is_mobile'        => $parser->isMobile(),
        'user_agent'       => $_SERVER['HTTP_USER_AGENT']
    ];
}

function getLang() {
    global $lang;

    return $lang;
}

function formatNum($number, $decimals = 8, $dec_point = '.') {
    return number_format($number, $decimals, $dec_point, ' ');
}

function getRandomString($length = 16) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';

    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[mt_rand(0, strlen($characters) - 1)];
    }

    return $string;
}

function string_to_int(string $val) {
    return (int) $val;
}

function array_get_val(array $array, $field, $default = null) {
    return isset($array[$field]) ? $array[$field] : $default;
}

function dd($val) {
    echo "<pre>";
    var_dump($val);
    echo "</pre>";
    die();
}

/* @param string $key_name
 * @return mixed
 */
function dynamicConfig(string $key_name) {
    return DynamicConfig::shared()->getKey($key_name);
}

function getRoles(): \Db\Model\ModelSet {
    static $roles;

    if (!$roles) {
        $roles = UserRoleModel::select();
    }

    return $roles;
}

function getPermissions(): \Db\Model\ModelSet {
    static $permissions;

    if (!$permissions) {
        $permissions = \Models\UserPermissionModel::select();
    }

    return $permissions;
}

function encrypt($plaintext): string {
    $key = KERNEL_CONFIG['crypto']['key'];
    $method = KERNEL_CONFIG['crypto']['method'];
    $iv = KERNEL_CONFIG['crypto']['iv'];
    if (in_array($method, openssl_get_cipher_methods()))  {
        return openssl_encrypt($plaintext, $method, $key, true, $iv);
    } else {
        throw new \Exception('Not found chipher method');
    }
}

function decrypt($ciphertext): string {
    $key = KERNEL_CONFIG['crypto']['key'];
    $method = KERNEL_CONFIG['crypto']['method'];
    $iv = KERNEL_CONFIG['crypto']['iv'];
    if (in_array($method, openssl_get_cipher_methods()))  {
        return openssl_decrypt($ciphertext, $method, $key, true, $iv);
    } else {
        throw new \Exception('Not found chipher method');
    }
}

function checkGoogleAuth($ga_code, $ga_hash) {
    $ga = new GoogleAuth();
    return $ga_code === $ga->getCode($ga_hash);
}

function getSubDomain(): string {
    static $sub_domain = null;

    if ($sub_domain !== null) {
        return $sub_domain;
    }

    if (preg_match('/([a-z]+)\.narfex\.dev/', $_SERVER['HTTP_HOST'], $matches)) {
        $sub_domain = $matches[1];
    } else {
        $sub_domain = '';
    }

    return $sub_domain;
}

function promoPeriods() {
    static $periods = null;

    if ($periods !== null) {
        return $periods;
    }

    $periods = [
        0 => [
            'from' => SettingsModel::getSettingByKey('coin_promo_first_period_from'),
            'to' => SettingsModel::getSettingByKey('coin_promo_first_period_to'),
            'percent' => SettingsModel::getSettingByKey('coin_promo_first_period'),
            'balance' => SettingsModel::getSettingByKey('coin_promo_first_period_balance'),
            'bank' => SettingsModel::getSettingByKey('coin_promo_first_period_bank'),
        ],
        1 => [
            'from' => SettingsModel::getSettingByKey('coin_promo_second_period_from'),
            'to' => SettingsModel::getSettingByKey('coin_promo_second_period_to'),
            'percent' => SettingsModel::getSettingByKey('coin_promo_second_period'),
            'balance' => SettingsModel::getSettingByKey('coin_promo_second_period_balance'),
            'bank' => SettingsModel::getSettingByKey('coin_promo_second_period_bank'),
        ],
        2 => [
            'from' => SettingsModel::getSettingByKey('coin_promo_third_period_from'),
            'to' => SettingsModel::getSettingByKey('coin_promo_third_period_to'),
            'percent' => SettingsModel::getSettingByKey('coin_promo_third_period'),
            'balance' => SettingsModel::getSettingByKey('coin_promo_third_period_balance'),
            'bank' => SettingsModel::getSettingByKey('coin_promo_third_period_bank'),
        ],
    ];

    return $periods;
}

function matchSubstrInText(string $text, string $substr, int $misses = 3) {
    $substr_length = strlen($substr);
    do {
        $base_string_length = strlen($text);

        $check_text = substr($text, 0, $substr_length);
        $length_available = $base_string_length > $substr_length;

        $levenshtein = levenshtein($check_text, $substr, 1, 1, 0);
        $is_match = $levenshtein <= $misses;

        $text = substr($text, 1);
    } while (!$is_match && $length_available);

    return $is_match;
}

function getPlatform(): string {
//    $platform = PLATFORM_FINDIRI;
//    if (App::isBitcoinovnet()) {
//        $platform = PLATFORM_BITCOINOVNET;
//    }

    return PLATFORM_BITCOINOVNET;
}

function bitcoinovnetIsActive() {
    return (bool) settings()->bitcoinovnet_active === true;
}

function maskCreditCard(string $cc) {
    $cc_length = strlen($cc);

    $cc_arr = str_split($cc);

    foreach ($cc_arr as $k => $v) {
        if ($k == 3) {
            $cc_arr[$k] = $cc_arr[$k] . ' ';
            continue;
        }
        if ($k > 3 && $k < $cc_length - 4) {
            if (($k + 1) % 4 == 0) {
                $cc_arr[$k] = '* ';
            } else {
                $cc_arr[$k] = '*';
            }
        }
    }

    return implode('', $cc_arr);
}

/**
 * @param  string $ip    IP
 * @param  string $range IP/CIDR
 * @return boolean
 */
function checkIpInRange($ip, $range) {
    if (strpos( $range, '/' ) === false) {
        $range .= '/32';
    }

    list($range, $netmask) = explode('/', $range, 2);
    $range_decimal = ip2long($range);
    $ip_decimal = ip2long($ip);
    $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
    $netmask_decimal = ~ $wildcard_decimal;
    return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
}
