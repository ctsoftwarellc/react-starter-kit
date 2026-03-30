<?php

namespace App\Modules\Pipeline\Models;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Pipeline\DTOs\TriggerPipelineRunData;
use App\Modules\Pipeline\Enums\PipelineRunStatus;
use App\Modules\Pipeline\Enums\TriggerType;
use App\Support\Concerns\HasStateMachine;
use App\Support\Concerns\HasUlid;
use Database\Factories\PipelineRunFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PipelineRun extends Model
{
    use HasFactory, HasStateMachine, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => PipelineRunStatus::class,
            'trigger_type' => TriggerType::class,
            'definition_snapshot' => 'json',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(PipelineJob::class);
    }

    public function artifact(): HasOne
    {
        return $this->hasOne(Artifact::class);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeForPipeline(Builder $query, Pipeline $pipeline): Builder
    {
        return $query->where('pipeline_id', $pipeline->id);
    }

    public function toRetryData(): TriggerPipelineRunData
    {
        return new TriggerPipelineRunData(
            triggerType: $this->trigger_type,
            environmentId: $this->environment_id,
            triggerRef: $this->trigger_ref,
            triggerSha: $this->trigger_sha,
            triggerActor: $this->trigger_actor,
        );
    }

    protected function getStatusEnum(): string
    {
        return PipelineRunStatus::class;
    }

    protected function getAllowedTransitions(): array
    {
        return [
            'pending' => [PipelineRunStatus::Running, PipelineRunStatus::Cancelled],
            'running' => [PipelineRunStatus::Succeeded, PipelineRunStatus::Failed, PipelineRunStatus::Cancelled, PipelineRunStatus::TimedOut],
        ];
    }

    protected static function newFactory(): PipelineRunFactory
    {
        return PipelineRunFactory::new();
    }
}
