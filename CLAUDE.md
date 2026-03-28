# Helm — Development Guide

> Personal self-hosted VPS cluster management and application delivery platform.
> Single-user tool, NOT multi-tenant SaaS. No organizations, teams, RBAC.
> Full architecture: `.planning/architecture.md`

## Stack

- **Laravel 13** with PHP 8.4
- **Inertia v3 + React/TypeScript** frontend
- **PostgreSQL** primary database
- **Redis** for sessions, cache, and queues
- **S3-compatible storage** for artifacts, pipeline logs, backups (local disk fallback for dev)
- **Fortify** for auth (login, register, 2FA, password reset)
- **Caddy** reverse proxy on managed servers (not this app)

## Architecture

Modular monolith. 8 domain modules under `app/Modules/`, HTTP layer in standard `app/Http/`.

### Modules

| Module | Purpose |
|--------|---------|
| `Infrastructure` | Servers, clusters, providers, agents, SSH bootstrap |
| `AppPlatform` | Projects, applications, environments, env vars, secrets, git connections |
| `Pipeline` | CI/CD pipelines, runs, jobs, runners, artifacts, webhooks |
| `Deployment` | Releases, deployments, deployment steps, health checks, rollback |
| `Networking` | Domains, certificates, Caddy proxy config |
| `ServiceManagement` | Database/cache provisioning, process management (post-MVP) |
| `Observability` | Server metrics, alert rules, alerts (post-MVP) |
| `Operations` | Audit logs, backups, retention |

### Module Structure

Each module at `app/Modules/{Module}/` contains:

```
Actions/          — single-purpose business operations (one execute() method)
Models/           — Eloquent models
Enums/            — PHP 8.1+ backed enums
Events/           — domain events (past tense: ServerBootstrapped, not BootstrapServer)
Jobs/             — queued jobs (orchestrate, don't contain business logic)
Services/         — stateless services (SSH, API clients, config generators)
DTOs/             — data transfer objects
StateMachines/    — state transition logic (used via HasStateMachine trait)
```

Not every module needs every subdirectory. Only create what's needed.

## How to Build a Feature

### 1. Migration

Create migration with ULID primary keys for all new tables:

```php
$table->ulid('id')->primary();
```

Foreign keys to other ULID tables:

```php
$table->ulid('server_id');  // NOT foreignUlid — just ulid column
$table->foreign('server_id')->references('id')->on('servers');
```

Or shorthand when the convention matches:

```php
$table->foreignUlid('server_id')->constrained();
```

Use `jsonb` for flexible config fields. Use `varchar` for status/enum columns (not DB-level enums — easier to evolve). Always add indexes for foreign keys and columns used in WHERE clauses.

### 2. Enum

Create a backed string enum for any status/type field:

```php
// app/Modules/Infrastructure/Enums/ServerStatus.php
namespace App\Modules\Infrastructure\Enums;

enum ServerStatus: string
{
    case Pending = 'pending';
    case Provisioning = 'provisioning';
    case Active = 'active';
    // ...
}
```

### 3. Model

```php
// app/Modules/Infrastructure/Models/Server.php
namespace App\Modules\Infrastructure\Models;

use App\Support\Concerns\HasUlid;
use App\Support\Concerns\HasStateMachine;
use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    use HasUlid, HasStateMachine;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ServerStatus::class,
            'metadata' => 'json',
            'agent_token' => 'encrypted',
            'last_heartbeat_at' => 'datetime',
        ];
    }

    // Relationships
    public function provider(): BelongsTo { ... }
    public function clusters(): BelongsToMany { ... }

    // State machine (required by HasStateMachine)
    protected function getStatusEnum(): string
    {
        return ServerStatus::class;
    }

    protected function getAllowedTransitions(): array
    {
        return [
            'pending' => [ServerStatus::Provisioning, ServerStatus::Bootstrapping],
            'provisioning' => [ServerStatus::Bootstrapping, ServerStatus::Failed],
            // ... full map from architecture doc Section 7
        ];
    }
}
```

**Model rules:**
- Define relationships, scopes, casts, accessors
- NO business logic in models
- Always set `protected $guarded = []` (validation happens in FormRequests)
- Cast enum columns, JSON columns, dates, and encrypted fields

### 4. Action

The primary unit of business logic. One public `execute()` method.

