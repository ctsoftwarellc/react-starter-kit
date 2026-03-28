<?php

namespace Tests\Unit\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Actions\CordonNode;
use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Events\ServerHealthChanged;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CordonNodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_transitions_active_to_cordoned(): void
    {
        Event::fake();

        $server = Server::factory()->active()->create();

        $result = (new CordonNode)->execute($server);

        $this->assertEquals(ServerStatus::Cordoned, $result->fresh()->status);
    }

    public function test_it_dispatches_server_health_changed_event(): void
    {
        Event::fake();

        $server = Server::factory()->active()->create();

        (new CordonNode)->execute($server);

        Event::assertDispatched(ServerHealthChanged::class, function (ServerHealthChanged $event) use ($server) {
            return $event->server->id === $server->id
                && $event->previousStatus === 'active'
                && $event->newStatus === 'cordoned';
        });
    }
}
