<?php

namespace Tests\Unit\Modules\Networking\Jobs;

use App\Modules\Networking\Jobs\RenewExpiringCertificates;
use App\Modules\Networking\Models\Certificate;
use App\Modules\Networking\Models\Domain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
class RenewExpiringCertificatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_expiring_certificate_statuses(): void
    {
        $secondDomain = Domain::factory()->verified()->create();
        $skippedDomain = Domain::factory()->verified()->create();

        Certificate::factory()->active()->create([
            'domain_id' => $secondDomain->id,
            'expires_at' => now()->addDays(7),
        ]);
        Certificate::factory()->active()->create([
            'domain_id' => $skippedDomain->id,
            'expires_at' => now()->addDays(45),
        ]);

        $syncedDomainIds = [];

        $sync = Mockery::mock('overload:App\Modules\Networking\Actions\SyncCertificateStatus');
        $sync->shouldReceive('execute')->once()->andReturnUsing(function (Domain $domain) use (&$syncedDomainIds): void {
            $syncedDomainIds[] = $domain->id;
        });

        (new RenewExpiringCertificates)->handle();

        sort($syncedDomainIds);
        $expectedIds = [$secondDomain->id];
        sort($expectedIds);

        $this->assertSame($expectedIds, $syncedDomainIds);
    }
}
