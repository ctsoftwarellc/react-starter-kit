<?php

namespace Tests\Unit\Modules\Networking\Actions;

use App\Modules\Networking\Actions\VerifyDomain;
use App\Modules\Networking\Enums\CertificateStatus;
use App\Modules\Networking\Events\DomainVerified;
use App\Modules\Networking\Models\Certificate;
use App\Modules\Networking\Models\Domain;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
class VerifyDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_domain_verified_when_dns_matches(): void
    {
        Event::fake();

        $domain = Domain::factory()->create(['is_verified' => false]);
        Certificate::factory()->create(['domain_id' => $domain->id]);
        $expiresAt = CarbonImmutable::now()->addDays(30);

        $dnsVerifier = Mockery::mock('overload:App\Modules\Networking\Services\DnsVerifier');
        $dnsVerifier->shouldReceive('verify')->once()->andReturn(true);

        $inspector = Mockery::mock('overload:App\Modules\Networking\Services\TlsCertificateInspector');
        $inspector->shouldReceive('inspect')->once()->with($domain->hostname)->andReturn([
            'issued_at' => CarbonImmutable::now()->subDay(),
            'expires_at' => $expiresAt,
        ]);

        $verified = (new VerifyDomain)->execute($domain);

        $this->assertTrue($verified->is_verified);
        $this->assertSame(CertificateStatus::Active, $verified->certificate->status);
        $this->assertSame($expiresAt->timestamp, $verified->certificate->expires_at->timestamp);

        Event::assertDispatched(DomainVerified::class, fn (DomainVerified $event) => $event->domain->is($verified));
    }

    public function test_it_leaves_domain_unverified_when_dns_does_not_match(): void
    {
        Event::fake();

        $domain = Domain::factory()->create(['is_verified' => false]);
        $certificate = Certificate::factory()->create(['domain_id' => $domain->id]);

        $dnsVerifier = Mockery::mock('overload:App\Modules\Networking\Services\DnsVerifier');
        $dnsVerifier->shouldReceive('verify')->once()->andReturn(false);

        $inspector = Mockery::mock('overload:App\Modules\Networking\Services\TlsCertificateInspector');
        $inspector->shouldReceive('inspect')->never();

        $verified = (new VerifyDomain)->execute($domain);

        $this->assertFalse($verified->is_verified);
        $this->assertSame($certificate->fresh()->status, $verified->certificate->status);

        Event::assertNotDispatched(DomainVerified::class);
    }

    public function test_it_dispatches_domain_verified_event_only_on_first_success(): void
    {
        Event::fake();

        $domain = Domain::factory()->verified()->create();
        Certificate::factory()->create(['domain_id' => $domain->id]);

        $dnsVerifier = Mockery::mock('overload:App\Modules\Networking\Services\DnsVerifier');
        $dnsVerifier->shouldReceive('verify')->once()->andReturn(true);

        $inspector = Mockery::mock('overload:App\Modules\Networking\Services\TlsCertificateInspector');
        $inspector->shouldReceive('inspect')->once()->andReturn(null);

        (new VerifyDomain)->execute($domain);

        Event::assertNotDispatched(DomainVerified::class);
    }
}
