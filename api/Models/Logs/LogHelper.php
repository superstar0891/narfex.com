<?php

namespace Models\Logs;

use Exceptions\InvalidMethodException;

abstract class LogHelper implements Logs {
    /** @var string */
    private $helper;
    /** @var string */
    private $ip;
    /** @var string */
    private $browser;
    /** @var bool */
    private $device;

    public static $fields = [];

    private static $default_fields = [
        'helper',
        'ip',
        'browser',
        'device',
    ];

    public function __construct(array $extra) {
        $keys = array_keys($extra);
        if (!in_array('helper', $keys)) {
            $extra = array_merge($extra, $this->initData());
        }
        $this->setHelper($extra['helper'])
            ->setIp($extra['ip'])
            ->setBrowser($extra['browser'])
            ->setDevice($extra['device']);
    }

    private function initData(): array {
        $browser_info = getBrowserInfo();
        $user_agent = $browser_info['user_agent'];
        unset($browser_info['user_agent']);
        $is_mobile = $browser_info['is_mobile'];
        unset($browser_info['is_mobile']);
        $browser_info = array_filter($browser_info);

        $browser = !empty($browser_info) ?
            sprintf('%s %s, %s %s',
                array_get_val($browser_info, 'platform_name', ''),
                array_get_val($browser_info, 'platform_version', ''),
                array_get_val($browser_info, 'browser_name', ''),
                array_get_val($browser_info, 'browser_version', '')
            ) : $user_agent;

        return [
            'helper' => get_class($this),
            'ip' => ipAddress(),
            'browser' => $browser,
            'device' => $is_mobile ? 1 : 0
        ];
    }

    /**
     * @return string
     * @throws InvalidMethodException
     */
    public function toJson(): string {
        $json = [];
        $fields = array_merge(self::$default_fields, static::$fields);

        foreach ($fields as $field) {
            $method_name = 'get' . implode('', array_map('ucfirst', explode('_', $field)));
            if (method_exists($this, $method_name)) {
                $json[$field] = $this->$method_name();
            } else {
                throw new InvalidMethodException(
                    sprintf('Method %s does not exists in %s', $method_name, get_class($this))
                );
            }
        }

        return json_encode($json);
    }

    public function getHelper(): string {
        return $this->helper;
    }

    public function setHelper($helper): LogHelper {
        $this->helper = $helper;
        return $this;
    }

    public function getIp(): string {
        return $this->ip;
    }

    public function setIp($ip): LogHelper {
        $this->ip = $ip;
        return $this;
    }

    public function getBrowser(): ?string {
        return $this->browser;
    }

    public function setBrowser(?string $browser): LogHelper {
        $this->browser = $browser;
        return $this;
    }

    public function getDevice(): bool {
        return $this->device;
    }

    public function setDevice($device): LogHelper {
        $this->device = $device;
        return $this;
    }

}
