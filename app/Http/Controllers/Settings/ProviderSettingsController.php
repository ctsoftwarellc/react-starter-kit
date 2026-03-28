<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Infrastructure\CreateProviderRequest;
use App\Http\Requests\Infrastructure\UpdateProviderRequest;
use App\Modules\Infrastructure\Actions\CreateProvider;
use App\Modules\Infrastructure\Actions\DeleteProvider;
use App\Modules\Infrastructure\Actions\TestProviderConnection;
use App\Modules\Infrastructure\Actions\UpdateProvider;
use App\Modules\Infrastructure\DTOs\CreateProviderData;
use App\Modules\Infrastructure\DTOs\UpdateProviderData;
use App\Modules\Infrastructure\Models\Provider;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProviderSettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/providers', [
            'providers' => Provider::latest()->get(),
        ]);
    }

    public function store(CreateProviderRequest $request): RedirectResponse
    {
        (new CreateProvider)->execute(
            CreateProviderData::from($request->validated()),
        );

        return back()->with('success', 'Provider created.');
    }

    public function update(UpdateProviderRequest $request, Provider $provider): RedirectResponse
    {
        (new UpdateProvider)->execute(
            $provider,
            UpdateProviderData::from($request->validated()),
        );

        return back()->with('success', 'Provider updated.');
    }

    public function destroy(Provider $provider): RedirectResponse
    {
        (new DeleteProvider)->execute($provider);

        return back()->with('success', 'Provider deleted.');
    }

    public function test(Provider $provider): RedirectResponse
    {
        $success = (new TestProviderConnection)->execute($provider);

        return back()->with(
            $success ? 'success' : 'error',
            $success ? 'Connection successful.' : 'Connection failed.',
        );
    }
}
