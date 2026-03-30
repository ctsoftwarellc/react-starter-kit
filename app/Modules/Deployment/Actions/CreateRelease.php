<?php

namespace App\Modules\Deployment\Actions;

use App\Models\User;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Enums\ReleaseStatus;
use App\Modules\Deployment\Models\Release;
use App\Modules\Pipeline\Models\Artifact;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateRelease
{
    public function execute(Environment $environment, Artifact $artifact, ?User $deployedBy = null): Release
    {
        $environment->loadMissing('application', 'variables', 'secrets', 'processDefinitions', 'runtimeProfile');

        if ($artifact->application_id !== $environment->application_id) {
            throw new RuntimeException('Artifact does not belong to the environment application.');
        }

        return DB::transaction(function () use ($environment, $artifact, $deployedBy) {
            $nextVersion = ((int) Release::query()
                ->where('environment_id', $environment->id)
                ->max('version')) + 1;

            $runtimeConfig = is_array($environment->runtimeProfile?->config)
                ? $environment->runtimeProfile->config
                : [];

            return Release::create([
                'environment_id' => $environment->id,
                'artifact_id' => $artifact->id,
                'version' => $nextVersion,
                'status' => ReleaseStatus::Pending,
                'config_snapshot' => [
                    'env_vars' => $environment->variables->pluck('value', 'key')->all(),
                    'secrets' => $environment->secrets->pluck('key')->values()->all(),
                    'processes' => $environment->processDefinitions
                        ->map(fn ($process) => [
                            'type' => $process->type->value,
                            'command' => $process->command,
                            'instances' => $process->instances,
                        ])
                        ->values()
                        ->all(),
                    'runtime' => $environment->application->runtime->value,
                    'php_version' => $environment->application->settings['php_version'] ?? '8.3',
                    'runtime_stack' => $environment->runtimeProfile?->stack?->value,
                    'runtime_profile_id' => $environment->runtime_profile_id,
                    'shared_dirs' => $runtimeConfig['shared_dirs'] ?? ['storage'],
                    'writable_dirs' => $runtimeConfig['writable_dirs'] ?? ['storage/framework/views', 'storage/logs', 'bootstrap/cache'],
                    'pre_activate' => $runtimeConfig['pre_activate'] ?? [
                        'php artisan migrate --force',
                        'php artisan config:cache',
                        'php artisan route:cache',
                        'php artisan view:cache',
                    ],
                    'post_activate' => $runtimeConfig['post_activate'] ?? ['php artisan queue:restart'],
                ],
                'deployed_by' => $deployedBy?->id,
            ]);
        });
    }
}
