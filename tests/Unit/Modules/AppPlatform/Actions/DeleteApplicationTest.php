<?php

namespace Tests\Unit\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Actions\DeleteApplication;
use App\Modules\AppPlatform\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_soft_deletes_application(): void
    {
        $application = Application::factory()->create();

        (new DeleteApplication)->execute($application);

        $this->assertSoftDeleted('applications', ['id' => $application->id]);
    }
}
