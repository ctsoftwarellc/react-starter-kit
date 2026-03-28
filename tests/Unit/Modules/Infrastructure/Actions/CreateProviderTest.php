<?php

namespace Tests\Unit\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Actions\CreateProvider;
use App\Modules\Infrastructure\DTOs\CreateProviderData;
use App\Modules\Infrastructure\Enums\ProviderType;
use App\Modules\Infrastructure\Events\ProviderCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_provider_with_correct_attributes(): void
    {
        Event::fake();

        $data = new CreateProviderData(
            name: 'My DigitalOcean',
            type: ProviderType::DigitalOcean,
            credentials: ['api_key' => 'test-key'],
        );

        $provider = (new CreateProvider)->execute($data);

        $this->assertDatabaseHas('providers', [
            'id' => $provider->id,
            'name' => 'My DigitalOcean',
            'type' => 'digitalocean',
        ]);
        $this->assertEquals(ProviderType::DigitalOcean, $provider->type);
    }

    public function test_it_dispatches_provider_created_event(): void
    {
        Event::fake();

        $data = new CreateProviderData(
            name: 'Test Provider',
            type: ProviderType::Manual,
            credentials: [],
        );

        $provider = (new CreateProvider)->execute($data);

        Event::assertDispatched(ProviderCreated::class, function (ProviderCreated $event) use ($provider) {
            return $event->provider->id === $provider->id;
        });
    }
}
