<?php

namespace Core\Services\Storage;

class FileManager {
    /** @var Storage */
    private $storage;

    const STORAGE_GOOGLE = 'google',
        STORAGE_LOCAL = 'local';

    public function __construct(string $type) {
        $storage = null;

        switch ($type) {
            case 'google':
                $storage = new GoogleStorage();
                break;
            case 'local':
                $storage = new LocalStorage();
                break;
            default:
                throw new \LogicException('Incorrect storage type');
        }

        $this->storage = $storage;
    }

    public function getStorage(): Storage {
        return $this->storage;
    }
}