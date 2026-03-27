---
name: pipeline-backend
description: Pipeline Stage 2 — Implement the backend domain layer for a build phase. Creates migrations, enums, models, actions, events, jobs, services, and factories.
argument-hint: "[phase-number]"
disable-model-invocation: true
allowed-tools: Read, Write, Edit, Bash, Grep, Glob
---

# Pipeline Stage: Backend

You are implementing the backend domain layer for Phase $ARGUMENTS of the Helm platform.

## Context

Read these files before writing any code:
1. `CLAUDE.md` — patterns and conventions (Action pattern, model rules, job rules, state machines, queue names)
2. `.planning/phase-$ARGUMENTS-plan.md` — the implementation plan from the Plan stage (your spec)
3. `.planning/architecture.md` — reference for any details not in the plan

## Implementation Order

Follow the plan document and implement in this exact sequence. Do NOT skip ahead.

### Step 1: Migrations

Create all migration files from the plan's Migration Plan section. For each table:
- Use `$table->ulid('id')->primary()` for primary keys
- Use `$table->ulid('column_name')` for foreign keys to ULID tables
- Use `$table->foreign('column_name')->references('id')->on('table')` for FK constraints
- Use `varchar` for status/type columns (not DB-level enums)
- Use `jsonb` for flexible JSON fields (PostgreSQL)
- Use `text` for encrypted fields (encryption happens in PHP, not DB)
- Use `inet` for IP address columns (PostgreSQL native)
- Add all indexes from the plan

After creating ALL migrations, run:
```
php artisan migrate
```
Fix any errors before proceeding.

### Step 2: Enums

Create PHP backed string enums from the plan's Enum Plan section:
```php
namespace App\Modules\{Module}\Enums;

enum ExampleStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
}
```

### Step 3: Models

Create Eloquent models from the plan's Model Plan section. Every model MUST:
- Use `App\Support\Concerns\HasUlid` trait
- Set `protected $guarded = []`
- Define `casts()` with all enum, json, encrypted, and datetime casts
- Define all relationships from the plan
- If it has a status field: use `App\Support\Concerns\HasStateMachine` and implement `getStatusEnum()` + `getAllowedTransitions()`

```php
namespace App\Modules\{Module}\Models;

use App\Support\Concerns\HasUlid;
use App\Support\Concerns\HasStateMachine;
use Illuminate\Database\Eloquent\Model;

class Example extends Model
{
    use HasUlid, HasStateMachine;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ExampleStatus::class,
            'config' => 'json',
            'secret' => 'encrypted',
            'started_at' => 'datetime',
        ];
    }

    // Relationships...

    protected function getStatusEnum(): string
    {
        return ExampleStatus::class;
    }

    protected function getAllowedTransitions(): array
    {
        return [
            'pending' => [ExampleStatus::Active, ExampleStatus::Failed],
            // ... full map from plan
        ];
    }
}
```

### Step 4: DTOs

Create data transfer objects if the plan specifies them:
```php
namespace App\Modules\{Module}\DTOs;

readonly class CreateExampleData
{
    public function __construct(
        public string $name,
        public string $publicIp,
        // ...
    ) {}

    public static function from(array $validated): static
    {
        return new static(...$validated);
    }
}
```

### Step 5: Events

Create domain events. Past tense names. Carry the model instance:
```php
namespace App\Modules\{Module}\Events;

class ExampleCreated
{
    public function __construct(public Example $example) {}
}
```

### Step 6: Actions

Create actions with one `execute()` method. This is where ALL business logic lives:
```php
namespace App\Modules\{Module}\Actions;

use Illuminate\Support\Facades\DB;

class CreateExample
{
    public function execute(CreateExampleData $data): Example
    {
        return DB::transaction(function () use ($data) {
            $example = Example::create([...]);
            event(new ExampleCreated($example));
            return $example;
        });
    }
}
```

Rules:
- Wrap multi-model mutations in `DB::transaction()`
- Dispatch events after the mutation, inside the transaction
- Return the created/modified resource
- Do NOT call other actions from an action (if you need composition, use a service)

### Step 7: Jobs

Create queued jobs that call actions:
```php
namespace App\Modules\{Module}\Jobs;

use App\Support\Enums\QueueName;

class ProcessExample implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(public Example $example) {}

    public function handle(): void
    {
        (new DoSomething)->execute($this->example);
    }

    public function failed(Throwable $e): void
    {
        $this->example->transitionTo(ExampleStatus::Failed);
    }

    public function queue(): string
    {
        return QueueName::Infrastructure->value;
    }
}
```

### Step 8: Services

Create services for infrastructure concerns (SSH, API clients, config generators). Services are stateless. They do NOT dispatch events or mutate models directly — that's the Action's job.

### Step 9: Listeners

Create or update event listeners. The `RecordAuditLog` listener should be updated to handle new events. Register listeners in `EventServiceProvider` or via `#[Listener]` attributes.

### Step 10: Factories

Create a factory for every new model:
```php
namespace Database\Factories;

use App\Modules\{Module}\Models\Example;

class ExampleFactory extends Factory
{
    protected $model = Example::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'status' => ExampleStatus::Active,
            // ... realistic defaults
        ];
    }
}
```

## Final Checks

After all code is written:
1. Run `php artisan migrate:fresh` to verify migrations work from scratch
2. Run `./vendor/bin/pint` to fix code style
3. Verify no PHP syntax errors: `php artisan route:list` (should not crash)
4. Verify models can be instantiated: `php artisan tinker` → `new App\Modules\...\Models\Example`

## Rules

- ONLY create backend domain layer code — NO controllers, routes, requests, resources, or frontend
- Follow the plan document exactly — do not improvise additional features
- If the plan is ambiguous, check architecture.md for clarification
- If something genuinely can't be built as planned, note it but do your best
