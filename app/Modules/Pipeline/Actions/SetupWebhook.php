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
            $webhook = Webhook::firstOrNew([
                'application_id' => $application->id,
                'provider' => $provider->value,
            ]);

            if (! $webhook->exists) {
                $webhook->secret = Str::random(40);
            }

            $webhook->is_active = true;
            $webhook->save();

            return $webhook;
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
