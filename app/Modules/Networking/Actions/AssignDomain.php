<?php

namespace App\Modules\Networking\Actions;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Networking\Enums\CertificateStatus;
use App\Modules\Networking\Events\DomainAssigned;
use App\Modules\Networking\Models\Certificate;
use App\Modules\Networking\Models\Domain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssignDomain
{
    public function execute(Environment $environment, array $attributes): Domain
    {
        return DB::transaction(function () use ($environment, $attributes) {
            $hostname = Str::lower(trim((string) $attributes['hostname']));
            $isPrimary = (bool) ($attributes['is_primary'] ?? false);

            if ($isPrimary) {
                $environment->domains()->where('is_primary', true)->update(['is_primary' => false]);
            }

            $domain = $environment->domains()->create([
                'hostname' => $hostname,
                'is_primary' => $isPrimary,
                'is_verified' => false,
                'verification_token' => hash('sha256', Str::lower(Str::random(40))),
            ]);

            Certificate::create([
                'domain_id' => $domain->id,
                'type' => 'auto',
                'status' => CertificateStatus::Pending,
            ]);

            event(new DomainAssigned($domain));

            return $domain->fresh(['certificate']);
        });
    }
}
