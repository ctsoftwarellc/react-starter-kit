<?php

namespace Tests\Unit\Modules\Deployment\Models;

use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Models\Deployment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class DeploymentStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_allows_each_supported_transition(): void
    {
        $transitions = [
            [DeploymentStatus::Pending, DeploymentStatus::Preparing],
            [DeploymentStatus::Pending, DeploymentStatus::Cancelled],
            [DeploymentStatus::Preparing, DeploymentStatus::Deploying],
            [DeploymentStatus::Preparing, DeploymentStatus::Failed],
            [DeploymentStatus::Deploying, DeploymentStatus::Verifying],
            [DeploymentStatus::Deploying, DeploymentStatus::Failed],
            [DeploymentStatus::Verifying, DeploymentStatus::Succeeded],
            [DeploymentStatus::Verifying, DeploymentStatus::Failed],
            [DeploymentStatus::Failed, DeploymentStatus::RolledBack],
        ];

        foreach ($transitions as [$from, $to]) {
            $deployment = Deployment::factory()->create(['status' => $from]);

            $deployment->transitionTo($to);

            $this->assertSame($to, $deployment->fresh()->status);
        }
    }

    public function test_it_rejects_invalid_transitions(): void
    {
        $deployment = Deployment::factory()->create(['status' => DeploymentStatus::Pending]);

        $this->expectException(InvalidArgumentException::class);

        $deployment->transitionTo(DeploymentStatus::Succeeded);
    }
}
