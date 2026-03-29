<?php

namespace App\Http\Controllers\Api\AppPlatform;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppPlatform\GitConnectionResource;
use App\Modules\AppPlatform\Actions\DeleteGitConnection;
use App\Modules\AppPlatform\Models\GitConnection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class GitConnectionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return GitConnectionResource::collection(GitConnection::latest()->paginate(15));
    }

    public function destroy(GitConnection $gitConnection): Response
    {
        (new DeleteGitConnection)->execute($gitConnection);

        return response()->noContent();
    }
}
