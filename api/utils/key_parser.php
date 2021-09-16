<?php


function getDirContents($dir, &$results = array()) {

    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            if (strpos($path, '.php') !== false) {
                $results[] = $path;
            }
        } else if ($value != "." && $value != "..") {
            if (strpos($path, '/alexxosipov/projects/narfex/vendor') !== false) {
                continue;
            }
            if (strpos($path, 'narfex/Database/Migrations') !== false) {
                continue;
            }
            getDirContents($path, $results);
        }
    }

    return $results;
}

$files = getDirContents('./');
$keys = [];
foreach ($files as $file) {
    $strings = file($file);
    foreach ($strings as $string) {
        if (strpos($string, "lang('") !== false) {
            $regex = '#lang\(\'(.*?)\'#';
            $code = preg_match($regex, $string, $matches);
            if (isset($matches[1])) {
                if ($matches[1] !== '$message') {
                    $keys[] = $matches[1];
                }
            }
        }

        if (strpos($string, "errorMessage('") !== false) {
            $regex = '#errorMessage\(\'(.*?)\'#';
            $code = preg_match($regex, $string, $matches);
            if (isset($matches[1])) {
                $keys[] = $matches[1];
            }
        }
    }
}

foreach ($keys as $key => $value) {
    if (strpos($value, ' ') !== false) {
        unset($keys[$key]);
    }
}

$keys = array_unique($keys);
var_dump($keys);
$keys = implode(',', $keys);
file_put_contents('keys.txt', $keys);
