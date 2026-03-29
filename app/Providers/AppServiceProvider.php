<?php

namespace App\Providers;

use App\Modules\AppPlatform\Events\ApplicationCreated;
use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\AppPlatform\Events\GitConnectionEstablished;
use App\Modules\AppPlatform\Events\ProjectCreated;
use App\Modules\AppPlatform\Events\ProjectDeleted;
use App\Modules\AppPlatform\Events\ProjectUpdated;
use App\Modules\AppPlatform\Events\SecretUpdated;
use App\Modules\AppPlatform\Listeners\CreateDefaultEnvironment;
use App\Modules\AppPlatform\Listeners\SetupApplicationWebhook;
use App\Modules\Infrastructure\Events\ClusterTopologyChanged;
use App\Modules\Infrastructure\Events\ProviderCreated;
use App\Modules\Infrastructure\Events\ProviderDeleted;
use App\Modules\Infrastructure\Events\ProviderUpdated;
use App\Modules\Infrastructure\Events\ServerBootstrapped;
use App\Modules\Infrastructure\Events\ServerHealthChanged;
use App\Modules\Infrastructure\Events\ServerRegistered;
use App\Modules\Infrastructure\Listeners\PushSshKeysOnBootstrap;
use App\Modules\Infrastructure\Listeners\UpdateClusterStatus;
use App\Modules\Operations\Listeners\RecordAuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerEventListeners();
    }

    protected function registerEventListeners(): void
    {
        // AppPlatform events
        Event::listen(ProjectCreated::class, [RecordAuditLog::class, 'handle']);
        Event::listen(ProjectUpdated::class, [RecordAuditLog::class, 'handle']);
        Event::listen(ProjectDeleted::class, [RecordAuditLog::class, 'handle']);
        Event::listen(GitConnectionEstablished::class, [RecordAuditLog::class, 'handle']);
        Event::listen(ApplicationCreated::class, [RecordAuditLog::class, 'handle']);
        Event::listen(EnvironmentConfigChanged::class, [RecordAuditLog::class, 'handle']);
        Event::listen(SecretUpdated::class, [RecordAuditLog::class, 'handle']);

        // Infrastructure events → Audit log
        Event::listen(ProviderCreated::class, [RecordAuditLog::class, 'handle']);
        Event::listen(ProviderUpdated::class, [RecordAuditLog::class, 'handle']);
        Event::listen(ProviderDeleted::class, [RecordAuditLog::class, 'handle']);
        Event::listen(ServerRegistered::class, [RecordAuditLog::class, 'handle']);
        Event::listen(ServerBootstrapped::class, [RecordAuditLog::class, 'handle']);
        Event::listen(ServerHealthChanged::class, [RecordAuditLog::class, 'handle']);
        Event::listen(ClusterTopologyChanged::class, [RecordAuditLog::class, 'handle']);

        // Infrastructure events → Domain listeners
        Event::listen(ServerBootstrapped::class, [UpdateClusterStatus::class, 'handle']);
        Event::listen(ServerHealthChanged::class, [UpdateClusterStatus::class, 'handle']);
        Event::listen(ServerBootstrapped::class, [PushSshKeysOnBootstrap::class, 'handle']);

        // AppPlatform events → Domain listeners
        Event::listen(ApplicationCreated::class, [CreateDefaultEnvironment::class, 'handle']);
        Event::listen(ApplicationCreated::class, [SetupApplicationWebhook::class, 'handle']);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
