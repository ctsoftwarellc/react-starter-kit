<?php

namespace Tests\Feature\Api\Deployment;

use App\Models\User;
use App\Modules\AppPlatform\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuntimeProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_runtime_profile(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $application = Application::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/applications/'.$application->id.'/runtime-profiles', [
                'name' => 'PHP Runtime',
                'stack' => 'php-fpm',
                'config' => ['php_version' => '8.4'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.application_id', $application->id)
            ->assertJsonPath('data.stack', 'php-fpm');

        $this->assertDatabaseHas('runtime_profiles', [
            'application_id' => $application->id,
            'name' => 'PHP Runtime',
        ]);
    }
}
