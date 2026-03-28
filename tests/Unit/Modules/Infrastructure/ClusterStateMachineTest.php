<?php

namespace Tests\Unit\Modules\Infrastructure;

use App\Modules\Infrastructure\Enums\ClusterStatus;
use App\Modules\Infrastructure\Models\Cluster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ClusterStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private function createClusterWithStatus(ClusterStatus $status): Cluster
    {
        return Cluster::factory()->create(['status' => $status]);
    }

    // --- Valid transitions ---

    public function test_pending_to_provisioning(): void
    {
        $cluster = $this->createClusterWithStatus(ClusterStatus::Pending);
        $cluster->transitionTo(ClusterStatus::Provisioning);
        $this->assertEquals(ClusterStatus::Provisioning, $cluster->fresh()->status);
    }

    public function test_provisioning_to_active(): void
    {
        $cluster = $this->createClusterWithStatus(ClusterStatus::Provisioning);
        $cluster->transitionTo(ClusterStatus::Active);
        $this->assertEquals(ClusterStatus::Active, $cluster->fresh()->status);
    }

    public function test_active_to_updating(): void
    {
        $cluster = $this->createClusterWithStatus(ClusterStatus::Active);
        $cluster->transitionTo(ClusterStatus::Updating);
        $this->assertEquals(ClusterStatus::Updating, $cluster->fresh()->status);
    }

    public function test_active_to_scaling(): void
    {
        $cluster = $this->createClusterWithStatus(ClusterStatus::Active);
        $cluster->transitionTo(ClusterStatus::Scaling);
        $this->assertEquals(ClusterStatus::Scaling, $cluster->fresh()->status);
    }

    public function test_active_to_degraded(): void
    {
        $cluster = $this->createClusterWithStatus(ClusterStatus::Active);
        $cluster->transitionTo(ClusterStatus::Degraded);
        $this->assertEquals(ClusterStatus::Degraded, $cluster->fresh()->status);
    }

    public function test_active_to_maintenance(): void
    {
        $cluster = $this->createClusterWithStatus(ClusterStatus::Active);
        $cluster->transitionTo(ClusterStatus::Maintenance);
        $this->assertEquals(ClusterStatus::Maintenance, $cluster->fresh()->status);
    }

    public function test_active_to_decommissioning(): void
    {
        $cluster = $this->createClusterWithStatus(ClusterStatus::Active);
        $cluster->transitionTo(ClusterStatus::Decommissioning);
        $this->assertEquals(ClusterStatus::Decommissioning, $cluster->fresh()->status);
    }

    public function test_updating_to_active(): void
    {
        $cluster = $this->createClusterWithStatus(ClusterStatus::Updating);
        $cluster->transitionTo(ClusterStatus::Active);
        $this->assertEquals(ClusterStatus::Active, $cluster->fresh()->status);
    }

    public function test_updating_to_degraded(): void
    {
        $cluster = $this->createClusterWithStatus(ClusterStatus::Updating);
        $cluster->transitionTo(ClusterStatus::Degraded);
        $this->assertEquals(ClusterStatus::Degraded, $cluster->fresh()->status);
    }

    public function test_scaling_to_active(): void
    {
        $cluster = $this->createClusterWithStatus(ClusterStatus::Scaling);
        $cluster->transitionTo(ClusterStatus::Active);
        $this->assertEquals(ClusterStatus::Active, $cluster->fresh()->status);
    }

    public function test_degraded_to_active(): void
    {
        $cluster = $this->createClusterWithStatus(ClusterStatus::Degraded);
        $cluster->transitionTo(ClusterStatus::Active);
        $this->assertEquals(ClusterStatus::Active, $cluster->fresh()->status);
    }

    public function test_maintenance_to_active(): void
    {
        $cluster = $this->createClusterWithStatus(ClusterStatus::Maintenance);
        $cluster->transitionTo(ClusterStatus::Active);
        $this->assertEquals(ClusterStatus::Active, $cluster->fresh()->status);
    }

    public function test_decommissioning_to_decommissioned(): void
    {
        $cluster = $this->createClusterWithStatus(ClusterStatus::Decommissioning);
        $cluster->transitionTo(ClusterStatus::Decommissioned);
        $this->assertEquals(ClusterStatus::Decommissioned, $cluster->fresh()->status);
    }

    // --- Invalid transitions ---

    public function test_pending_to_active_throws(): void
    {
        $cluster = $this->createClusterWithStatus(ClusterStatus::Pending);
        $this->expectException(InvalidArgumentException::class);
        $cluster->transitionTo(ClusterStatus::Active);
    }

    public function test_decommissioned_to_active_throws(): void
    {
        $cluster = $this->createClusterWithStatus(ClusterStatus::Decommissioned);
        $this->expectException(InvalidArgumentException::class);
        $cluster->transitionTo(ClusterStatus::Active);
    }

    public function test_pending_to_decommissioned_throws(): void
    {
        $cluster = $this->createClusterWithStatus(ClusterStatus::Pending);
        $this->expectException(InvalidArgumentException::class);
        $cluster->transitionTo(ClusterStatus::Decommissioned);
    }

    public function test_scaling_to_degraded_throws(): void
    {
        $cluster = $this->createClusterWithStatus(ClusterStatus::Scaling);
        $this->expectException(InvalidArgumentException::class);
        $cluster->transitionTo(ClusterStatus::Degraded);
    }

    public function test_degraded_to_pending_throws(): void
    {
        $cluster = $this->createClusterWithStatus(ClusterStatus::Degraded);
        $this->expectException(InvalidArgumentException::class);
        $cluster->transitionTo(ClusterStatus::Pending);
    }

    // --- canTransitionTo ---

    public function test_can_transition_to_returns_true_for_valid(): void
    {
        $cluster = $this->createClusterWithStatus(ClusterStatus::Active);
        $this->assertTrue($cluster->canTransitionTo(ClusterStatus::Updating));
    }

    public function test_can_transition_to_returns_false_for_invalid(): void
    {
        $cluster = $this->createClusterWithStatus(ClusterStatus::Pending);
        $this->assertFalse($cluster->canTransitionTo(ClusterStatus::Active));
    }
}
