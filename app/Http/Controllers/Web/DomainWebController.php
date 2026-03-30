<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Networking\AssignDomainRequest;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Networking\Actions\AssignDomain;
use App\Modules\Networking\Actions\RemoveDomain;
use App\Modules\Networking\Actions\VerifyDomain;
use App\Modules\Networking\Models\Domain;
use Illuminate\Http\RedirectResponse;

class DomainWebController extends Controller
{
    public function store(AssignDomainRequest $request, Environment $environment): RedirectResponse
    {
        (new AssignDomain)->execute($environment, $request->validated());

        return redirect()->route('environments.show', $environment)->with('success', 'Domain added to the environment.');
    }

    public function destroy(Domain $domain): RedirectResponse
    {
        $domain->loadMissing('environment');
        $environment = $domain->environment;

        (new RemoveDomain)->execute($domain);

        return redirect()->route('environments.show', $environment)->with('success', 'Domain removed.');
    }

    public function verify(Domain $domain): RedirectResponse
    {
        $domain->loadMissing('environment');
        $environment = $domain->environment;

        $verifiedDomain = (new VerifyDomain)->execute($domain);

        return redirect()->route('environments.show', $environment)->with(
            $verifiedDomain->is_verified ? 'success' : 'error',
            $verifiedDomain->is_verified
                ? 'Domain verification succeeded.'
                : 'Domain verification did not pass yet. Check DNS and try again.',
        );
    }
}
