<?php

namespace Tests\Unit\Modules\Pipeline\Services;

use App\Modules\Pipeline\DTOs\WebhookEventData;
use App\Modules\Pipeline\Enums\TriggerType;
use App\Modules\Pipeline\Models\Pipeline;
use App\Modules\Pipeline\Services\TriggerEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TriggerEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_matches_push_events_by_branch(): void
    {
        $pipeline = Pipeline::factory()->create([
            'trigger_branches' => ['main'],
            'trigger_events' => ['push'],
        ]);

        $matches = (new TriggerEvaluator)->matches($pipeline, new WebhookEventData(
            provider: 'github',
            triggerType: TriggerType::Push,
            ref: 'main',
            sha: 'abc',
            actor: 'caleb',
            eventName: 'push',
        ));

        $this->assertTrue($matches);
    }

    public function test_it_matches_tag_events(): void
    {
        $pipeline = Pipeline::factory()->create([
            'trigger_events' => ['tag'],
            'trigger_branches' => ['main'],
        ]);

        $matches = (new TriggerEvaluator)->matches($pipeline, new WebhookEventData(
            provider: 'github',
            triggerType: TriggerType::Tag,
            ref: 'v1.0.0',
            sha: 'abc',
            actor: 'caleb',
            eventName: 'push',
        ));

        $this->assertTrue($matches);
    }

    public function test_it_ignores_inactive_pipelines(): void
    {
        $pipeline = Pipeline::factory()->create(['is_active' => false]);

        $matches = (new TriggerEvaluator)->matches($pipeline, new WebhookEventData(
            provider: 'github',
            triggerType: TriggerType::Push,
            ref: 'main',
            sha: 'abc',
            actor: 'caleb',
            eventName: 'push',
        ));

        $this->assertFalse($matches);
    }
}
