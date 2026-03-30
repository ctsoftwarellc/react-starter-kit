<?php

namespace App\Modules\Pipeline\Models;

use App\Modules\AppPlatform\Models\Application;
use App\Modules\Deployment\Models\Release;
use App\Modules\Pipeline\Enums\ArtifactStatus;
use App\Support\Concerns\HasStateMachine;
use App\Support\Concerns\HasUlid;
use Database\Factories\ArtifactFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Artifact extends Model
{
    use HasFactory, HasStateMachine, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ArtifactStatus::class,
            'size_bytes' => 'integer',
            'metadata' => 'json',
        ];
    }

    public function pipelineRun(): BelongsTo
    {
        return $this->belongsTo(PipelineRun::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(Release::class);
    }

    public function scopeForApplication(Builder $query, Application $application): Builder
    {
        return $query->where('application_id', $application->id);
    }

    protected function getStatusEnum(): string
    {
        return ArtifactStatus::class;
    }

    protected function getAllowedTransitions(): array
    {
        return [
            'building' => [ArtifactStatus::Ready, ArtifactStatus::Failed],
            'ready' => [ArtifactStatus::Deployed, ArtifactStatus::Expired],
            'deployed' => [ArtifactStatus::Superseded, ArtifactStatus::Expired],
            'superseded' => [ArtifactStatus::Expired],
        ];
    }

    protected static function newFactory(): ArtifactFactory
    {
        return ArtifactFactory::new();
    }
}
