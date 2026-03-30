<?php

namespace App\Modules\Pipeline\Actions;

use App\Modules\Pipeline\DTOs\RunnerHeartbeatData;
use App\Modules\Pipeline\Enums\RunnerStatus;
use App\Modules\Pipeline\Models\Runner;

class RecordRunnerHeartbeat
{
    public function execute(Runner $runner, RunnerHeartbeatData $data): Runner
    {
        $runner->update([
            'platform' => $data->platform ?? $runner->platform,
            'last_heartbeat_at' => now(),
            'metadata' => array_merge($runner->metadata ?? [], $data->metadata),
            'status' => $runner->status === RunnerStatus::Busy ? RunnerStatus::Busy : RunnerStatus::Online,
        ]);

        return $runner->fresh();
    }
}
