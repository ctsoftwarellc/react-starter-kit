<?php

namespace Tests\Unit\Modules\Pipeline;

use App\Modules\Pipeline\Enums\ArtifactStatus;
use App\Modules\Pipeline\Models\Artifact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ArtifactStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_artifact_transitions(): void
    {
        $artifact = Artifact::factory()->create(['status' => ArtifactStatus::Building]);
        $artifact->transitionTo(ArtifactStatus::Ready);
        $this->assertEquals(ArtifactStatus::Ready, $artifact->fresh()->status);

        $artifact = Artifact::factory()->create(['status' => ArtifactStatus::Ready]);
        $artifact->transitionTo(ArtifactStatus::Deployed);
        $this->assertEquals(ArtifactStatus::Deployed, $artifact->fresh()->status);

        $artifact->transitionTo(ArtifactStatus::Superseded);
        $artifact->transitionTo(ArtifactStatus::Expired);
        $this->assertEquals(ArtifactStatus::Expired, $artifact->fresh()->status);
    }

    public function test_invalid_artifact_transition_throws(): void
    {
        $artifact = Artifact::factory()->create(['status' => ArtifactStatus::Building]);

        $this->expectException(InvalidArgumentException::class);

        $artifact->transitionTo(ArtifactStatus::Superseded);
    }
}
