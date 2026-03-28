<?php

namespace Tests\Unit\Modules\Infrastructure;

use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ServerStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private function createServerWithStatus(ServerStatus $status): Server
    {
        return Server::factory()->create(['status' => $status]);
    }

    // --- Valid transitions ---

    public function test_pending_to_provisioning(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Pending);
        $server->transitionTo(ServerStatus::Provisioning);
        $this->assertEquals(ServerStatus::Provisioning, $server->fresh()->status);
    }

    public function test_pending_to_bootstrapping(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Pending);
        $server->transitionTo(ServerStatus::Bootstrapping);
        $this->assertEquals(ServerStatus::Bootstrapping, $server->fresh()->status);
    }

    public function test_provisioning_to_bootstrapping(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Provisioning);
        $server->transitionTo(ServerStatus::Bootstrapping);
        $this->assertEquals(ServerStatus::Bootstrapping, $server->fresh()->status);
    }

    public function test_provisioning_to_failed(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Provisioning);
        $server->transitionTo(ServerStatus::Failed);
        $this->assertEquals(ServerStatus::Failed, $server->fresh()->status);
    }

    public function test_bootstrapping_to_active(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Bootstrapping);
        $server->transitionTo(ServerStatus::Active);
        $this->assertEquals(ServerStatus::Active, $server->fresh()->status);
    }

    public function test_bootstrapping_to_failed(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Bootstrapping);
        $server->transitionTo(ServerStatus::Failed);
        $this->assertEquals(ServerStatus::Failed, $server->fresh()->status);
    }

    public function test_active_to_draining(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Active);
        $server->transitionTo(ServerStatus::Draining);
        $this->assertEquals(ServerStatus::Draining, $server->fresh()->status);
    }

    public function test_active_to_cordoned(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Active);
        $server->transitionTo(ServerStatus::Cordoned);
        $this->assertEquals(ServerStatus::Cordoned, $server->fresh()->status);
    }

    public function test_active_to_maintenance(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Active);
        $server->transitionTo(ServerStatus::Maintenance);
        $this->assertEquals(ServerStatus::Maintenance, $server->fresh()->status);
    }

    public function test_active_to_decommissioning(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Active);
        $server->transitionTo(ServerStatus::Decommissioning);
        $this->assertEquals(ServerStatus::Decommissioning, $server->fresh()->status);
    }

    public function test_draining_to_active(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Draining);
        $server->transitionTo(ServerStatus::Active);
        $this->assertEquals(ServerStatus::Active, $server->fresh()->status);
    }

    public function test_draining_to_decommissioning(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Draining);
        $server->transitionTo(ServerStatus::Decommissioning);
        $this->assertEquals(ServerStatus::Decommissioning, $server->fresh()->status);
    }

    public function test_cordoned_to_active(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Cordoned);
        $server->transitionTo(ServerStatus::Active);
        $this->assertEquals(ServerStatus::Active, $server->fresh()->status);
    }

    public function test_cordoned_to_decommissioning(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Cordoned);
        $server->transitionTo(ServerStatus::Decommissioning);
        $this->assertEquals(ServerStatus::Decommissioning, $server->fresh()->status);
    }

    public function test_maintenance_to_active(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Maintenance);
        $server->transitionTo(ServerStatus::Active);
        $this->assertEquals(ServerStatus::Active, $server->fresh()->status);
    }

    public function test_maintenance_to_decommissioning(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Maintenance);
        $server->transitionTo(ServerStatus::Decommissioning);
        $this->assertEquals(ServerStatus::Decommissioning, $server->fresh()->status);
    }

    public function test_decommissioning_to_decommissioned(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Decommissioning);
        $server->transitionTo(ServerStatus::Decommissioned);
        $this->assertEquals(ServerStatus::Decommissioned, $server->fresh()->status);
    }

    public function test_failed_to_bootstrapping(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Failed);
        $server->transitionTo(ServerStatus::Bootstrapping);
        $this->assertEquals(ServerStatus::Bootstrapping, $server->fresh()->status);
    }

    public function test_failed_to_decommissioning(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Failed);
        $server->transitionTo(ServerStatus::Decommissioning);
        $this->assertEquals(ServerStatus::Decommissioning, $server->fresh()->status);
    }

    // --- Invalid transitions ---

    public function test_pending_to_active_throws(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Pending);
        $this->expectException(InvalidArgumentException::class);
        $server->transitionTo(ServerStatus::Active);
    }

    public function test_active_to_pending_throws(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Active);
        $this->expectException(InvalidArgumentException::class);
        $server->transitionTo(ServerStatus::Pending);
    }

    public function test_decommissioned_to_active_throws(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Decommissioned);
        $this->expectException(InvalidArgumentException::class);
        $server->transitionTo(ServerStatus::Active);
    }

    public function test_pending_to_decommissioned_throws(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Pending);
        $this->expectException(InvalidArgumentException::class);
        $server->transitionTo(ServerStatus::Decommissioned);
    }

    public function test_failed_to_active_throws(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Failed);
        $this->expectException(InvalidArgumentException::class);
        $server->transitionTo(ServerStatus::Active);
    }

    // --- canTransitionTo ---

    public function test_can_transition_to_returns_true_for_valid(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Pending);
        $this->assertTrue($server->canTransitionTo(ServerStatus::Provisioning));
    }

    public function test_can_transition_to_returns_false_for_invalid(): void
    {
        $server = $this->createServerWithStatus(ServerStatus::Pending);
        $this->assertFalse($server->canTransitionTo(ServerStatus::Active));
    }
}
