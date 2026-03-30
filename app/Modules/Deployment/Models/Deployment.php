<?php

namespace App\Modules\Deployment\Models;

use App\Models\User;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Enums\DeploymentStrategy;
use App\Support\Concerns\HasStateMachine;
use App\Support\Concerns\HasUlid;
use Database\Factories\DeploymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deployment extends Model
{
    use HasFactory, HasStateMachine, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => DeploymentStatus::class,
            'strategy' => DeploymentStrategy::class,
            'total_nodes' => 'integer',
            'completed_nodes' => 'integer',
            'failed_nodes' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(DeploymentStep::class);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeForEnvironment(Builder $query, Environment $environment): Builder
    {
        return $query->where('environment_id', $environment->id);
    }

    protected function getStatusEnum(): string
    {
        return DeploymentStatus::class;
    }

    protected function getAllowedTransitions(): array
    {
        return [
            'pending' => [DeploymentStatus::Preparing, DeploymentStatus::Cancelled],
            'preparing' => [DeploymentStatus::Deploying, DeploymentStatus::Failed],
            'deploying' => [DeploymentStatus::Verifying, DeploymentStatus::Failed],
            'verifying' => [DeploymentStatus::Succeeded, DeploymentStatus::Failed],
            'failed' => [DeploymentStatus::RolledBack],
        ];
    }

    protected static function newFactory(): DeploymentFactory
    {
        return DeploymentFactory::new();
    }
}
