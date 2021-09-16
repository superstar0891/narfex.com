<?php

namespace Core\Services\Storage;

class FileNotFoundException extends \Exception {
    protected $message = 'file not found';
}