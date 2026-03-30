<?php

namespace App\Modules\Deployment\Models;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Enums\HealthCheckType;
use App\Support\Concerns\HasUlid;
use Database\Factories\HealthCheckFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthCheck extends Model
{
    use HasFactory, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => HealthCheckType::class,
            'interval_seconds' => 'integer',
            'timeout_seconds' => 'integer',
            'healthy_threshold' => 'integer',
            'unhealthy_threshold' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    protected static function newFactory(): HealthCheckFactory
    {
        return HealthCheckFactory::new();
    }
}
