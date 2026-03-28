<?php

namespace Tests\Unit\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Actions\ActivateNode;
use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Events\ServerHealthChanged;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ActivateNodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_transitions_draining_to_active(): void
    {
        Event::fake();

        $server = Server::factory()->create(['status' => ServerStatus::Draining]);

        $result = (new ActivateNode)->execute($server);

        $this->assertEquals(ServerStatus::Active, $result->fresh()->status);
    }

    public function test_it_transitions_cordoned_to_active(): void
    {
        Event::fake();

        $server = Server::factory()->create(['status' => ServerStatus::Cordoned]);

        $result = (new ActivateNode)->execute($server);

        $this->assertEquals(ServerStatus::Active, $result->fresh()->status);
    }

    public function test_it_transitions_maintenance_to_active(): void
    {
        Event::fake();

        $server = Server::factory()->create(['status' => ServerStatus::Maintenance]);

        $result = (new ActivateNode)->execute($server);

        $this->assertEquals(ServerStatus::Active, $result->fresh()->status);
    }

    public function test_it_dispatches_server_health_changed_event(): void
    {
        Event::fake();

        $server = Server::factory()->create(['status' => ServerStatus::Draining]);

        (new ActivateNode)->execute($server);

        Event::assertDispatched(ServerHealthChanged::class, function (ServerHealthChanged $event) use ($server) {
            return $event->server->id === $server->id
                && $event->previousStatus === 'draining'
                && $event->newStatus === 'active';
        });
    }
}
