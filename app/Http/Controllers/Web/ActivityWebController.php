<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Operations\Models\AuditLog;
use Inertia\Inertia;
use Inertia\Response;

class ActivityWebController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('activity/index', [
            'logs' => AuditLog::latest()->paginate(25),
        ]);
    }
}
