<?php

namespace App\Modules\Deployment\Models;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Enums\RemoteCommandStatus;
use App\Modules\Deployment\Enums\RemoteCommandType;
use App\Modules\Infrastructure\Models\Server;
use App\Support\Concerns\HasUlid;
use Database\Factories\RemoteCommandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemoteCommand extends Model
{
    use HasFactory, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => RemoteCommandType::class,
            'status' => RemoteCommandStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    protected static function newFactory(): RemoteCommandFactory
    {
        return RemoteCommandFactory::new();
    }
}
