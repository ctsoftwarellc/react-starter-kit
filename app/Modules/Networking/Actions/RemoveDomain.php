<?php

namespace App\Modules\Networking\Actions;

use App\Modules\Networking\Jobs\PushProxyConfig as PushProxyConfigJob;
use App\Modules\Networking\Models\Domain;
use Illuminate\Support\Facades\DB;

class RemoveDomain
{
    public function execute(Domain $domain): void
    {
        $domain->loadMissing('environment.cluster', 'certificate');

        DB::transaction(function () use ($domain) {
            $cluster = $domain->environment?->cluster;
            $wasVerified = $domain->is_verified;

            $domain->certificate()?->delete();
            $domain->delete();

            if ($wasVerified && $cluster !== null) {
                PushProxyConfigJob::dispatch($cluster)->afterCommit();
            }
        });
    }
}
