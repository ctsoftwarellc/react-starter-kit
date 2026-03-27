---
name: pipeline-validate
description: Pipeline Stage 4 — Validate that a build phase implementation matches the architecture specification. Checks schemas, patterns, state machines, and completeness.
argument-hint: "[phase-number]"
disable-model-invocation: true
allowed-tools: Read, Grep, Glob, Write, Bash
---

# Pipeline Stage: Validate Architecture

You are validating that Phase $ARGUMENTS of the Helm platform was implemented correctly against the architecture specification.

## Context

Read these files:
1. `CLAUDE.md` — coding standards and architectural rules
2. `.planning/architecture.md` — the source of truth
3. `.planning/phase-$ARGUMENTS-plan.md` — what was planned
4. `.planning/ROADMAP.md` — task checklist for this phase

## Validation Checklist

Inspect the actual code for every item below. Use Grep and Read to check real files — do not assume compliance.

### 1. Schema Compliance

For every migration created in this phase:

- [ ] Column names and types match architecture.md Section 6 exactly
- [ ] All indexes specified in the architecture are present
- [ ] All unique constraints are present
- [ ] All foreign key constraints are present
- [ ] Primary keys use `$table->ulid('id')->primary()`
- [ ] Status/type columns use `varchar` (not DB enum)
- [ ] JSON columns use `jsonb` (PostgreSQL)
- [ ] IP columns use `inet` where specified
- [ ] Encrypted fields use `text` type
- [ ] Nullable columns are correctly marked
- [ ] Default values match the spec
- [ ] Soft delete columns present where specified

Run `php artisan migrate:fresh` to verify migrations execute cleanly.

### 2. Model Compliance

For every model created in this phase:

- [ ] Uses `App\Support\Concerns\HasUlid` trait
- [ ] Has `protected $guarded = []`
- [ ] `casts()` includes ALL: enum columns, json columns, encrypted fields, datetime fields
- [ ] All relationships defined and return types correct
- [ ] Models with status fields use `App\Support\Concerns\HasStateMachine`
- [ ] `getStatusEnum()` returns the correct enum class
- [ ] `getAllowedTransitions()` map matches architecture.md Section 7 exactly — check every transition
- [ ] Soft deletes trait used where specified (servers, projects, applications)
- [ ] No business logic in the model (no create/update helpers with side effects)

### 3. Enum Compliance

- [ ] All enum cases match architecture.md entity definitions
- [ ] Enums are backed string enums (`enum X: string`)
- [ ] Located in correct module directory

### 4. Action Compliance

For every action:

- [ ] Single `execute()` method
- [ ] Multi-model mutations wrapped in `DB::transaction()`
- [ ] Events dispatched inside (or at the end of) the action
- [ ] Returns the created/modified resource (or void where appropriate)
- [ ] No controller logic, no HTTP concerns

### 5. Controller Compliance

For every controller:

- [ ] Thin — validates via FormRequest, calls ONE Action, returns Resource or Inertia response
- [ ] No business logic, no raw DB queries, no complex conditionals
- [ ] Correct HTTP status codes (201 for store, 204 for destroy, etc.)

### 6. Job Compliance

For every job:

- [ ] Calls an Action in `handle()` — does not contain business logic
- [ ] `failed()` method updates resource status appropriately
- [ ] Queue specified via `QueueName` enum
- [ ] `$tries` and `$timeout` set to reasonable values
- [ ] Implements `ShouldQueue`

### 7. Event Compliance

- [ ] Past tense naming (e.g., `ServerRegistered`, not `RegisterServer`)
- [ ] Constructor receives model instance(s)
- [ ] Registered in listener mappings

### 8. Route Compliance

- [ ] API routes match architecture.md Section 13
- [ ] Routes are in the correct file (api.php, agent.php, runner.php, webhooks.php, web.php)
- [ ] API routes use `/api/v1` prefix
- [ ] Auth middleware applied correctly
- [ ] RESTful conventions used where appropriate

### 9. State Machine Compliance

For every model with a state machine, verify EVERY transition from architecture.md Section 7:

- [ ] All valid transitions are present in `getAllowedTransitions()`
- [ ] No extra transitions that aren't in the spec
- [ ] Terminal states have no outgoing transitions (or only the ones specified)

### 10. Completeness

Cross-reference `.planning/ROADMAP.md` tasks for Phase $ARGUMENTS:

- [ ] Every task has corresponding code
- [ ] Factories exist for every new model
- [ ] No placeholder/TODO code left in production files

### 11. Security

- [ ] Encrypted fields (tokens, credentials, secrets) are never exposed in API Resources
- [ ] Secret values only returned via explicit reveal endpoints
- [ ] Agent/runner tokens are hashed or encrypted in DB

## Output

Write a validation report to `.planning/phase-$ARGUMENTS-validation.md`:

```markdown
# Phase $ARGUMENTS Validation Report

## Summary
- Total checks: X
- Passed: Y
- Failed: Z

## Results

### Schema Compliance: PASS/FAIL
- [details of any failures with file:line references]

### Model Compliance: PASS/FAIL
- [details]

[... etc for each category]

## Required Fixes
1. [file:line] — description of what's wrong and how to fix it
2. ...

## Notes
- [any observations or suggestions that aren't failures]
```

Be specific. Include file paths and line numbers for every failure. Suggest the exact fix.
