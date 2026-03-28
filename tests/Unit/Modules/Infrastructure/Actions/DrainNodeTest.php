<?php

namespace Tests\Unit\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Actions\DrainNode;
use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Events\ServerHealthChanged;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DrainNodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_transitions_active_to_draining(): void
    {
        Event::fake();

        $server = Server::factory()->active()->create();

        $result = (new DrainNode)->execute($server);

        $this->assertEquals(ServerStatus::Draining, $result->fresh()->status);
    }

    public function test_it_dispatches_server_health_changed_event(): void
    {
        Event::fake();

        $server = Server::factory()->active()->create();

        (new DrainNode)->execute($server);

        Event::assertDispatched(ServerHealthChanged::class, function (ServerHealthChanged $event) use ($server) {
            return $event->server->id === $server->id
                && $event->previousStatus === 'active'
                && $event->newStatus === 'draining';
        });
    }
}
