<?php

namespace App\Modules\Operations\Models;

use App\Modules\Infrastructure\Models\Server;
use App\Modules\Operations\Enums\BackupStatus;
use App\Support\Concerns\HasStateMachine;
use App\Support\Concerns\HasUlid;
use Database\Factories\BackupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    use HasFactory, HasStateMachine, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => BackupStatus::class,
            'size_bytes' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'retention_days' => 'integer',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    protected function getStatusEnum(): string
    {
        return BackupStatus::class;
    }

    protected function getAllowedTransitions(): array
    {
        return [
            'pending' => [BackupStatus::Running, BackupStatus::Failed],
            'running' => [BackupStatus::Completed, BackupStatus::Failed],
            'completed' => [BackupStatus::Expired],
            'failed' => [],
            'expired' => [],
        ];
    }

    protected static function newFactory(): BackupFactory
    {
        return BackupFactory::new();
    }
}
