<?php

namespace Tests\Unit\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Actions\DecommissionNode;
use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Events\ServerHealthChanged;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DecommissionNodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_transitions_active_to_decommissioning(): void
    {
        Event::fake();

        $server = Server::factory()->active()->create();

        $result = (new DecommissionNode)->execute($server);

        $this->assertEquals(ServerStatus::Decommissioning, $result->fresh()->status);
    }

    public function test_it_transitions_draining_to_decommissioning(): void
    {
        Event::fake();

        $server = Server::factory()->create(['status' => ServerStatus::Draining]);

        $result = (new DecommissionNode)->execute($server);

        $this->assertEquals(ServerStatus::Decommissioning, $result->fresh()->status);
    }

    public function test_it_dispatches_server_health_changed_event(): void
    {
        Event::fake();

        $server = Server::factory()->active()->create();

        (new DecommissionNode)->execute($server);

        Event::assertDispatched(ServerHealthChanged::class, function (ServerHealthChanged $event) use ($server) {
            return $event->server->id === $server->id
                && $event->previousStatus === 'active'
                && $event->newStatus === 'decommissioning';
        });
    }
}
