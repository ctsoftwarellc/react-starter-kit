<?php

namespace App\Modules\Deployment\Services;

use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\DeploymentStep;
use App\Support\Services\ObjectStorage\ObjectStorageService;

class DeploymentCommandPayloadBuilder
{
    public function __construct(
        private readonly ObjectStorageService $storage = new ObjectStorageService,
    ) {}

    public function buildDeployPayload(Deployment $deployment, DeploymentStep $step): array
    {
        return [
            'command' => 'deploy',
            'release_id' => $deployment->release->id,
            'artifact_url' => $this->storage->artifactUrl($deployment->release->artifact->storage_path),
            'artifact_hash' => 'sha256:'.$deployment->release->artifact->content_hash,
            'config' => array_merge($this->baseConfig($deployment, $step), [
                'pre_activate' => $deployment->release->config_snapshot['pre_activate'] ?? [
                    'php artisan migrate --force',
                    'php artisan config:cache',
                    'php artisan route:cache',
                    'php artisan view:cache',
                ],
                'post_activate' => $deployment->release->config_snapshot['post_activate'] ?? ['php artisan queue:restart'],
            ]),
        ];
    }

    public function buildRollbackPayload(Deployment $deployment, DeploymentStep $step): array
    {
        return [
            'command' => 'rollback',
            'release_id' => $deployment->release->id,
            'artifact_url' => $this->storage->artifactUrl($deployment->release->artifact->storage_path),
            'artifact_hash' => 'sha256:'.$deployment->release->artifact->content_hash,
            'config' => $this->baseConfig($deployment, $step),
            'hooks' => [
                'post_activate' => $deployment->release->config_snapshot['post_activate'] ?? ['php artisan queue:restart'],
            ],
        ];
    }

    private function baseConfig(Deployment $deployment, DeploymentStep $step): array
    {
        $deployment->loadMissing('release.artifact');
        $step->loadMissing('server');

        $snapshot = is_array($deployment->release->config_snapshot) ? $deployment->release->config_snapshot : [];

        return [
            'env_vars' => $snapshot['env_vars'] ?? [],
            'secrets' => $snapshot['secrets'] ?? [],
            'processes' => $snapshot['processes'] ?? [],
            'runtime' => $snapshot['runtime'] ?? null,
            'runtime_stack' => $snapshot['runtime_stack'] ?? null,
            'php_version' => $snapshot['php_version'] ?? '8.3',
            'shared_dirs' => $snapshot['shared_dirs'] ?? ['storage'],
            'writable_dirs' => $snapshot['writable_dirs'] ?? ['storage/framework/views', 'storage/logs', 'bootstrap/cache'],
        ];
    }
}
