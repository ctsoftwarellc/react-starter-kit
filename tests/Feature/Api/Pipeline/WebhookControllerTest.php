<?php

namespace Tests\Feature\Api\Pipeline;

use App\Modules\AppPlatform\Enums\GitProvider;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\Pipeline\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_signature_returns_202(): void
    {
        $application = Application::factory()->create();
        $webhook = Webhook::factory()->create([
            'application_id' => $application->id,
            'provider' => GitProvider::Github,
            'secret' => 'webhook-secret',
        ]);
        $payload = json_encode(['ref' => 'refs/heads/main']);
        $signature = 'sha256='.hash_hmac('sha256', $payload, $webhook->secret);

        $this->withHeader('X-Hub-Signature-256', $signature)
            ->postJson('/webhooks/'.$application->id.'/github', json_decode($payload, true))
            ->assertAccepted()
            ->assertJsonPath('application_id', $application->id)
            ->assertJsonPath('provider', 'github');
    }

    public function test_invalid_signature_returns_401(): void
    {
        $application = Application::factory()->create();
        Webhook::factory()->create([
            'application_id' => $application->id,
            'provider' => GitProvider::Github,
            'secret' => 'webhook-secret',
        ]);

        $this->withHeader('X-Hub-Signature-256', 'sha256=bad-signature')
            ->postJson('/webhooks/'.$application->id.'/github', ['ref' => 'refs/heads/main'])
            ->assertUnauthorized();
    }

    public function test_unknown_application_or_provider_returns_404(): void
    {
        $application = Application::factory()->create();

        $this->postJson('/webhooks/does-not-exist/github', ['ref' => 'refs/heads/main'])->assertNotFound();
        $this->postJson('/webhooks/'.$application->id.'/gitlab', ['ref' => 'refs/heads/main'])->assertNotFound();
    }
}
