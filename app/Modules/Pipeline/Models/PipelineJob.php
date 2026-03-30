<?php

namespace App\Modules\Pipeline\Models;

use App\Modules\Pipeline\Enums\PipelineJobStatus;
use App\Support\Concerns\HasStateMachine;
use App\Support\Concerns\HasUlid;
use Database\Factories\PipelineJobFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PipelineJob extends Model
{
    use HasFactory, HasStateMachine, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => PipelineJobStatus::class,
            'commands' => 'json',
            'environment' => 'json',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'exit_code' => 'integer',
        ];
    }

    public function pipelineRun(): BelongsTo
    {
        return $this->belongsTo(PipelineRun::class);
    }

    public function runner(): BelongsTo
    {
        return $this->belongsTo(Runner::class);
    }

    public function scopeRunnable(Builder $query): Builder
    {
        return $query
            ->where('status', PipelineJobStatus::Queued->value)
            ->where(function (Builder $query) {
                $query->whereNull('runner_id')
                    ->orWhereHas('runner', fn (Builder $runnerQuery) => $runnerQuery->where('status', '!=', 'draining'));
            });
    }

    public function scopeForRun(Builder $query, PipelineRun $run): Builder
    {
        return $query->where('pipeline_run_id', $run->id);
    }

    protected function getStatusEnum(): string
    {
        return PipelineJobStatus::class;
    }

    protected function getAllowedTransitions(): array
    {
        return [
            'pending' => [PipelineJobStatus::Queued, PipelineJobStatus::Skipped],
            'queued' => [PipelineJobStatus::Assigned, PipelineJobStatus::Cancelled],
            'assigned' => [PipelineJobStatus::Running, PipelineJobStatus::Cancelled],
            'running' => [PipelineJobStatus::Succeeded, PipelineJobStatus::Failed, PipelineJobStatus::Cancelled, PipelineJobStatus::TimedOut],
        ];
    }

    protected static function newFactory(): PipelineJobFactory
    {
        return PipelineJobFactory::new();
    }
}
