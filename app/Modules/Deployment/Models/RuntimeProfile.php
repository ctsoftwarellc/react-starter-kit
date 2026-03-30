<?php

namespace App\Modules\Deployment\Models;

use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Enums\RuntimeProfileStack;
use App\Support\Concerns\HasUlid;
use Database\Factories\RuntimeProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RuntimeProfile extends Model
{
    use HasFactory, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'stack' => RuntimeProfileStack::class,
            'config' => 'json',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function environments(): HasMany
    {
        return $this->hasMany(Environment::class);
    }

    protected static function newFactory(): RuntimeProfileFactory
    {
        return RuntimeProfileFactory::new();
    }
}
