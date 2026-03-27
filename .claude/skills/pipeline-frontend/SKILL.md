---
name: pipeline-frontend
description: Pipeline Stage 3 — Implement the HTTP layer and frontend for a build phase. Creates controllers, form requests, API resources, routes, and Inertia React pages.
argument-hint: "[phase-number]"
disable-model-invocation: true
allowed-tools: Read, Write, Edit, Bash, Grep, Glob
---

# Pipeline Stage: Frontend (HTTP + UI)

You are implementing the HTTP layer and React frontend for Phase $ARGUMENTS of the Helm platform.

## Context

Read these files before writing any code:
1. `CLAUDE.md` — controller rules, route files, request/resource patterns
2. `.planning/phase-$ARGUMENTS-plan.md` — Controller + Route Plan, Frontend Plan sections
3. `.planning/architecture.md` — API design (Section 13), UI screens (Section 14)

Also explore what already exists:
- `resources/js/layouts/` — existing layout components
- `resources/js/components/` — existing UI components (buttons, forms, tables, etc.)
- `resources/js/pages/` — existing pages for reference on Inertia patterns
- `resources/js/types/` — existing TypeScript type definitions
- `routes/web.php`, `routes/api.php` — existing route patterns

The backend domain layer (models, actions, events, jobs) already exists. You are building the HTTP interface and user-facing UI on top of it.

## Implementation Order

### Step 1: Form Requests

Create Laravel FormRequest classes in `app/Http/Requests/{Module}/`:

```php
namespace App\Http\Requests\Infrastructure;

use Illuminate\Foundation\Http\FormRequest;

class RegisterServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Single-user tool — auth middleware handles it
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'public_ip' => ['required', 'ip'],
            // ...
        ];
    }
}
```

### Step 2: API Resources

Create JSON API resources in `app/Http/Resources/{Module}/`:

```php
namespace App\Http\Resources\Infrastructure;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status->value,
            'public_ip' => $this->public_ip,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Include loaded relationships conditionally
            'provider' => new ProviderResource($this->whenLoaded('provider')),
        ];
    }
}
```

Never expose encrypted fields (tokens, credentials, secrets) in resources.

### Step 3: API Controllers

Create thin controllers in `app/Http/Controllers/Api/{Module}/`:

```php
namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;

class ServerController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $servers = Server::with('provider')->latest()->paginate(25);
        return ServerResource::collection($servers);
    }

    public function store(RegisterServerRequest $request): ServerResource
    {
        $server = (new RegisterServer)->execute(
            RegisterServerData::from($request->validated())
        );
        return new ServerResource($server);
    }

    public function show(Server $server): ServerResource
    {
        return new ServerResource($server->load('provider', 'clusters'));
    }

    // ... update, destroy, and action endpoints
}
```

Controller rules:
- Max ONE Action call per method
- No business logic, DB queries, or conditionals beyond what's shown above
- Use route model binding for show/update/destroy
- Return API Resources for JSON endpoints
- Return `Inertia::render()` for web endpoints

### Step 4: Web Controllers (Inertia)

For pages that need Inertia rendering, create web controllers:

```php
namespace App\Http\Controllers\Web;

use Inertia\Inertia;
use Inertia\Response;

class ServerPageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('servers/index', [
            'servers' => ServerResource::collection(
                Server::with('provider')->latest()->paginate(25)
            ),
        ]);
    }

    public function show(Server $server): Response
    {
        return Inertia::render('servers/show', [
            'server' => new ServerResource($server->load('provider', 'clusters')),
        ]);
    }
}
```

### Step 5: Routes

Add routes to the correct file based on the plan:

**`routes/api.php`** — user-facing API endpoints:
```php
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::apiResource('servers', ServerController::class);
    Route::post('servers/{server}/bootstrap', [ServerController::class, 'bootstrap']);
});
```

**`routes/web.php`** — Inertia pages:
```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('servers', [ServerPageController::class, 'index'])->name('servers.index');
    Route::get('servers/{server}', [ServerPageController::class, 'show'])->name('servers.show');
});
```

**`routes/agent.php`** — agent API (with agent auth middleware)
**`routes/runner.php`** — runner API (with runner auth middleware)
**`routes/webhooks.php`** — webhook endpoints (no auth, signature verified)

### Step 6: TypeScript Types

Add type definitions matching API resource shapes:

```typescript
// resources/js/types/models.d.ts or resources/js/types/index.d.ts
export interface Server {
    id: string;
    name: string;
    status: string;
    public_ip: string;
    provider?: Provider;
    created_at: string;
    updated_at: string;
}
```

### Step 7: Inertia Pages

Create React pages in `resources/js/pages/`. Follow existing patterns in the codebase:

- Use the existing layout component
- Use existing UI components (buttons, inputs, tables, etc.)
- Receive data as typed props from Inertia
- Use `router.post()`, `router.put()`, `router.delete()` for mutations
- Use `useForm()` for form handling

Look at existing pages like `resources/js/pages/dashboard.tsx` and `resources/js/pages/settings/` for patterns.

For new pages:
- **List pages**: table with filters, pagination, action buttons
- **Detail pages**: header with status/actions, tabbed content
- **Form pages/modals**: use `useForm()` hook for validation integration
- **Settings pages**: match existing settings layout pattern

## Final Checks

After all code is written:
1. Run `./vendor/bin/pint` to fix PHP code style
2. Run `php artisan route:list` to verify no route errors
3. Run `npx tsc --noEmit` to check TypeScript (if applicable)
4. Manually verify key pages render: `php artisan serve` and click through

## Rules

- The backend domain layer is already built — do NOT modify models, actions, events, or jobs
- If you need a query that doesn't exist as a scope, add the scope to the model
- Follow the plan document for which controllers and routes to create
- Match existing UI patterns in the codebase — don't invent new component patterns
