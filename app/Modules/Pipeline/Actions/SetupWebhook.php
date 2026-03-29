<?php

namespace App\Modules\Pipeline\Actions;

use App\Modules\AppPlatform\Enums\GitProvider;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\Pipeline\Models\Webhook;
use App\Modules\Pipeline\Services\GitHubWebhookService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SetupWebhook
{
    public function __construct(
        private readonly GitHubWebhookService $gitHubWebhookService = new GitHubWebhookService,
    ) {}

    public function execute(Application $application, GitProvider $provider = GitProvider::Github): Webhook
    {
        $webhook = DB::transaction(function () use ($application, $provider) {
            return Webhook::firstOrCreate(
                [
                    'application_id' => $application->id,
                    'provider' => $provider->value,
                ],
                [
                    'secret' => Str::random(40),
                    'is_active' => true,
                ],
            );
        });

        if (
            $provider === GitProvider::Github
            && $application->gitConnection !== null
            && $application->gitConnection->provider === GitProvider::Github
            && $application->repository_url !== null
        ) {
            $this->gitHubWebhookService->registerWebhook(
                $application,
                $application->gitConnection,
                $webhook->secret,
            );
        }

        return $webhook;
    }
}
