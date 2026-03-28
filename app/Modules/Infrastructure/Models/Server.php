<?php

namespace App\Modules\Infrastructure\Models;

use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Support\Concerns\HasStateMachine;
use App\Support\Concerns\HasUlid;
use Database\Factories\ServerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Server extends Model
{
    use HasFactory, HasStateMachine, HasUlid, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ServerStatus::class,
            'agent_token' => 'encrypted',
            'metadata' => 'json',
            'last_heartbeat_at' => 'datetime',
            'ssh_port' => 'integer',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function clusters(): BelongsToMany
    {
        return $this->belongsToMany(Cluster::class, 'cluster_node')
            ->withPivot(['role', 'is_active', 'sort_order'])
            ->withTimestamps();
    }

    public function agentCommands(): HasMany
    {
        return $this->hasMany(AgentCommand::class);
    }

    protected function getStatusEnum(): string
    {
        return ServerStatus::class;
    }

    protected function getAllowedTransitions(): array
    {
        return [
            'pending' => [ServerStatus::Provisioning, ServerStatus::Bootstrapping],
            'provisioning' => [ServerStatus::Bootstrapping, ServerStatus::Failed],
            'bootstrapping' => [ServerStatus::Active, ServerStatus::Failed],
            'active' => [ServerStatus::Draining, ServerStatus::Cordoned, ServerStatus::Maintenance, ServerStatus::Decommissioning],
            'draining' => [ServerStatus::Active, ServerStatus::Decommissioning],
            'cordoned' => [ServerStatus::Active, ServerStatus::Decommissioning],
            'maintenance' => [ServerStatus::Active, ServerStatus::Decommissioning],
            'decommissioning' => [ServerStatus::Decommissioned],
            'failed' => [ServerStatus::Bootstrapping, ServerStatus::Decommissioning],
        ];
    }

    protected static function newFactory(): ServerFactory
    {
        return ServerFactory::new();
    }
}
