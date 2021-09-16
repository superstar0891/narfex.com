<?php

namespace Core\Services\Storage;

use Google\Cloud\Storage\StorageClient;

class GoogleStorage implements Storage {
    /** @var StorageClient  */
    private $client;

    /** @var string  */
    private $project_id = 'narfex-com';

    /** @var string  */
    private $bucket = 'narfex-com.appspot.com';

    public function __construct() {
        $this->client = new StorageClient([
            'projectId' => $this->project_id
        ]);
    }

    public function upload(string $file_path, string $file_name): bool {
        $bucket = $this->client->bucket($this->bucket);

        try {
            $bucket->upload(
                fopen($file_path, 'r'),
                [
                    'name' => $file_name,
                    'predefinedAcl' => 'projectPrivate'
                ]
            );
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function getContent(string $file_path): string {
        $bucket = $this->client->bucket($this->bucket);
        return $bucket->object($file_path)->downloadAsStream()->getContents();
    }

    /**
     * @param string $file_path
     * @throws FileNotFoundException
     * @return bool
     */
    public function delete(string $file_path): bool {
        $bucket = $this->client->bucket($this->bucket);

        if ($bucket->object($file_path)->exists()) {
            throw new FileNotFoundException();
        }

        $bucket->object($file_path)->delete();

        return true;
    }
}