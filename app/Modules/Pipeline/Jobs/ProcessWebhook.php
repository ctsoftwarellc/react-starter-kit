<?php

namespace App\Modules\Pipeline\Jobs;

use App\Modules\AppPlatform\Models\Application;
use App\Modules\Pipeline\Actions\TriggerPipelineRun;
use App\Modules\Pipeline\DTOs\TriggerPipelineRunData;
use App\Modules\Pipeline\Services\TriggerEvaluator;
use App\Modules\Pipeline\Services\WebhookProcessor;
use App\Support\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public Application $application,
        public string $provider,
        public array $payload,
        public array $headers = [],
    ) {}

    public function handle(): void
    {
        $event = (new WebhookProcessor)->parse($this->application, $this->provider, $this->payload, $this->headers);
        $evaluator = new TriggerEvaluator;

        $this->application->loadMissing('pipelines');

        foreach ($this->application->pipelines as $pipeline) {
            if (! $evaluator->matches($pipeline, $event)) {
                continue;
            }

            (new TriggerPipelineRun)->execute($pipeline, new TriggerPipelineRunData(
                triggerType: $event->triggerType,
                triggerRef: $event->ref,
                triggerSha: $event->sha,
                triggerActor: $event->actor,
            ));
        }
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
    }

    public function queue(): string
    {
        return QueueName::Pipeline->value;
    }
}
