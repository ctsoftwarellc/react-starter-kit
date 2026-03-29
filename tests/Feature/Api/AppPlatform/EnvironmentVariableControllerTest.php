<?php

namespace Tests\Feature\Api\AppPlatform;

use App\Models\User;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\EnvironmentVariable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnvironmentVariableControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_environment_variables(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        EnvironmentVariable::factory()->count(2)->create(['environment_id' => $environment->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/environments/'.$environment->id.'/variables')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_set_environment_variable(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/environments/'.$environment->id.'/variables', [
                'key' => 'APP_ENV',
                'value' => 'production',
                'is_build_arg' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('data.key', 'APP_ENV');
    }

    public function test_can_update_environment_variable(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        $variable = EnvironmentVariable::factory()->create([
            'environment_id' => $environment->id,
            'key' => 'OLD_KEY',
            'value' => 'old',
        ]);

        $this->actingAs($user)
            ->putJson('/api/v1/environments/'.$environment->id.'/variables/'.$variable->id, [
                'key' => 'NEW_KEY',
                'value' => 'new',
                'is_build_arg' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $variable->id)
            ->assertJsonPath('data.key', 'NEW_KEY');

        $this->assertDatabaseMissing('environment_variables', [
            'environment_id' => $environment->id,
            'key' => 'OLD_KEY',
        ]);
        $this->assertSame(1, EnvironmentVariable::where('environment_id', $environment->id)->count());
    }

    public function test_can_delete_environment_variable(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        $variable = EnvironmentVariable::factory()->create(['environment_id' => $environment->id]);

        $this->actingAs($user)
            ->deleteJson('/api/v1/environments/'.$environment->id.'/variables/'.$variable->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('environment_variables', ['id' => $variable->id]);
    }
}
