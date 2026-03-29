<?php

namespace App\Modules\ServiceManagement\Models;

use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\ServiceManagement\Enums\CacheEngine;
use App\Support\Concerns\HasUlid;
use Database\Factories\CacheInstanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CacheInstance extends Model
{
    use HasFactory, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'engine' => CacheEngine::class,
            'port' => 'integer',
            'password' => 'encrypted',
        ];
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    public function serviceBindings(): HasMany
    {
        return $this->hasMany(ServiceBinding::class);
    }

    protected static function newFactory(): CacheInstanceFactory
    {
        return CacheInstanceFactory::new();
    }
}
