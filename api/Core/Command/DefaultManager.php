<?php

namespace Core\Command;

use Core\Command\Exception\InvalidParamException;
use Core\Command\Exception\NoCommandSpecifiedException;
use Core\Command\Exception\UnknownCommandException;

class DefaultManager {
    /**
     * @param array $arguments
     *
     * @throws InvalidParamException
     * @throws NoCommandSpecifiedException
     * @throws UnknownCommandException
     */
    public static function command(array $arguments) {
        $inst = null;

        if (!isset($arguments['c'])) {
            throw new NoCommandSpecifiedException();
        }
        $command = $arguments['c'];

        switch ($command) {
            case 'migrate':
                $prefix = self::getCommandParam('prefix', $arguments, [
                    'required' => false,
                ]);
                $db = self::getCommandParam('db', $arguments, [
                    'required' => false,
                    'default' => 'mysql',
                ]);
                $inst = new Migrate($prefix, $db);
                break;
            case 'migration':
                $name = self::getCommandParam('name', $arguments);
                $prefix = self::getCommandParam('prefix', $arguments, [
                    'required' => false,
                ]);
                $db = self::getCommandParam('db', $arguments, [
                    'required' => false,
                    'default' => 'mysql'
                ]);
                $inst = new Migration($name, $prefix, $db);
                break;
            case 'cron':
                $job = self::getCommandParam('job', $arguments);
                $inst = new Cron($job);
                break;
            case 'index':
                $name = self::getCommandParam('name', $arguments);
                $inst = new Index($name);
                break;
            case 'blockchain':
                $name = self::getCommandParam('name', $arguments);
                $params = self::getCommandParam('params', $arguments, [
                    'json' => true,
                ]);
                $inst = new Blockchain($name, $params);
                break;
            case 'goglev':
                $name = self::getCommandParam('name', $arguments);
                $params = self::getCommandParam('params', $arguments, [
                    'json' => true,
                ]);
                $inst = new Goglev($name, $params);
                break;
            case 'osipov':
                $name = self::getCommandParam('name', $arguments);
                $params = self::getCommandParam('params', $arguments, [
                    'json' => true,
                    'required' => false
                ]);
                if (!is_array($params)) {
                    $params = null;
                }
                $inst = new Osipov($name, $params);
                break;
            case 'vasiliev':
                $name = self::getCommandParam('name', $arguments);
                $params = self::getCommandParam('params', $arguments, [
                    'json' => true,
                    'required' => false
                ]);
                if (!is_array($params)) {
                    $params = null;
                }
                $inst = new Vasiliev($name, $params);
                break;
            default:
                throw new UnknownCommandException();
        }

        $inst->exec();
    }

    /**
     * @param string $arg
     * @param array $arguments
     * @param array $options
     *
     * @return mixed|null
     * @throws InvalidParamException
     */
    private static function getCommandParam(string $arg, array $arguments, array $options = []) {
        $options += [
            'required' => true,
            'json' => false,
            'default' => null,
        ];
        if (isset($arguments[$arg])) {
            $value = trim($arguments[$arg]);

            if ($options['json']) {
                $value = json_decode($value, true);

                if (!$value && $options['required']) {
                    throw new InvalidParamException($arg);
                }
            }

            return $value;
        } else {
            if ($options['required']) {
                throw new InvalidParamException($arg);
            } else if ($options['default']) {
                return $options['default'];
            }
        }

        return null;
    }
}
