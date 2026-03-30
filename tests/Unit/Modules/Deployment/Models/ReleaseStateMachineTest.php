<?php

namespace Tests\Unit\Modules\Deployment\Models;

use App\Modules\Deployment\Enums\ReleaseStatus;
use App\Modules\Deployment\Models\Release;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ReleaseStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_allows_each_supported_transition(): void
    {
        $transitions = [
            [ReleaseStatus::Pending, ReleaseStatus::Deploying],
            [ReleaseStatus::Deploying, ReleaseStatus::Active],
            [ReleaseStatus::Deploying, ReleaseStatus::Failed],
            [ReleaseStatus::Active, ReleaseStatus::Superseded],
            [ReleaseStatus::Failed, ReleaseStatus::RolledBack],
        ];

        foreach ($transitions as [$from, $to]) {
            $release = Release::factory()->create(['status' => $from]);

            $release->transitionTo($to);

            $this->assertSame($to, $release->fresh()->status);
        }
    }

    public function test_it_rejects_invalid_transitions(): void
    {
        $release = Release::factory()->create(['status' => ReleaseStatus::Pending]);

        $this->expectException(InvalidArgumentException::class);

        $release->transitionTo(ReleaseStatus::Active);
    }

    public function test_it_rejects_reactivating_a_superseded_release(): void
    {
        $release = Release::factory()->create(['status' => ReleaseStatus::Superseded]);

        $this->expectException(InvalidArgumentException::class);

        $release->transitionTo(ReleaseStatus::Active);
    }
}
