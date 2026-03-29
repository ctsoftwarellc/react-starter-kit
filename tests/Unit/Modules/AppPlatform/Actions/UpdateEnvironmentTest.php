<?php

namespace Tests\Unit\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Actions\UpdateEnvironment;
use App\Modules\AppPlatform\DTOs\UpdateEnvironmentData;
use App\Modules\AppPlatform\Enums\EnvironmentType;
use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Infrastructure\Models\Cluster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UpdateEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_cluster_branch_and_auto_deploy(): void
    {
        Event::fake();

        $environment = Environment::factory()->create([
            'type' => EnvironmentType::Production,
            'is_auto_deploy' => false,
            'branch' => 'main',
        ]);
        $cluster = Cluster::factory()->create();

        $updated = (new UpdateEnvironment)->execute($environment, new UpdateEnvironmentData(
            clusterId: $cluster->id,
            type: EnvironmentType::Staging,
            isAutoDeploy: true,
            branch: 'develop',
            hasClusterId: true,
            hasType: true,
            hasIsAutoDeploy: true,
            hasBranch: true,
        ));

        $this->assertSame($cluster->id, $updated->cluster_id);
        $this->assertSame(EnvironmentType::Staging, $updated->type);
        $this->assertTrue($updated->is_auto_deploy);
        $this->assertSame('develop', $updated->branch);
        Event::assertDispatched(EnvironmentConfigChanged::class);
    }
}
