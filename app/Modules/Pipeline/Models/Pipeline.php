<?php

namespace App\Modules\Pipeline\Models;

use App\Modules\AppPlatform\Models\Application;
use App\Support\Concerns\HasUlid;
use Database\Factories\PipelineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pipeline extends Model
{
    use HasFactory, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'definition' => 'json',
            'is_active' => 'boolean',
            'trigger_branches' => 'json',
            'trigger_events' => 'json',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(PipelineRun::class);
    }

    protected static function newFactory(): PipelineFactory
    {
        return PipelineFactory::new();
    }
}
