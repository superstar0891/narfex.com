<?php

namespace Core\Services\Storage;

class LocalStorage implements Storage {

    private $base_path = '';

    public function __construct(string $base_path = null) {
         $this->base_path = $base_path === null
             ? $_SERVER['DOCUMENT_ROOT'] . '/local/files/'
             : $base_path;
    }

    public function upload(string $file_path, string $file_name): bool {
        $full_path = $this->base_path . $file_name;
        $dir = pathinfo($full_path, PATHINFO_DIRNAME);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        return move_uploaded_file($file_path, $_SERVER['DOCUMENT_ROOT'] . '/local/files/' . $file_name);
    }

    /**
     * @param string $file_path
     * @return string
     * @throws FileNotFoundException
     */
    public function getContent(string $file_path): string {
        $content = file_get_contents($this->base_path . $file_path);
        if ($content === false) {
            throw new FileNotFoundException();
        }
        return $content;
    }

    /**
     * @param string $file_path
     * @return bool
     * @throws FileNotFoundException
     */
    public function delete(string $file_path): bool {
        if (!file_exists($file_path)) {
            throw new FileNotFoundException();
        }

        return unlink($file_path);
    }
}