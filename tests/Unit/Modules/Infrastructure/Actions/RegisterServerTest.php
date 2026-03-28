<?php

namespace Tests\Unit\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Actions\RegisterServer;
use App\Modules\Infrastructure\DTOs\RegisterServerData;
use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Events\ServerRegistered;
use App\Modules\Infrastructure\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RegisterServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_server_in_pending_status(): void
    {
        Event::fake();

        $data = new RegisterServerData(
            name: 'web-1',
            hostname: 'web-1.example.com',
            publicIp: '1.2.3.4',
        );

        $server = (new RegisterServer)->execute($data);

        $this->assertDatabaseHas('servers', [
            'id' => $server->id,
            'name' => 'web-1',
            'hostname' => 'web-1.example.com',
            'public_ip' => '1.2.3.4',
        ]);
        $this->assertEquals(ServerStatus::Pending, $server->status);
    }

    public function test_it_creates_server_with_optional_fields(): void
    {
        Event::fake();

        $provider = Provider::factory()->create();

        $data = new RegisterServerData(
            name: 'web-2',
            hostname: 'web-2.example.com',
            publicIp: '5.6.7.8',
            privateIp: '10.0.0.1',
            sshPort: 2222,
            sshUser: 'deploy',
            providerId: $provider->id,
            os: 'Ubuntu 24.04',
            region: 'nyc1',
        );

        $server = (new RegisterServer)->execute($data);

        $this->assertEquals('10.0.0.1', $server->private_ip);
        $this->assertEquals(2222, $server->ssh_port);
        $this->assertEquals('deploy', $server->ssh_user);
        $this->assertEquals($provider->id, $server->provider_id);
        $this->assertEquals('Ubuntu 24.04', $server->os);
        $this->assertEquals('nyc1', $server->region);
    }

    public function test_it_dispatches_server_registered_event(): void
    {
        Event::fake();

        $data = new RegisterServerData(
            name: 'web-1',
            hostname: 'web-1.example.com',
            publicIp: '1.2.3.4',
        );

        $server = (new RegisterServer)->execute($data);

        Event::assertDispatched(ServerRegistered::class, function (ServerRegistered $event) use ($server) {
            return $event->server->id === $server->id;
        });
    }
}
