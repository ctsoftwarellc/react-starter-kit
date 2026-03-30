<?php

namespace Tests\Feature\Web;

use App\Models\User;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Networking\Models\Domain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
class EnvironmentDomainManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_verify_and_remove_domains_from_environment_page(): void
    {
        $this->withoutVite();

        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();

        $this->actingAs($user)
            ->post(route('domains.store', $environment), [
                'hostname' => 'app.example.test',
                'is_primary' => true,
            ])
            ->assertRedirect(route('environments.show', $environment))
            ->assertSessionHas('success', 'Domain added to the environment.');

        $domain = Domain::query()->where('environment_id', $environment->id)->firstOrFail();

        $dnsVerifier = \Mockery::mock('overload:App\Modules\Networking\Services\DnsVerifier');
        $dnsVerifier->shouldReceive('verify')->once()->andReturn(true);

        $inspector = \Mockery::mock('overload:App\Modules\Networking\Services\TlsCertificateInspector');
        $inspector->shouldReceive('inspect')->once()->andReturn([
            'issued_at' => now()->subDay(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($user)
            ->post(route('domains.verify', $domain))
            ->assertRedirect(route('environments.show', $environment))
            ->assertSessionHas('success', 'Domain verification succeeded.');

        $this->actingAs($user)
            ->get(route('environments.show', $environment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('environments/show')
                ->has('domains', 1)
                ->where('domains.0.hostname', 'app.example.test')
                ->where('domains.0.is_verified', true));

        $this->actingAs($user)
            ->delete(route('domains.destroy', $domain))
            ->assertRedirect(route('environments.show', $environment))
            ->assertSessionHas('success', 'Domain removed.');

        $this->assertDatabaseMissing('domains', ['id' => $domain->id]);
    }
}
