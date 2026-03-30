<?php

namespace Tests\Unit\Modules\Deployment\Actions;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Actions\RollbackDeployment;
use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Enums\ReleaseStatus;
use App\Modules\Deployment\Jobs\ExecuteDeployment;
use App\Modules\Deployment\Models\Release;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class RollbackDeploymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_manual_rollback_deployment_for_a_selected_release(): void
    {
        Bus::fake();

        $environment = Environment::factory()->create();
        $activeRelease = Release::factory()->create([
            'environment_id' => $environment->id,
            'version' => 1,
            'status' => ReleaseStatus::Active,
        ]);
        $rollbackRelease = Release::factory()->create([
            'environment_id' => $environment->id,
            'version' => 2,
            'status' => ReleaseStatus::Superseded,
            'config_snapshot' => ['env_vars' => ['APP_ENV' => 'production']],
        ]);

        $environment->update(['active_release_id' => $activeRelease->id]);

        $deployment = (new RollbackDeployment)->execute($environment->fresh(), $rollbackRelease->fresh());

        $this->assertDatabaseHas('deployments', [
            'id' => $deployment->id,
            'environment_id' => $environment->id,
            'status' => DeploymentStatus::Pending->value,
        ]);

        $clonedRelease = $deployment->release()->firstOrFail();

        $this->assertNotSame($rollbackRelease->id, $clonedRelease->id);
        $this->assertSame($rollbackRelease->artifact_id, $clonedRelease->artifact_id);
        $this->assertSame($rollbackRelease->config_snapshot, $clonedRelease->config_snapshot);
        $this->assertSame(ReleaseStatus::Deploying, $clonedRelease->fresh()->status);
        $this->assertSame(3, $clonedRelease->version);

        Bus::assertDispatched(ExecuteDeployment::class, 1);
    }
}
