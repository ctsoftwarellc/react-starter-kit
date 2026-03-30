<?php

namespace Tests\Feature\Api\Networking;

use App\Models\User;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Networking\Enums\CertificateStatus;
use App\Modules\Networking\Models\Certificate;
use App\Modules\Networking\Models\Domain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
class DomainControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_environment_domains(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        $primary = Domain::factory()->primary()->create(['environment_id' => $environment->id, 'hostname' => 'b.example.test']);
        Certificate::factory()->create(['domain_id' => $primary->id]);
        $secondary = Domain::factory()->create(['environment_id' => $environment->id, 'hostname' => 'a.example.test']);
        Certificate::factory()->create(['domain_id' => $secondary->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/environments/'.$environment->id.'/domains')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $primary->id)
            ->assertJsonPath('data.1.id', $secondary->id);
    }

    public function test_it_creates_a_domain(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/environments/'.$environment->id.'/domains', [
                'hostname' => 'App.Example.TEST',
                'is_primary' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.hostname', 'app.example.test')
            ->assertJsonPath('data.certificate.status', CertificateStatus::Pending->value);

        $this->assertDatabaseHas('domains', [
            'environment_id' => $environment->id,
            'hostname' => 'app.example.test',
            'is_primary' => 1,
        ]);
    }

    public function test_it_deletes_a_domain(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $domain = Domain::factory()->create();
        $certificate = Certificate::factory()->create(['domain_id' => $domain->id]);

        $this->actingAs($user)
            ->deleteJson('/api/v1/domains/'.$domain->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('domains', ['id' => $domain->id]);
        $this->assertDatabaseMissing('certificates', ['id' => $certificate->id]);
    }

    public function test_it_verifies_a_domain(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $domain = Domain::factory()->create(['is_verified' => false]);
        Certificate::factory()->create(['domain_id' => $domain->id]);

        $dnsVerifier = \Mockery::mock('overload:App\Modules\Networking\Services\DnsVerifier');
        $dnsVerifier->shouldReceive('verify')->once()->andReturn(true);

        $inspector = \Mockery::mock('overload:App\Modules\Networking\Services\TlsCertificateInspector');
        $inspector->shouldReceive('inspect')->once()->andReturn([
            'issued_at' => now()->subDay(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($user)
            ->postJson('/api/v1/domains/'.$domain->id.'/verify')
            ->assertOk()
            ->assertJsonPath('data.is_verified', true)
            ->assertJsonPath('data.certificate.status', CertificateStatus::Active->value);
    }
}
