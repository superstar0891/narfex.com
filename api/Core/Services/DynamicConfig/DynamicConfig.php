<?php

namespace Core\Services\DynamicConfig;

use Google\Cloud\Datastore\DatastoreClient;

class DynamicConfig {

    /**
     * @var DynamicConfig
     */
    private static $inst = null;

    private $datastore;

    public function __construct() {
        try {
            $this->datastore = new DatastoreClient();
        } catch (\Exception $e) { }
    }

    public static function shared() {
        if (self::$inst === null) {
            self::$inst = new DynamicConfig();
        }

        return self::$inst;
    }


    function getKey(string $name) {
        static $keys = null;

        if ($keys === null) {
            try {
                if ($this->datastore === null) {
                    throw new \Exception();
                }
                $key = $this->datastore->key(getenv('CONFIG_ENV'), 'findiri');
                $entity = $this->datastore->lookup($key);
                $keys = $entity->get();
            } catch (\Exception $e) {
                $keys = [];
            }
        }

        return isset($keys[$name]) ? $keys[$name] : getenv($name);
    }
}