```php
// app/Modules/Infrastructure/Actions/RegisterServer.php
namespace App\Modules\Infrastructure\Actions;

use Illuminate\Support\Facades\DB;

class RegisterServer
{
    public function execute(RegisterServerData $data): Server
    {
        return DB::transaction(function () use ($data) {
            $server = Server::create([
                'name' => $data->name,
                'public_ip' => $data->publicIp,
                'status' => ServerStatus::Pending,
                // ...
            ]);

            event(new ServerRegistered($server));

            return $server;
        });
    }
}
```

**Action rules:**
- Wrap multi-model mutations in `DB::transaction()`
- Dispatch events for cross-module side effects
- Return the created/modified resource
- Each Action is independently testable

### 5. Event + Listener

```php
// app/Modules/Infrastructure/Events/ServerRegistered.php
class ServerRegistered
{
    public function __construct(public Server $server) {}
}
```

Listeners for cross-cutting concerns (audit log, notifications). The `RecordAuditLog` listener at `app/Modules/Operations/Listeners/RecordAuditLog.php` should listen to all domain events.

### 6. Job (if async work needed)

```php
// app/Modules/Infrastructure/Jobs/BootstrapServer.php
class BootstrapServer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(public Server $server) {}

    public function handle(): void
    {
        // Call the action — jobs orchestrate, they don't contain logic
        (new \App\Modules\Infrastructure\Actions\BootstrapServer)->execute($this->server);
    }

    public function failed(Throwable $e): void
    {
        $this->server->transitionTo(ServerStatus::Failed);
    }

    public function queue(): string
    {
        return QueueName::Infrastructure->value;
    }
}
```

**Job rules:**
- Jobs call Actions, they don't contain business logic
- Must be idempotent (safe to retry)
- Must handle failure (mark resource as failed)
- Specify queue via `QueueName` enum
- Set `$timeout` for long-running jobs

### 7. Controller

Thin. Validate → call Action → return response.

```php
// app/Http/Controllers/Api/Infrastructure/ServerController.php
class ServerController extends Controller
{
    public function store(RegisterServerRequest $request): ServerResource
    {
        $server = (new RegisterServer)->execute(
            RegisterServerData::from($request->validated())
        );

        return new ServerResource($server);
    }
}
```

**Controller rules:**
- Max one Action per method
- No business logic, no DB queries, no conditionals beyond auth
- API controllers return API Resources
- Web controllers return `Inertia::render()`

### 8. FormRequest

```php
// app/Http/Requests/Infrastructure/RegisterServerRequest.php
class RegisterServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Single-user tool, auth middleware handles it
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

### 9. API Resource

```php
// app/Http/Resources/Infrastructure/ServerResource.php
class ServerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status->value,
            // ...
            'created_at' => $this->created_at,
        ];
    }
}
```

### 10. Tests

Every feature needs:
- **Unit test** for the Action: `tests/Unit/Modules/{Module}/Actions/{Action}Test.php`
- **Feature test** for the API endpoint: `tests/Feature/Api/{Module}/{Controller}Test.php`

```php
// tests/Unit/Modules/Infrastructure/Actions/RegisterServerTest.php
class RegisterServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_server(): void
    {
        $data = new RegisterServerData(name: 'web-1', publicIp: '1.2.3.4', ...);
        $server = (new RegisterServer)->execute($data);

        $this->assertDatabaseHas('servers', ['name' => 'web-1']);
        $this->assertEquals(ServerStatus::Pending, $server->status);
    }
}
```

### 11. Factory

Every model gets a factory:

```php
// database/factories/ServerFactory.php
class ServerFactory extends Factory
{
    protected $model = Server::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word() . '-' . $this->faker->numberBetween(1, 99),
            'public_ip' => $this->faker->ipv4(),
            'status' => ServerStatus::Active,
            // ...
        ];
    }
}
```

## Route Files

| File | Purpose | Auth |
|------|---------|------|
| `routes/web.php` | Inertia pages | Session (Fortify) |
| `routes/api.php` | User-facing API | Bearer token or session |
| `routes/agent.php` | Node agent endpoints | Per-server token |
| `routes/runner.php` | CI runner endpoints | Per-runner token |
| `routes/webhooks.php` | Git provider webhooks | HMAC signature |
| `routes/settings.php` | Profile/security settings | Session |

## Key Patterns

### State Machines

Models with status fields use `HasStateMachine` trait. Transition via:

```php
$server->transitionTo(ServerStatus::Active);       // saves immediately
$server->canTransitionTo(ServerStatus::Active);     // check without saving
```

Allowed transitions defined in the model's `getAllowedTransitions()` method. Invalid transitions throw `InvalidArgumentException`.

### Encrypted Fields

Use Laravel `encrypted` cast for sensitive data:

```php
protected function casts(): array
{
    return [
        'agent_token' => 'encrypted',
        'credentials' => 'encrypted:json',
    ];
}
```

### Object Storage

Use `ObjectStorageService` for artifacts, logs, backups:

```php
$storage = new ObjectStorageService();
$storage->putArtifact("app_id/run_id/hash.tar.gz", $contents);
$storage->appendLog("runs/run_id/job_id.log", $chunk);
```

Configured via `config('helm.storage_disk')`. Falls back to local disk in dev.

### Queue Names

Always use the `QueueName` enum:

```php
use App\Support\Enums\QueueName;

