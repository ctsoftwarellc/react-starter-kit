<?php

namespace App\Modules\Networking\Actions;

use App\Modules\Networking\Enums\CertificateStatus;
use App\Modules\Networking\Models\Certificate;
use App\Modules\Networking\Models\Domain;
use App\Modules\Networking\Services\TlsCertificateInspector;

class SyncCertificateStatus
{
    public function __construct(
        private readonly TlsCertificateInspector $inspector = new TlsCertificateInspector,
    ) {}

    public function execute(Domain $domain): Certificate
    {
        $domain->loadMissing('certificate');

        $certificate = $domain->certificate;

        if ($certificate === null) {
            throw new \RuntimeException('A domain certificate record is required before syncing status.');
        }

        $metadata = $domain->is_verified ? $this->inspector->inspect($domain->hostname) : null;

        if ($metadata !== null) {
            $expiresAt = $metadata['expires_at'];

            $certificate->update([
                'status' => $expiresAt !== null && $expiresAt->isPast()
                    ? CertificateStatus::Expired
                    : CertificateStatus::Active,
                'issued_at' => $metadata['issued_at'],
                'expires_at' => $expiresAt,
            ]);

            return $certificate->fresh();
        }

        $certificate->update([
            'status' => $certificate->expires_at !== null && $certificate->expires_at->isPast()
                ? CertificateStatus::Expired
                : CertificateStatus::Pending,
        ]);

        return $certificate->fresh();
    }
}
