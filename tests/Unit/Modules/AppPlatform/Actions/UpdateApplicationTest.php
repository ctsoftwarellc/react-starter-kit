<?php

namespace Tests\Unit\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Actions\UpdateApplication;
use App\Modules\AppPlatform\DTOs\UpdateApplicationData;
use App\Modules\AppPlatform\Enums\Runtime;
use App\Modules\AppPlatform\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_application_fields(): void
    {
        $application = Application::factory()->create([
            'runtime' => Runtime::Php,
            'repository_branch' => 'main',
            'settings' => [],
        ]);

        $updated = (new UpdateApplication)->execute($application, new UpdateApplicationData(
            runtime: Runtime::Node,
            repositoryUrl: 'https://github.com/caleb/new-repo',
            repositoryBranch: 'develop',
            settings: ['build' => 'npm run build'],
            hasRuntime: true,
            hasRepositoryUrl: true,
            hasRepositoryBranch: true,
            hasSettings: true,
        ));

        $this->assertSame(Runtime::Node, $updated->runtime);
        $this->assertSame('develop', $updated->repository_branch);
        $this->assertSame(['build' => 'npm run build'], $updated->settings);
    }

    public function test_it_regenerates_slug_when_name_changes(): void
    {
        $application = Application::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

        $updated = (new UpdateApplication)->execute($application, new UpdateApplicationData(
            name: 'New Name',
            hasName: true,
        ));

        $this->assertSame('new-name', $updated->slug);
    }
}
