<?php

use App\Http\Middleware\AuthenticateAgent;
use App\Http\Middleware\AuthenticateRunner;
use App\Http\Middleware\AuthenticateWithToken;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\VerifyWebhookSignature;
use App\Modules\Networking\Jobs\RenewExpiringCertificates;
use App\Modules\Operations\Jobs\ApplyRetentionPolicy;
use App\Modules\Pipeline\Jobs\CheckJobTimeout;
use App\Modules\Pipeline\Jobs\CleanupOldArtifacts;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->group(base_path('routes/agent.php'));

            Route::middleware('api')
                ->group(base_path('routes/runner.php'));

            Route::middleware('api')
                ->group(base_path('routes/webhooks.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'auth.token' => AuthenticateWithToken::class,
            'auth.agent' => AuthenticateAgent::class,
            'auth.runner' => AuthenticateRunner::class,
            'webhook.signature' => VerifyWebhookSignature::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->job(new CheckJobTimeout)->everyMinute();
        $schedule->job(new CleanupOldArtifacts)->daily();
        $schedule->job(new RenewExpiringCertificates)->daily();
        $schedule->job(new ApplyRetentionPolicy)->daily();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function ($response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();

            if ($request->expectsJson() || ! in_array($status, [404, 500, 503], true)) {
                return $response;
            }

            return Inertia::render("errors/{$status}", [
                'status' => $status,
            ])->toResponse($request)->setStatusCode($status);
        });
    })->create();
