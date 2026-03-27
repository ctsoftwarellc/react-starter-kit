---
name: build-phase
description: Master orchestrator that builds an implementation phase through a full pipeline. Runs plan → backend → frontend → validate → test → docs stages sequentially using subagents.
argument-hint: "[phase-number]"
disable-model-invocation: true
allowed-tools: Agent, Read, Bash, Grep, Glob, Edit, Write
---

# Build Phase Orchestrator

You are the master orchestrator for building implementation phases of the Helm platform. You drive a phase through a 6-stage pipeline, invoking a dedicated subagent for each stage.

## Input

Phase number: **$ARGUMENTS**

## Setup

1. Read `.planning/ROADMAP.md` to get the task list for this phase.
2. Read `.planning/architecture.md` sections relevant to this phase (domain model, database schema, state machines, API endpoints).
3. Read `CLAUDE.md` for coding patterns and conventions.
4. Summarize what this phase needs to deliver — list the tables, models, enums, actions, events, jobs, services, controllers, tests, and UI pages.

## Pipeline Stages

Execute each stage sequentially. Each stage is a subagent that receives the full context of what to build. Wait for each stage to complete before starting the next. If a stage fails or produces issues, fix them before proceeding.

### Stage 1: Plan (`/pipeline-plan`)

Launch a **Plan** subagent with the following task:

> You are planning the implementation of Phase $ARGUMENTS of the Helm platform.
>
> Read these files for context:
> - `CLAUDE.md` (coding patterns and conventions)
> - `.planning/ROADMAP.md` (task checklist for this phase)
> - `.planning/architecture.md` (domain model, database schema, state machines, API surface)
>
> Produce a concrete implementation plan:
> 1. List every file that needs to be created or modified, in dependency order
> 2. For each migration, specify the exact columns, types, constraints, and indexes from the architecture doc
> 3. For each model, specify relationships, casts, and state machine transitions
> 4. For each action, specify inputs, outputs, and side effects (events dispatched, jobs queued)
> 5. Identify any cross-module dependencies or ordering constraints
> 6. Flag any ambiguities or decisions needed before implementation
>
> Write the plan to `.planning/phase-$ARGUMENTS-plan.md`
> Do NOT write any application code — only the plan document.

### Stage 2: Backend (`/pipeline-backend`)

Launch a **general-purpose** subagent with the following task:

> You are implementing the backend for Phase $ARGUMENTS of the Helm platform.
>
> Read these files for full context:
> - `CLAUDE.md` (patterns: Action pattern, model rules, job rules, state machines, queue names)
> - `.planning/phase-$ARGUMENTS-plan.md` (implementation plan from Stage 1)
> - `.planning/architecture.md` (reference for schemas, state machines, entity details)
>
> Implement in this exact order:
> 1. **Migrations** — Create all migration files with exact schema from the plan
> 2. **Enums** — Create all PHP backed string enums in `app/Modules/{Module}/Enums/`
> 3. **Models** — Create models in `app/Modules/{Module}/Models/` with HasUlid, casts, relationships, state machine transitions. Use `protected $guarded = []`
> 4. **DTOs** — Create data transfer objects in `app/Modules/{Module}/DTOs/` if the plan calls for them
> 5. **Events** — Create domain events in `app/Modules/{Module}/Events/` (past tense names, carry model instance)
> 6. **Actions** — Create actions in `app/Modules/{Module}/Actions/` with one `execute()` method, DB transactions, event dispatch
> 7. **Jobs** — Create queued jobs in `app/Modules/{Module}/Jobs/` that call actions, specify queue via QueueName enum, handle failure
> 8. **Services** — Create services in `app/Modules/{Module}/Services/` for infrastructure concerns (SSH, API clients, config generators)
> 9. **Listeners** — Create/update listeners (especially RecordAuditLog) for new events
> 10. **Factories** — Create model factories in `database/factories/`
>
> Rules:
> - Follow CLAUDE.md patterns exactly — business logic in Actions only
> - Use `App\Support\Concerns\HasUlid` on all models
> - Use `App\Support\Concerns\HasStateMachine` on models with status fields
> - Use `App\Support\Enums\QueueName` for job queue assignment
> - Cast enum columns, JSON columns, encrypted fields, and dates
> - Run `php artisan migrate` after creating migrations to verify they work
> - Run `./vendor/bin/pint` after all code is written
>
> Do NOT create controllers, form requests, API resources, routes, or frontend code. Backend domain layer only.

### Stage 3: Frontend (`/pipeline-frontend`)

Launch a **general-purpose** subagent with the following task:

> You are implementing the HTTP layer and frontend for Phase $ARGUMENTS of the Helm platform.
>
> Read these files for full context:
> - `CLAUDE.md` (controller rules, route files, request/resource patterns)
> - `.planning/phase-$ARGUMENTS-plan.md` (implementation plan)
> - `.planning/architecture.md` (API endpoints section, UI section)
>
> The backend domain layer (models, actions, events, jobs) is already built. Now build the HTTP + UI layer:
>
> 1. **Form Requests** — Create in `app/Http/Requests/{Module}/` with validation rules
> 2. **API Resources** — Create in `app/Http/Resources/{Module}/` with `toArray()` returning relevant fields
> 3. **Controllers** — Create in `app/Http/Controllers/Api/{Module}/`. Thin controllers: validate via FormRequest, call Action, return Resource. Max one Action per method.
> 4. **Web Controllers** — Create in `app/Http/Controllers/Web/` for Inertia pages if the plan calls for them
> 5. **Routes** — Add routes to the appropriate route file (`routes/api.php`, `routes/agent.php`, `routes/runner.php`, `routes/webhooks.php`, or `routes/web.php`). Use RESTful resource routes where possible.
> 6. **Inertia Pages** — Create React/TypeScript pages in `resources/js/pages/` matching the UI screens from the architecture doc. Use existing layout and component patterns from the codebase.
> 7. **TypeScript Types** — Add type definitions in `resources/js/types/` for new models
>
> Rules:
> - Controllers are thin — validate, call action, return response
> - API routes go under `/api/v1` prefix with auth middleware
> - Agent/runner routes use their dedicated route files and auth middleware
> - Webhook routes are unauthenticated but signature-verified
> - Web routes use Inertia `render()` and pass data as props
> - Run `./vendor/bin/pint` after all PHP code is written
> - Check that `php artisan route:list` shows no errors after adding routes

