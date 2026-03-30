<?php

namespace App\Modules\Networking\Actions;

use App\Modules\Networking\Enums\CertificateStatus;
use App\Modules\Networking\Events\DomainVerified;
use App\Modules\Networking\Models\Domain;
use App\Modules\Networking\Services\DnsVerifier;
use App\Modules\Networking\Services\TlsCertificateInspector;
use Illuminate\Support\Facades\DB;

class VerifyDomain
{
    public function __construct(
        private readonly DnsVerifier $dnsVerifier = new DnsVerifier,
        private readonly TlsCertificateInspector $inspector = new TlsCertificateInspector,
    ) {}

    public function execute(Domain $domain): Domain
    {
        $domain->loadMissing('certificate', 'environment.cluster.servers');

        if (! $this->dnsVerifier->verify($domain)) {
            return $domain->fresh(['certificate']);
        }

        $alreadyVerified = $domain->is_verified;

        $verifiedDomain = DB::transaction(function () use ($domain) {
            $domain->forceFill([
                'is_verified' => true,
            ])->save();

            $certificate = $domain->certificate;

            if ($certificate !== null) {
                $metadata = $this->inspector->inspect($domain->hostname);

                $certificate->update([
                    'status' => $metadata !== null
                        ? (($metadata['expires_at'] !== null && $metadata['expires_at']->isPast())
                            ? CertificateStatus::Expired
                            : CertificateStatus::Active)
                        : CertificateStatus::Pending,
                    'issued_at' => $metadata['issued_at'] ?? null,
                    'expires_at' => $metadata['expires_at'] ?? null,
                ]);
            }

            return $domain->fresh(['certificate']);
        });

        if (! $alreadyVerified) {
            event(new DomainVerified($verifiedDomain));
        }

        return $verifiedDomain;
    }
}
