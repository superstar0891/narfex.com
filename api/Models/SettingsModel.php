<?php

namespace Models;

use Db\Model\Field\CharField;
use Db\Model\Field\TextField;
use Db\Model\Model;
use Db\Model\ModelSet;

/**
 * @property string name
 * @property string group_name
 * @property string value
 * @property string description
 */
class SettingsModel extends Model {
    protected static $table_name = 'settings';

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'name' => CharField::init()->setLength(255),
            'group_name' => CharField::init()->setLength(255),
            'value' => CharField::init()->setLength(255),
            'description' => TextField::init(),
        ];
    }

    public static function getSettings(): array {
        static $settings = null;

        if ($settings !== null) {
            return $settings;
        }

        $settings_models = self::select();

        foreach ($settings_models as $setting) {
            /** @var SettingsModel  $setting*/
            $settings[$setting->name] = $setting;
        }

        return $settings;
    }

    public function toJson(): array {
        return [
            'key' => $this->name,
            'value' => $this->value,
            'group' => $this->group_name,
            'description' => $this->description,
        ];
    }

    public static function getSettingByKey(string $key): ?SettingsModel {
        return array_get_val(self::getSettings(), $key);
    }
}
