<?php

namespace App\Modules\Pipeline\DTOs;

use App\Modules\Pipeline\Models\Runner;

class RegisteredRunnerData
{
    public function __construct(
        public Runner $runner,
        public string $plainTextToken,
    ) {}
}
