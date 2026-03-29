<?php

namespace App\Modules\Infrastructure\Models;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Infrastructure\Enums\ClusterStatus;
use App\Modules\ServiceManagement\Models\CacheInstance;
use App\Modules\ServiceManagement\Models\DatabaseInstance;
use App\Support\Concerns\HasStateMachine;
use App\Support\Concerns\HasUlid;
use Database\Factories\ClusterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cluster extends Model
{
    use HasFactory, HasStateMachine, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ClusterStatus::class,
            'settings' => 'json',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function servers(): BelongsToMany
    {
        return $this->belongsToMany(Server::class, 'cluster_node')
            ->withPivot(['role', 'is_active', 'sort_order'])
            ->withTimestamps();
    }

    public function environments(): HasMany
    {
        return $this->hasMany(Environment::class);
    }

    public function databaseInstances(): HasMany
    {
        return $this->hasMany(DatabaseInstance::class);
    }

    public function cacheInstances(): HasMany
    {
        return $this->hasMany(CacheInstance::class);
    }

    protected function getStatusEnum(): string
    {
        return ClusterStatus::class;
    }

    protected function getAllowedTransitions(): array
    {
        return [
            'pending' => [ClusterStatus::Provisioning],
            'provisioning' => [ClusterStatus::Active],
            'active' => [ClusterStatus::Updating, ClusterStatus::Scaling, ClusterStatus::Degraded, ClusterStatus::Maintenance, ClusterStatus::Decommissioning],
            'updating' => [ClusterStatus::Active, ClusterStatus::Degraded],
            'scaling' => [ClusterStatus::Active],
            'degraded' => [ClusterStatus::Active],
            'maintenance' => [ClusterStatus::Active],
            'decommissioning' => [ClusterStatus::Decommissioned],
        ];
    }

    protected static function newFactory(): ClusterFactory
    {
        return ClusterFactory::new();
    }
}
