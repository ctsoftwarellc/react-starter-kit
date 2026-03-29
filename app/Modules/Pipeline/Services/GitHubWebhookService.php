<?php

namespace App\Modules\Pipeline\Services;

use App\Modules\AppPlatform\Enums\GitProvider;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\GitConnection;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class GitHubWebhookService
{
    public function registerWebhook(Application $application, GitConnection $connection, string $secret): void
    {
        [$owner, $repository] = $this->parseRepository($application->repository_url);

        Http::withToken($connection->access_token)
            ->acceptJson()
            ->post("https://api.github.com/repos/{$owner}/{$repository}/hooks", [
                'name' => 'web',
                'active' => true,
                'events' => ['push'],
                'config' => [
                    'url' => $this->webhookUrl($application),
                    'content_type' => 'json',
                    'secret' => $secret,
                    'insecure_ssl' => '0',
                ],
            ])
            ->throw();
    }

    private function parseRepository(?string $repositoryUrl): array
    {
        if (! is_string($repositoryUrl) || $repositoryUrl === '') {
            throw new InvalidArgumentException('Application repository URL is required to register a webhook.');
        }

        if (preg_match('#github\.com[:/]([^/]+)/([^/.]+)(?:\.git)?$#', $repositoryUrl, $matches) !== 1) {
            throw new InvalidArgumentException('Repository URL must be a valid GitHub repository URL.');
        }

        return [$matches[1], $matches[2]];
    }

    private function webhookUrl(Application $application): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        return $baseUrl.'/webhooks/'.$application->getKey().'/'.GitProvider::Github->value;
    }
}
