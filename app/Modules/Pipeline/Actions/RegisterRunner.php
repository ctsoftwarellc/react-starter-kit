<?php

namespace App\Modules\Pipeline\Actions;

use App\Modules\Pipeline\DTOs\RegisteredRunnerData;
use App\Modules\Pipeline\DTOs\RegisterRunnerData;
use App\Modules\Pipeline\Enums\RunnerStatus;
use App\Modules\Pipeline\Models\Runner;
use Illuminate\Support\Str;

class RegisterRunner
{
    public function execute(RegisterRunnerData $data): RegisteredRunnerData
    {
        $plainTextToken = Str::random(64);

        $runner = Runner::create([
            'name' => $data->name,
            'token' => $plainTextToken,
            'token_hash' => hash('sha256', $plainTextToken),
            'status' => RunnerStatus::Offline,
            'platform' => $data->platform,
            'metadata' => $data->metadata,
        ]);

        return new RegisteredRunnerData($runner, $plainTextToken);
    }
}
