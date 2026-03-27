<?php

namespace App\Http\Controllers\Settings;

use App\Actions\AddSshKey;
use App\Actions\RemoveSshKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddSshKeyRequest;
use App\Models\SshKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SshKeySettingsController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('settings/ssh-keys', [
            'sshKeys' => $request->user()->sshKeys()->latest()->get(),
        ]);
    }

    public function store(AddSshKeyRequest $request): RedirectResponse
    {
        (new AddSshKey)->execute(
            user: $request->user(),
            name: $request->validated('name'),
            publicKey: $request->validated('public_key'),
        );

        return back();
    }

    public function destroy(SshKey $sshKey): RedirectResponse
    {
        (new RemoveSshKey)->execute($sshKey);

        return back();
    }
}
