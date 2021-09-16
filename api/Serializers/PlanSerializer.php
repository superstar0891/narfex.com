<?php

namespace Serializers;

use Models\PlanModel;

class PlanSerializer {
    public static function listItem(PlanModel $plan, $drop_coeff = 1): array {
        $percent = $plan->percent * $plan->days * $drop_coeff;
        return [
            'id' => (int) $plan->id,
            'description' => $plan->description,
            'percent' => (double) round($percent, 2, PHP_ROUND_HALF_DOWN),
            'bonus' => (double) max(0, round($percent - ($plan->percent * $plan->days), 2, PHP_ROUND_HALF_DOWN)),
            'days' => (int) $plan->days,
            'min' => (double) $plan->min,
            'max' => (double) $plan->max,
        ];
    }
}