dispatch(new BootstrapServer($server))->onQueue(QueueName::Infrastructure->value);
```

## Database Conventions

- **ULID** primary keys on all tables (via `HasUlid` trait)
- **No `organization_id`** anywhere — single-user tool
- **`jsonb`** for flexible config (PostgreSQL)
- **`varchar`** for status/type columns (cast to PHP enums)
- **`text` with `encrypted` cast** for secrets/tokens
- **`inet`** for IP addresses (PostgreSQL native type)
- **Soft deletes** only on: servers, projects, applications
- **No `updated_at`** on audit_logs (append-only)
- **`bigint` auto-increment** only on server_metrics (high-volume, not ULID)

## Testing

Tests use SQLite in-memory by default (phpunit.xml). The existing test suite passes with this config. For features using PostgreSQL-specific types (jsonb, inet), create a separate test that targets PostgreSQL.

Run tests: `php artisan test`
Run style check: `./vendor/bin/pint --test`
Fix style: `./vendor/bin/pint`

## Implementation Phases

| Phase | Focus | Status |
|-------|-------|--------|
| 0 | Foundation: PostgreSQL, Redis, S3, module structure, traits, config | **Done** |
| 1 | Personal access tokens, SSH keys, projects, audit logs | **Done** |
| 2 | Providers, servers, clusters, SSH bootstrap, agent API | Pending |
| 3 | Git connections, applications, environments, secrets, webhooks | Pending |
| 4 | Pipelines, pipeline runs/jobs, runners, artifacts, log streaming | Pending |
| 5 | Releases, deployments, rolling deploy, health checks, rollback | Pending |
| 6 | Domains, certificates, Caddy config, backups, dashboard polish | Pending |

See `.planning/ROADMAP.md` for granular task tracking per phase.
See `.planning/architecture.md` for full details on tables, schemas, state machines, API endpoints, and UI screens.

## Build Pipeline

To build a phase, run the master orchestrator:

```
/build-phase 1
```

This runs 6 stages sequentially via subagents:

| Stage | Skill | What it does |
|-------|-------|-------------|
| 1 | `/pipeline-plan` | Reads architecture docs, produces a file-by-file implementation plan |
| 2 | `/pipeline-backend` | Creates migrations, enums, models, actions, events, jobs, services, factories |
| 3 | `/pipeline-frontend` | Creates controllers, form requests, resources, routes, Inertia pages |
| 4 | `/pipeline-validate` | Checks all code against architecture spec, reports violations |
| 5 | `/pipeline-test` | Writes unit tests, feature tests, contract tests, runs them |
| 6 | `/pipeline-docs` | Updates ROADMAP.md checkboxes, CLAUDE.md phase status, cleans up |

Each stage skill can also be invoked standalone: `/pipeline-backend 2` runs only the backend stage for Phase 2.

Skills are defined in `.claude/skills/`.

## Don'ts

- Don't put business logic in controllers, models, jobs, or listeners
- Don't create an Action that does more than one thing
- Don't skip events — they're how modules communicate
- Don't hardcode queue names — use `QueueName` enum
- Don't use auto-incrementing IDs — use ULIDs
- Don't add organization/team scoping — this is a personal tool
- Don't add RBAC — auth check is just "is authenticated"
- Don't create files in modules that aren't needed yet
- Don't add docstrings/comments to code that is self-explanatory