### Stage 4: Validate Architecture (`/pipeline-validate`)

Launch an **Explore** subagent with the following task:

> You are validating that Phase $ARGUMENTS of the Helm platform was implemented correctly against the architecture specification.
>
> Read:
> - `CLAUDE.md` (coding standards and architectural rules)
> - `.planning/architecture.md` (the source of truth for schemas, state machines, relationships, API contracts)
> - `.planning/phase-$ARGUMENTS-plan.md` (what was planned)
> - `.planning/ROADMAP.md` (task checklist for this phase)
>
> Validate each of the following. Report violations as a structured list:
>
> **Schema compliance:**
> - Do migrations match the column definitions in architecture.md Section 6?
> - Are all indexes, unique constraints, and foreign keys present?
> - Are ULID primary keys used on all tables?
> - Are status columns varchar (not DB enums)?
>
> **Model compliance:**
> - Do models use `HasUlid` trait?
> - Do models with status fields use `HasStateMachine` trait?
> - Are all casts defined (enums, json, encrypted, datetime)?
> - Do relationships match architecture.md Section 5?
> - Is `$guarded = []` set?
>
> **Pattern compliance:**
> - Is business logic only in Actions (not controllers, models, jobs, listeners)?
> - Do controllers only validate + call action + return response?
> - Do jobs only call actions and handle failure?
> - Do events use past tense names?
> - Are queues specified via QueueName enum?
>
> **State machine compliance:**
> - Do state machine transitions match architecture.md Section 7?
> - Are all valid transitions defined?
> - Are invalid transitions properly prevented?
>
> **API compliance:**
> - Do routes match the API design in architecture.md Section 13?
> - Are routes in the correct route file?
>
> **Completeness:**
> - Cross-reference every task in ROADMAP.md for this phase — is each one implemented?
> - Are factories created for every new model?
>
> Write a validation report to `.planning/phase-$ARGUMENTS-validation.md` with:
> - PASS/FAIL per category
> - Specific violations with file paths and line numbers
> - Suggested fixes for any failures

After this subagent completes, read the validation report. If there are FAILures, fix them directly before proceeding to the test stage.

### Stage 5: Test (`/pipeline-test`)

Launch a **general-purpose** subagent with the following task:

> You are writing tests for Phase $ARGUMENTS of the Helm platform.
>
> Read:
> - `CLAUDE.md` (testing expectations)
> - `.planning/phase-$ARGUMENTS-plan.md` (what was built)
> - `.planning/architecture.md` (state machines, API contracts for this phase)
>
> Write tests following this structure:
>
> **Unit tests** (`tests/Unit/Modules/{Module}/`):
> - Test every Action's `execute()` method — valid inputs produce correct DB state and events
> - Test every state machine — all valid transitions succeed, all invalid transitions throw InvalidArgumentException
> - Test enums have expected cases
>
> **Feature tests** (`tests/Feature/Api/{Module}/`):
> - Test every API endpoint: happy path with valid data → correct response shape and status code
> - Test validation: invalid data → 422 with error messages
> - Test auth: unauthenticated request → 401
> - Test not found: invalid ID → 404
>
> **Contract tests** (`tests/Contract/`) if agent or runner APIs were added:
> - Test request/response shapes for agent endpoints
> - Test request/response shapes for runner endpoints
>
> Rules:
> - Use `RefreshDatabase` trait
> - Use factories to create test data
> - Use `actingAs(User::factory()->create())` for authenticated requests
> - Test state machine transitions exhaustively — every valid transition AND every invalid transition
> - Mock external services (SSH, cloud provider APIs) at the service boundary
> - Run `php artisan test` after writing all tests — fix any failures
> - Run `./vendor/bin/pint` to fix style

After this subagent completes, run `php artisan test` yourself to verify. Fix any remaining failures.

### Stage 6: Update Docs (`/pipeline-docs`)

Launch a **general-purpose** subagent with the following task:

> You are updating documentation after completing Phase $ARGUMENTS of the Helm platform.
>
> Read:
> - `.planning/ROADMAP.md`
> - `CLAUDE.md`
>
> Do the following:
>
> 1. **Update ROADMAP.md**: Check off every completed task for Phase $ARGUMENTS. Mark the phase status as `COMPLETE`.
> 2. **Update CLAUDE.md**: Update the phase status table to mark Phase $ARGUMENTS as **Done**.
> 3. **Clean up**: Delete `.planning/phase-$ARGUMENTS-plan.md` and `.planning/phase-$ARGUMENTS-validation.md` (implementation artifacts, no longer needed).
>
> Do NOT modify architecture.md.

## Completion

After all 6 stages complete:
1. Run `php artisan test` one final time
2. Run `./vendor/bin/pint --test`
3. Run `php artisan route:list` to verify no route errors
4. Summarize what was built: tables, models, endpoints, pages, test count
5. State what the next phase is and what it will deliver
