<?php

namespace Tests\Unit\Modules\Networking\Actions;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Networking\Actions\AssignDomain;
use App\Modules\Networking\Enums\CertificateStatus;
use App\Modules\Networking\Events\DomainAssigned;
use App\Modules\Networking\Models\Domain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AssignDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_assigns_a_domain_and_creates_pending_certificate(): void
    {
        Event::fake();

        $environment = Environment::factory()->create();

        $domain = (new AssignDomain)->execute($environment, [
            'hostname' => 'WWW.Example.TEST ',
            'is_primary' => true,
        ]);

        $this->assertSame('www.example.test', $domain->hostname);
        $this->assertTrue($domain->is_primary);
        $this->assertFalse($domain->is_verified);
        $this->assertNotEmpty($domain->verification_token);
        $this->assertSame(CertificateStatus::Pending, $domain->certificate->status);

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'environment_id' => $environment->id,
            'hostname' => 'www.example.test',
            'is_primary' => 1,
            'is_verified' => 0,
        ]);

        Event::assertDispatched(DomainAssigned::class, fn (DomainAssigned $event) => $event->domain->is($domain));
    }

    public function test_it_demotes_existing_primary_domain_when_new_primary_is_assigned(): void
    {
        $environment = Environment::factory()->create();
        $existing = Domain::factory()->primary()->create(['environment_id' => $environment->id]);

        $newPrimary = (new AssignDomain)->execute($environment, [
            'hostname' => 'api.example.test',
            'is_primary' => true,
        ]);

        $this->assertFalse($existing->fresh()->is_primary);
        $this->assertTrue($newPrimary->is_primary);
    }
}
