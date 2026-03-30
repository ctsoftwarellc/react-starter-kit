<?php

namespace App\Modules\Pipeline\Services;

use App\Modules\AppPlatform\Enums\GitProvider;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\Pipeline\DTOs\WebhookEventData;
use App\Modules\Pipeline\Enums\TriggerType;
use InvalidArgumentException;

class WebhookProcessor
{
    public function parse(Application $application, string $provider, array $payload, array $headers = []): WebhookEventData
    {
        if ($provider !== GitProvider::Github->value) {
            throw new InvalidArgumentException("Unsupported webhook provider [{$provider}].");
        }

        $eventName = $this->resolveHeaderValue($headers, 'X-GitHub-Event');

        if (! is_string($eventName) || $eventName === '') {
            throw new InvalidArgumentException('GitHub webhook event header is required.');
        }

        $ref = $payload['ref'] ?? null;
        $sha = $payload['after'] ?? $payload['pull_request']['head']['sha'] ?? null;
        $actor = $payload['sender']['login'] ?? $payload['pusher']['name'] ?? null;

        return new WebhookEventData(
            provider: $provider,
            triggerType: $this->resolveTriggerType($eventName, $ref),
            ref: $this->normalizeRef($ref, $eventName),
            sha: is_string($sha) ? $sha : null,
            actor: is_string($actor) ? $actor : null,
            eventName: $eventName,
            payload: $payload,
        );
    }

    private function resolveTriggerType(string $eventName, mixed $ref): TriggerType
    {
        return match ($eventName) {
            'push' => is_string($ref) && str_starts_with($ref, 'refs/tags/')
                ? TriggerType::Tag
                : TriggerType::Push,
            'pull_request' => TriggerType::PullRequest,
            'schedule' => TriggerType::Schedule,
            default => TriggerType::Api,
        };
    }

    private function normalizeRef(mixed $ref, string $eventName): ?string
    {
        if ($eventName === 'pull_request') {
            return null;
        }

        if (! is_string($ref) || $ref === '') {
            return null;
        }

        return preg_replace('#^refs/(heads|tags)/#', '', $ref) ?: $ref;
    }

    private function resolveHeaderValue(array $headers, string $header): ?string
    {
        $value = $headers[$header]
            ?? $headers[strtolower($header)]
            ?? $headers[strtoupper(str_replace('-', '_', $header))]
            ?? null;

        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }
}
