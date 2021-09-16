<?php

namespace Core\Queue;

interface ShouldQueue {
    public function handle();
}