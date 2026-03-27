---
name: pipeline-plan
description: Pipeline Stage 1 — Plan the implementation for a build phase. Reads architecture docs and produces a concrete file-by-file implementation plan.
argument-hint: "[phase-number]"
disable-model-invocation: true
allowed-tools: Read, Grep, Glob, Write, Bash
---

# Pipeline Stage: Plan

You are planning the implementation of Phase $ARGUMENTS of the Helm platform.

## Context Gathering

Read these files in full before planning:

1. `CLAUDE.md` — coding patterns, conventions, module structure
2. `.planning/ROADMAP.md` — task checklist for Phase $ARGUMENTS
3. `.planning/architecture.md` — domain model (Section 5), database schema (Section 6), state machines (Section 7), API design (Section 13), UI screens (Section 14)

Also read the current codebase to understand what already exists:
- Check `database/migrations/` for existing tables
- Check `app/Modules/` for existing models, actions, enums
- Check `routes/` for existing routes
- Check `resources/js/pages/` for existing UI pages

## Output

Produce a concrete implementation plan at `.planning/phase-$ARGUMENTS-plan.md` with these sections:

### 1. Summary
One paragraph: what this phase delivers and why it matters for the platform.

### 2. Dependencies
What must already exist for this phase to work? Verify these exist in the codebase. Flag any missing prerequisites.

### 3. Migration Plan
For each migration file to create, specify:
- File name (with timestamp prefix)
- Table name
- Every column: name, type, nullable, default, constraints
- Indexes (including composite indexes)
- Unique constraints
- Foreign keys
- Pull exact definitions from architecture.md Section 6

### 4. Enum Plan
For each enum:
- Full class path
- Every case with string value
- Pull from architecture.md entity definitions and state machines

### 5. Model Plan
For each model:
- Full class path
- Traits to use (HasUlid, HasStateMachine, SoftDeletes, etc.)
- `$guarded = []`
- `casts()` array with every cast
- Every relationship method with return type
- State machine config (if applicable): `getStatusEnum()` return, full `getAllowedTransitions()` map pulled from architecture.md Section 7
- Scopes if needed

### 6. Action Plan
For each action:
- Full class path
- `execute()` signature (parameter types → return type)
- What it does step by step
- Whether it needs a DB transaction
- Which events it dispatches
- Which jobs it queues (if any)

### 7. Event Plan
For each event:
- Full class path
- Constructor parameters
- Which listeners should handle it

### 8. Job Plan
For each job:
- Full class path
- Queue name (from QueueName enum)
- `$tries`, `$timeout` values
- What action it calls
- Failure handling (what status to set on what model)

### 9. Service Plan
For each service:
- Full class path
- Public methods with signatures
- External dependencies (SSH, HTTP client, etc.)
- What needs to be mocked in tests

### 10. Controller + Route Plan
For each controller:
- Full class path
- Methods with HTTP verb + route path
- Which FormRequest validates input
- Which Action is called
- Which Resource formats output
- Which route file it belongs in

### 11. Frontend Plan
For each page/component:
- File path under `resources/js/`
- Props it receives from Inertia
- Key UI elements
- Links to other pages

### 12. Test Plan
- List of unit test files with test method names
- List of feature test files with test method names
- What factories are needed

### 13. File Creation Order
Ordered list of every file to create, respecting dependencies. Migrations first, then enums, models, etc.

## Rules

- Be extremely specific — copy exact column definitions and transition maps from architecture.md
- Do NOT write any application code in this plan — only the specification
- Flag any conflicts between architecture.md and ROADMAP.md tasks
- Flag any decisions that need to be made before implementation
