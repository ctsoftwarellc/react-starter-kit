<?php

namespace App\Modules\Deployment\Models;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Infrastructure\Enums\NodeRole;
use App\Support\Concerns\HasUlid;
use Database\Factories\ServerRoleProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerRoleProfile extends Model
{
    use HasFactory, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'role' => NodeRole::class,
            'config' => 'json',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    protected static function newFactory(): ServerRoleProfileFactory
    {
        return ServerRoleProfileFactory::new();
    }
}
