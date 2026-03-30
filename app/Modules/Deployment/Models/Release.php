<?php

namespace App\Modules\Deployment\Models;

use App\Models\User;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Enums\ReleaseStatus;
use App\Modules\Pipeline\Models\Artifact;
use App\Support\Concerns\HasStateMachine;
use App\Support\Concerns\HasUlid;
use Database\Factories\ReleaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Release extends Model
{
    use HasFactory, HasStateMachine, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ReleaseStatus::class,
            'config_snapshot' => 'json',
            'version' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function artifact(): BelongsTo
    {
        return $this->belongsTo(Artifact::class);
    }

    public function deployedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deployed_by');
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    public function activeEnvironments(): HasMany
    {
        return $this->hasMany(Environment::class, 'active_release_id');
    }

    public function scopeForEnvironment(Builder $query, Environment $environment): Builder
    {
        return $query->where('environment_id', $environment->id);
    }

    public function scopeLatestVersionFirst(Builder $query): Builder
    {
        return $query->orderByDesc('version');
    }

    protected function getStatusEnum(): string
    {
        return ReleaseStatus::class;
    }

    protected function getAllowedTransitions(): array
    {
        return [
            'pending' => [ReleaseStatus::Deploying],
            'deploying' => [ReleaseStatus::Active, ReleaseStatus::Failed],
            'active' => [ReleaseStatus::Superseded],
            'failed' => [ReleaseStatus::RolledBack],
        ];
    }

    protected static function newFactory(): ReleaseFactory
    {
        return ReleaseFactory::new();
    }
}
