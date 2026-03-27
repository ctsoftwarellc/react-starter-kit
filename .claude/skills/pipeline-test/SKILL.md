---
name: pipeline-test
description: Pipeline Stage 5 — Write and run tests for a build phase. Creates unit tests for actions and state machines, feature tests for API endpoints, and contract tests for agent/runner APIs.
argument-hint: "[phase-number]"
disable-model-invocation: true
allowed-tools: Read, Write, Edit, Bash, Grep, Glob
---

# Pipeline Stage: Test

You are writing and running tests for Phase $ARGUMENTS of the Helm platform.

## Context

Read these files:
1. `CLAUDE.md` — testing expectations
2. `.planning/phase-$ARGUMENTS-plan.md` — Test Plan section (lists test files and methods)
3. `.planning/architecture.md` — state machine transitions (Section 7), API contracts (Section 13)

Explore what was built:
- `app/Modules/` — find all new models, actions, enums, state machines for this phase
- `app/Http/Controllers/` — find all new controllers
- `routes/` — find all new routes
- `database/factories/` — find available factories

## Test Structure

### Unit Tests: Actions

Location: `tests/Unit/Modules/{Module}/Actions/`

For every Action created in this phase, test:

```php
namespace Tests\Unit\Modules\Infrastructure\Actions;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegisterServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_server_with_pending_status(): void
    {
        $data = new RegisterServerData(
            name: 'web-1',
            publicIp: '1.2.3.4',
            // ...
        );

        $server = (new RegisterServer)->execute($data);

        $this->assertDatabaseHas('servers', [
            'id' => $server->id,
            'name' => 'web-1',
            'public_ip' => '1.2.3.4',
        ]);
        $this->assertEquals(ServerStatus::Pending, $server->status);
    }

    public function test_it_dispatches_server_registered_event(): void
    {
        Event::fake([ServerRegistered::class]);

        $server = (new RegisterServer)->execute($data);

        Event::assertDispatched(ServerRegistered::class, function ($event) use ($server) {
            return $event->server->id === $server->id;
        });
    }
}
```

Test patterns for Actions:
- Valid input → correct DB state
- Valid input → correct return value
- Events dispatched with correct payload
- Edge cases (duplicate names, missing optional fields, etc.)
- If action uses transaction, test that partial failures don't leave dirty state

### Unit Tests: State Machines

Location: `tests/Unit/Modules/{Module}/StateMachines/`

For every model with `HasStateMachine`, test ALL transitions from architecture.md Section 7:

```php
class ServerStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_can_transition_to_provisioning(): void
    {
        $server = Server::factory()->create(['status' => ServerStatus::Pending]);

        $server->transitionTo(ServerStatus::Provisioning);

        $this->assertEquals(ServerStatus::Provisioning, $server->fresh()->status);
    }

    public function test_pending_cannot_transition_to_active(): void
    {
        $server = Server::factory()->create(['status' => ServerStatus::Pending]);

        $this->expectException(InvalidArgumentException::class);

        $server->transitionTo(ServerStatus::Active);
    }

    public function test_active_can_transition_to_draining(): void { ... }
    public function test_active_can_transition_to_cordoned(): void { ... }
    public function test_active_can_transition_to_maintenance(): void { ... }
    public function test_active_can_transition_to_decommissioning(): void { ... }
    public function test_active_cannot_transition_to_pending(): void { ... }
    public function test_decommissioned_cannot_transition_anywhere(): void { ... }

    // ... test EVERY valid and invalid transition from architecture.md Section 7
}
```

This is critical — test every single transition path, including invalid ones.

### Feature Tests: API Endpoints

Location: `tests/Feature/Api/{Module}/`

For every API endpoint, test:

```php
class ServerControllerTest extends TestCase
{
    use RefreshDatabase;

    // Happy path
    public function test_index_returns_paginated_servers(): void
    {
        $user = User::factory()->create();
        Server::factory()->count(3)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/servers');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'status', 'public_ip', 'created_at']],
                'meta' => ['current_page', 'total'],
            ]);
    }

    public function test_store_creates_server(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/servers', [
                'name' => 'web-1',
                'public_ip' => '1.2.3.4',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'web-1');

        $this->assertDatabaseHas('servers', ['name' => 'web-1']);
    }

    // Validation
    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/servers', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'public_ip']);
    }

    // Auth
    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/servers');

        $response->assertUnauthorized();
    }

    // Not found
    public function test_show_returns_404_for_invalid_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/servers/nonexistent');

        $response->assertNotFound();
    }

    // State transitions via API
    public function test_bootstrap_transitions_server_status(): void { ... }
    public function test_drain_transitions_server_status(): void { ... }
}
```

### Contract Tests: Agent/Runner APIs

Location: `tests/Contract/`

Only if this phase added agent or runner endpoints:

```php
class AgentApiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_heartbeat_accepts_valid_payload(): void
    {
        $server = Server::factory()->create([
            'agent_token' => 'test-token',
            'status' => ServerStatus::Active,
        ]);

        $response = $this->postJson('/api/agent/heartbeat', [
            'server_id' => $server->id,
            'cpu_percent' => 45.2,
            'memory_percent' => 67.8,
            'disk_percent' => 32.1,
            'load_avg' => [1.2, 0.8, 0.5],
            'uptime_seconds' => 86400,
            'agent_version' => '0.1.0',
        ], [
            'Authorization' => 'Bearer test-token',
        ]);

        $response->assertOk();
    }

    public function test_heartbeat_rejects_invalid_token(): void
    {
        $response = $this->postJson('/api/agent/heartbeat', [...], [
            'Authorization' => 'Bearer invalid',
        ]);

        $response->assertUnauthorized();
    }
}
```

## Execution

After writing ALL tests:

1. Run the full test suite:
```bash
php artisan test
```

2. If any tests fail, fix the issue. Common problems:
   - Missing factory definitions
   - Missing route model binding
   - Incorrect response structure
   - State machine transition not matching test expectation

3. Fix code style:
```bash
./vendor/bin/pint
```

4. Run tests again to confirm everything passes.

5. Report the final count: X tests, Y assertions, all passing.

## Rules

- Use `RefreshDatabase` trait on all tests that touch the DB
- Use `Event::fake()` to assert events without side effects
- Use `Queue::fake()` to assert jobs without executing them
- Use factories to create test data — never create models with raw `::create()`
- Mock external services (SSH, HTTP clients) at the service boundary — never call real external services
- Test EVERY state machine transition — both valid and invalid
- Test EVERY API endpoint — happy path, validation, auth, not-found
- Do NOT modify production code to make tests pass — if a test reveals a bug, fix the bug in the production code
