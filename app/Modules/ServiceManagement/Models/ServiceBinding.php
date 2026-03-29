<?php

namespace App\Modules\ServiceManagement\Models;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\ServiceManagement\Enums\ServiceType;
use App\Support\Concerns\HasUlid;
use Database\Factories\ServiceBindingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceBinding extends Model
{
    use HasFactory, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'service_type' => ServiceType::class,
            'config' => 'json',
        ];
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function databaseInstance(): BelongsTo
    {
        return $this->belongsTo(DatabaseInstance::class);
    }

    public function cacheInstance(): BelongsTo
    {
        return $this->belongsTo(CacheInstance::class);
    }

    public function storageBucket(): BelongsTo
    {
        return $this->belongsTo(StorageBucket::class);
    }

    public function service(): DatabaseInstance|CacheInstance|StorageBucket|null
    {
        return match ($this->service_type) {
            ServiceType::Database => $this->databaseInstance,
            ServiceType::Cache => $this->cacheInstance,
            ServiceType::Storage => $this->storageBucket,
        };
    }

    protected static function newFactory(): ServiceBindingFactory
    {
        return ServiceBindingFactory::new();
    }
}
