<?php

namespace App\Modules\Infrastructure\Models;

use App\Modules\Infrastructure\Enums\AgentCommandStatus;
use App\Modules\Infrastructure\Enums\AgentCommandType;
use App\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentCommand extends Model
{
    use HasUlid;

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => AgentCommandType::class,
            'status' => AgentCommandStatus::class,
            'payload' => 'json',
            'result' => 'json',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query
            ->where('status', AgentCommandStatus::Pending)
            ->where('expires_at', '>', now());
    }
}
