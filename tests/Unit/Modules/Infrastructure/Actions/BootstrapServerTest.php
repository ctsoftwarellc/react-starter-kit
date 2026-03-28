<?php

namespace Tests\Unit\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Actions\BootstrapServer;
use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Events\ServerBootstrapped;
use App\Modules\Infrastructure\Models\Server;
use App\Modules\Infrastructure\Services\ServerBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class BootstrapServerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock ServerBootstrapper since the action uses `new ServerBootstrapper`
        $mock = Mockery::mock('overload:'.ServerBootstrapper::class);
        $mock->shouldReceive('bootstrap')->andReturnNull();
    }

    public function test_it_generates_agent_token_and_hash(): void
    {
        Event::fake();

        $server = Server::factory()->pending()->create();

        $result = (new BootstrapServer)->execute($server);

        $result->refresh();

        $this->assertNotNull($result->agent_token);
        $this->assertNotNull($result->agent_token_hash);
        $this->assertEquals(64, strlen($result->agent_token_hash));
    }

    public function test_it_transitions_to_active(): void
    {
        Event::fake();

        $server = Server::factory()->pending()->create();

        $result = (new BootstrapServer)->execute($server);

        $this->assertEquals(ServerStatus::Active, $result->fresh()->status);
    }

    public function test_it_dispatches_server_bootstrapped_event(): void
    {
        Event::fake();

        $server = Server::factory()->pending()->create();

        $result = (new BootstrapServer)->execute($server);

        Event::assertDispatched(ServerBootstrapped::class, function (ServerBootstrapped $event) use ($result) {
            return $event->server->id === $result->id;
        });
    }
}
