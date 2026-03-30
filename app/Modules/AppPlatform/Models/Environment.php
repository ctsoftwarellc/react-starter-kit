<?php

namespace App\Modules\AppPlatform\Models;

use App\Modules\AppPlatform\Enums\EnvironmentType;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\HealthCheck;
use App\Modules\Deployment\Models\Release;
use App\Modules\Deployment\Models\RemoteCommand;
use App\Modules\Deployment\Models\RuntimeProfile;
use App\Modules\Deployment\Models\ServerRoleProfile;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\Networking\Models\Domain;
use App\Modules\Pipeline\Models\PipelineRun;
use App\Modules\ServiceManagement\Models\ServiceBinding;
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
            'active_release_id' => 'string',
            'runtime_profile_id' => 'string',
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

    public function activeRelease(): BelongsTo
    {
        return $this->belongsTo(Release::class, 'active_release_id');
    }

    public function runtimeProfile(): BelongsTo
    {
        return $this->belongsTo(RuntimeProfile::class);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(Release::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    public function healthChecks(): HasMany
    {
        return $this->hasMany(HealthCheck::class);
    }

    public function serverRoleProfiles(): HasMany
    {
        return $this->hasMany(ServerRoleProfile::class);
    }

    public function remoteCommands(): HasMany
    {
        return $this->hasMany(RemoteCommand::class);
    }

    public function serviceBindings(): HasMany
    {
        return $this->hasMany(ServiceBinding::class);
    }

    public function pipelineRuns(): HasMany
    {
        return $this->hasMany(PipelineRun::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    protected static function newFactory(): EnvironmentFactory
    {
        return EnvironmentFactory::new();
    }
}
