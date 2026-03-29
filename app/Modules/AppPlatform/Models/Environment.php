<?php

namespace App\Modules\AppPlatform\Models;

use App\Modules\AppPlatform\Enums\EnvironmentType;
use App\Modules\Infrastructure\Models\Cluster;
use App\Support\Concerns\HasUlid;
use Database\Factories\EnvironmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Environment extends Model
{
    use HasFactory, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => EnvironmentType::class,
            'is_auto_deploy' => 'boolean',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    public function variables(): HasMany
    {
        return $this->hasMany(EnvironmentVariable::class);
    }

    public function secrets(): HasMany
    {
        return $this->hasMany(Secret::class);
    }

    public function processDefinitions(): HasMany
    {
        return $this->hasMany(ProcessDefinition::class);
    }

    protected static function newFactory(): EnvironmentFactory
    {
        return EnvironmentFactory::new();
    }
}
