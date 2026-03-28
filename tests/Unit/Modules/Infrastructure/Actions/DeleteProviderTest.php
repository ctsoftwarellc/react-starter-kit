<?php

namespace Tests\Unit\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Actions\DeleteProvider;
use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Events\ProviderDeleted;
use App\Modules\Infrastructure\Models\Provider;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Tests\TestCase;

class DeleteProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_provider_with_no_active_servers(): void
    {
        Event::fake();

        $provider = Provider::factory()->create();

        (new DeleteProvider)->execute($provider);

        $this->assertDatabaseMissing('providers', ['id' => $provider->id]);
    }

    public function test_it_dispatches_provider_deleted_event(): void
    {
        Event::fake();

        $provider = Provider::factory()->create();

        (new DeleteProvider)->execute($provider);

        Event::assertDispatched(ProviderDeleted::class, function (ProviderDeleted $event) use ($provider) {
            return $event->provider->id === $provider->id;
        });
    }

    public function test_it_throws_when_provider_has_active_servers(): void
    {
        Event::fake();

        $provider = Provider::factory()->create();
        Server::factory()->create([
            'provider_id' => $provider->id,
            'status' => ServerStatus::Active,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete provider with active servers.');

        (new DeleteProvider)->execute($provider);
    }

    public function test_it_throws_when_provider_has_bootstrapping_servers(): void
    {
        Event::fake();

        $provider = Provider::factory()->create();
        Server::factory()->create([
            'provider_id' => $provider->id,
            'status' => ServerStatus::Bootstrapping,
        ]);

        $this->expectException(InvalidArgumentException::class);

        (new DeleteProvider)->execute($provider);
    }

    public function test_it_deletes_provider_with_decommissioned_servers(): void
    {
        Event::fake();

        $provider = Provider::factory()->create();
        Server::factory()->create([
            'provider_id' => $provider->id,
            'status' => ServerStatus::Decommissioned,
        ]);

        (new DeleteProvider)->execute($provider);

        $this->assertDatabaseMissing('providers', ['id' => $provider->id]);
    }
}
