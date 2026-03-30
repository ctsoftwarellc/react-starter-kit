<?php

namespace App\Modules\Deployment\Models;

use App\Modules\Deployment\Enums\DeploymentStepStatus;
use App\Modules\Infrastructure\Models\Server;
use App\Support\Concerns\HasUlid;
use Database\Factories\DeploymentStepFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeploymentStep extends Model
{
    use HasFactory, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => DeploymentStepStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function scopeForDeployment(Builder $query, Deployment $deployment): Builder
    {
        return $query->where('deployment_id', $deployment->id);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->select('deployment_steps.*')
            ->join('deployments', 'deployments.id', '=', 'deployment_steps.deployment_id')
            ->join('environments', 'environments.id', '=', 'deployments.environment_id')
            ->join('cluster_node', function ($join) {
                $join->on('cluster_node.server_id', '=', 'deployment_steps.server_id')
                    ->on('cluster_node.cluster_id', '=', 'environments.cluster_id');
            })
            ->orderBy('cluster_node.sort_order');
    }

    protected static function newFactory(): DeploymentStepFactory
    {
        return DeploymentStepFactory::new();
    }
}
