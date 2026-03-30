<?php

namespace Tests\Feature\Api\Networking;

use App\Models\User;
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
class CertificateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_domain_certificate_status(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $domain = Domain::factory()->verified()->create();
        Certificate::factory()->create([
            'domain_id' => $domain->id,
            'status' => CertificateStatus::Pending,
        ]);

        $certificate = $domain->certificate()->firstOrFail()->forceFill([
            'status' => CertificateStatus::Active,
            'issued_at' => now()->subDay(),
            'expires_at' => now()->addDays(45),
        ]);

        $sync = \Mockery::mock('overload:App\Modules\Networking\Actions\SyncCertificateStatus');
        $sync->shouldReceive('execute')->once()->andReturn($certificate);

        $this->actingAs($user)
            ->getJson('/api/v1/domains/'.$domain->id.'/certificate')
            ->assertOk()
            ->assertJsonPath('data.domain_id', $domain->id)
            ->assertJsonPath('data.status', CertificateStatus::Active->value);
    }
}
