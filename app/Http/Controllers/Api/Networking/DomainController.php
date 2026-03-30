<?php

namespace App\Http\Controllers\Api\Networking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Networking\AssignDomainRequest;
use App\Http\Resources\Networking\DomainResource;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Networking\Actions\AssignDomain;
use App\Modules\Networking\Actions\RemoveDomain;
use App\Modules\Networking\Actions\VerifyDomain;
use App\Modules\Networking\Models\Domain;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DomainController extends Controller
{
    public function index(Environment $environment): AnonymousResourceCollection
    {
        return DomainResource::collection(
            $environment->domains()->with('certificate')->orderByDesc('is_primary')->orderBy('hostname')->get(),
        );
    }

    public function store(AssignDomainRequest $request, Environment $environment): DomainResource
    {
        $domain = (new AssignDomain)->execute($environment, $request->validated());

        return new DomainResource($domain->load('certificate'));
    }

    public function destroy(Domain $domain): Response
    {
        (new RemoveDomain)->execute($domain);

        return response()->noContent();
    }

    public function verify(Domain $domain): DomainResource
    {
        $domain = (new VerifyDomain)->execute($domain);

        return new DomainResource($domain->load('certificate'));
    }
}
