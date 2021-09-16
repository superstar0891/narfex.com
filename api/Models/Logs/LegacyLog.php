<?php

namespace Models\Logs;

class LegacyLog extends LogHelper {
    public static $fields = [];

    public function tableColumn(): string {
        return sprintf("old logging");
    }
}
