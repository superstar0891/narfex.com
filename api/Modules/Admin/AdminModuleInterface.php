<?php

namespace Modules\Admin;

interface AdminModuleInterface {
    public static function getName(): string;
    public function page(): array;
}
