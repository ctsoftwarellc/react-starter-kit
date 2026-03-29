<?php

namespace Tests\Unit\Modules\Pipeline\Actions;

use App\Modules\AppPlatform\Enums\GitProvider;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\GitConnection;
use App\Modules\Pipeline\Actions\SetupWebhook;
use App\Modules\Pipeline\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SetupWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_webhook_record_with_secret(): void
    {
        Http::fake();

        $application = Application::factory()->create(['git_connection_id' => null, 'repository_url' => null]);

        $webhook = (new SetupWebhook)->execute($application);

        $this->assertDatabaseHas('webhooks', [
            'id' => $webhook->id,
            'application_id' => $application->id,
            'provider' => GitProvider::Github->value,
            'is_active' => true,
        ]);
        $this->assertNotEmpty($webhook->secret);
    }

    public function test_it_registers_webhook_with_github_when_connection_exists(): void
    {
        Http::fake(['https://api.github.com/*' => Http::response([], 201)]);

        $connection = GitConnection::factory()->create([
            'provider' => GitProvider::Github,
            'access_token' => 'test-token',
        ]);
        $application = Application::factory()->create([
            'git_connection_id' => $connection->id,
            'repository_url' => 'https://github.com/caleb/helm',
        ]);

        $webhook = (new SetupWebhook)->execute($application);

        Http::assertSent(function ($request) use ($webhook) {
            return $request->url() === 'https://api.github.com/repos/caleb/helm/hooks'
                && $request['config']['secret'] === $webhook->secret;
        });
    }

    public function test_it_reactivates_existing_webhook_without_creating_duplicates(): void
    {
        Http::fake();

        $application = Application::factory()->create(['git_connection_id' => null, 'repository_url' => null]);
        $existing = Webhook::factory()->create([
            'application_id' => $application->id,
            'provider' => GitProvider::Github,
            'is_active' => false,
        ]);

        $webhook = (new SetupWebhook)->execute($application);

        $this->assertSame($existing->id, $webhook->id);
        $this->assertTrue($webhook->fresh()->is_active);
        $this->assertSame(1, Webhook::where('application_id', $application->id)->where('provider', GitProvider::Github)->count());
    }
}
