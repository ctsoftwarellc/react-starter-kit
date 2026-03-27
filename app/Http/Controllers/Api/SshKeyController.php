<?php

namespace App\Http\Controllers\Api;

use App\Actions\AddSshKey;
use App\Actions\RemoveSshKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddSshKeyRequest;
use App\Http\Resources\SshKeyResource;
use App\Models\SshKey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SshKeyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $keys = $request->user()
            ->sshKeys()
            ->latest()
            ->paginate(15);

        return SshKeyResource::collection($keys);
    }

    public function store(AddSshKeyRequest $request): SshKeyResource
    {
        $key = (new AddSshKey)->execute(
            user: $request->user(),
            name: $request->validated('name'),
            publicKey: $request->validated('public_key'),
        );

        return new SshKeyResource($key);
    }

    public function destroy(SshKey $sshKey): Response
    {
        (new RemoveSshKey)->execute($sshKey);

        return response()->noContent();
    }
}
