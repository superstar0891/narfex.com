<?php

namespace Api\Image;

function retrieve($request) {
    /* @var string $object */
    extract($request['params']);

    list(, $object_name) = explode('/', $object);

    $storage = new \Google\Cloud\Storage\StorageClient([
        'projectId' => 'narfex-com'
    ]);
    $bucket = $storage->bucket('narfex');
    $obj = $bucket->object($object_name);

    $image = imagecreatefromstring($obj->downloadAsString());

    $o_width = imagesx($image);
    $o_height = imagesy($image);

    $ratio = $o_width / $o_height;
    if ($ratio > 1) {
        $width = 300;
        $height = 300 / $ratio;
    } else {
        $width = 300 * $ratio;
        $height = 300;
    }

    $dst = imagecreatetruecolor($width, $height);
    imagecopyresampled($dst, $image,0,0,0,0, $width, $height, $o_width, $o_height);
    imagedestroy($image);

    header('Content-Type: image/jpeg');
    imagejpeg($dst);

    imagedestroy($dst);
    exit;
}
