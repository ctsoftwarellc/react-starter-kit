<?php

namespace App\Modules\AppPlatform\Models;

use App\Modules\AppPlatform\Enums\Runtime;
use App\Modules\Deployment\Models\RuntimeProfile;
use App\Modules\Pipeline\Models\Artifact;
use App\Modules\Pipeline\Models\Pipeline;
use App\Modules\Pipeline\Models\Webhook;
use App\Support\Concerns\HasUlid;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Application extends Model
{
    use HasFactory, HasUlid, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'runtime' => Runtime::class,
            'settings' => 'json',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function gitConnection(): BelongsTo
    {
        return $this->belongsTo(GitConnection::class);
    }

    public function environments(): HasMany
    {
        return $this->hasMany(Environment::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    public function pipelines(): HasMany
    {
        return $this->hasMany(Pipeline::class);
    }

    public function artifacts(): HasMany
    {
        return $this->hasMany(Artifact::class);
    }

    public function runtimeProfiles(): HasMany
    {
        return $this->hasMany(RuntimeProfile::class);
    }

    public function scopeForProject(Builder $query, Project $project): Builder
    {
        return $query->where('project_id', $project->id);
    }

    protected static function newFactory(): ApplicationFactory
    {
        return ApplicationFactory::new();
    }
}
