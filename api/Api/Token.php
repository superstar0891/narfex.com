<?php

namespace Api\Token;

use Core\Response\JsonResponse;
use Models\SettingsModel;

class Token {
    public static function retrieve() {
        $period_settings = promoPeriods();
        $periods = [];

        $current_period = null;
        $current_date = time();

        foreach ($period_settings as $period_number => $settings) {
            $from = (int) $settings['from']->value;
            $to = (int) $settings['to']->value;
            if ($current_date >= $from && $current_date < $to) {
                $current_period = (int) $period_number;
            }

            foreach ($settings as $key => $setting) {
                if ($key == 'balance') {
                    continue;
                }
                if ($key == 'percent') {
                    $periods[$period_number][$key] = (float) $setting->value * 100;
                    continue;
                }
                $periods[$period_number][$key] = (float) $setting->value;
            }
        }

        $promo_code_reward = SettingsModel::getSettingByKey('coin_promo_buy_with_code_reward');

        JsonResponse::ok([
            'periods' => $periods,
            'current_period' => $current_period,
            'promo_code_reward_percent' => (float) $promo_code_reward->value * 100
        ]);
    }
}