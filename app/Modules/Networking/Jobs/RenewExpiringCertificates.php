<?php

namespace App\Modules\Networking\Jobs;

use App\Modules\Networking\Actions\SyncCertificateStatus;
use App\Modules\Networking\Enums\CertificateStatus;
use App\Modules\Networking\Models\Certificate;
use App\Support\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RenewExpiringCertificates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function handle(): void
    {
        Certificate::query()
            ->with('domain')
            ->where(function ($query): void {
                $query->where('status', CertificateStatus::Pending)
                    ->orWhere(function ($inner): void {
                        $inner->where('status', CertificateStatus::Active)
                            ->whereNotNull('expires_at')
                            ->where('expires_at', '<=', now()->addDays(14));
                    });
            })
            ->get()
            ->each(function (Certificate $certificate): void {
                if ($certificate->domain !== null) {
                    (new SyncCertificateStatus)->execute($certificate->domain);
                }
            });
    }

    public function queue(): string
    {
        return QueueName::Maintenance->value;
    }
}
