<?php

namespace Models\Logs;

interface Logs {
    public function toJson(): string;
    public function tableColumn(): string;
}