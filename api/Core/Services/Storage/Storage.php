<?php

namespace Core\Services\Storage;

interface Storage {
    public function upload(string $file_path, string $file_name): bool;
    public function getContent(string $file_path): string;
    public function delete(string $file_path): bool;
}