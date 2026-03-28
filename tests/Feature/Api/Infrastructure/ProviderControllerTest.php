<?php

namespace Tests\Feature\Api\Infrastructure;

use App\Actions\CreatePersonalAccessToken;
use App\Models\User;
use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Models\Provider;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ProviderControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithToken(): array
    {
        $user = User::factory()->create();
        $result = (new CreatePersonalAccessToken)->execute($user, 'Test Token');

        return [$user, $result->plainTextToken];
    }

    public function test_can_list_providers(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        Provider::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/providers');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'type', 'is_active', 'created_at', 'updated_at'],
            ],
        ]);
    }

    public function test_can_create_provider(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/providers', [
                'name' => 'My DigitalOcean',
                'type' => 'digitalocean',
                'credentials' => ['api_key' => 'test-key'],
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'My DigitalOcean');
        $response->assertJsonPath('data.type', 'digitalocean');
    }

    public function test_can_show_provider(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $provider = Provider::factory()->create(['name' => 'Test Provider']);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/providers/'.$provider->id);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Test Provider');
    }

    public function test_can_update_provider(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $provider = Provider::factory()->create(['name' => 'Old Name']);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/providers/'.$provider->id, [
                'name' => 'Updated Name',
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_can_delete_provider(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $provider = Provider::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/providers/'.$provider->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('providers', ['id' => $provider->id]);
    }

    public function test_can_test_provider_connection(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $provider = Provider::factory()->create(['type' => 'manual']);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/providers/'.$provider->id.'/test');

        $response->assertOk();
        $response->assertJsonPath('success', true);
    }

    public function test_credentials_never_exposed(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $provider = Provider::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/providers/'.$provider->id);

        $response->assertOk();
        $response->assertJsonMissingPath('data.credentials');
    }

    public function test_create_validation_fails_missing_required_fields(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/providers', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name', 'type', 'credentials']);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/v1/providers');

        $response->assertUnauthorized();
    }

    public function test_cannot_delete_provider_with_active_servers(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $provider = Provider::factory()->create();
        Server::factory()->create([
            'provider_id' => $provider->id,
            'status' => ServerStatus::Active,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/providers/'.$provider->id);

        $response->assertStatus(500);
    }
}
