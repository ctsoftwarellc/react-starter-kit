<?php

namespace App\Http\Controllers\Api\Networking;

use App\Http\Controllers\Controller;
use App\Http\Resources\Networking\CertificateResource;
use App\Modules\Networking\Actions\SyncCertificateStatus;
use App\Modules\Networking\Models\Domain;

class CertificateController extends Controller
{
    public function show(Domain $domain): CertificateResource
    {
        $certificate = (new SyncCertificateStatus)->execute($domain);

        return new CertificateResource($certificate);
    }
}
