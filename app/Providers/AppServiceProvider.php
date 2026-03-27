<?php

namespace App\Providers;

use App\Modules\AppPlatform\Events\ProjectCreated;
use App\Modules\AppPlatform\Events\ProjectDeleted;
use App\Modules\AppPlatform\Events\ProjectUpdated;
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
        Event::listen(ProjectCreated::class, [RecordAuditLog::class, 'handle']);
        Event::listen(ProjectUpdated::class, [RecordAuditLog::class, 'handle']);
        Event::listen(ProjectDeleted::class, [RecordAuditLog::class, 'handle']);
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
