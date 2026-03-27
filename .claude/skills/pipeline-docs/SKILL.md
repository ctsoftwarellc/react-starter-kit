---
name: pipeline-docs
description: Pipeline Stage 6 — Update project documentation after completing a build phase. Marks tasks complete in ROADMAP.md, updates phase status in CLAUDE.md, cleans up planning artifacts.
argument-hint: "[phase-number]"
disable-model-invocation: true
allowed-tools: Read, Write, Edit, Bash, Grep, Glob
---

# Pipeline Stage: Update Docs

You are updating project documentation after completing Phase $ARGUMENTS of the Helm platform.

## Tasks

### 1. Update ROADMAP.md

Read `.planning/ROADMAP.md` and find the section for Phase $ARGUMENTS.

For every task in this phase:
- Read the actual codebase to verify the task was completed
- If the file/feature exists: change `- [ ]` to `- [x]`
- If the file/feature does NOT exist: leave unchecked and add a note

Update the phase status line from `**Status: NOT STARTED**` to `**Status: COMPLETE**`.

If any tasks are left unchecked, instead use `**Status: PARTIAL**` and note what's missing.

### 2. Update CLAUDE.md

Read `CLAUDE.md` and find the Implementation Phases table.

Update Phase $ARGUMENTS status from `Pending` to `**Done**`.

### 3. Verify Phase Deliverables

Run these commands to quantify what was built:

```bash
# Count new migrations
ls -la database/migrations/ | grep -c "$(date +%Y)"

# Count new models
find app/Modules -name "*.php" -path "*/Models/*" | wc -l

# Count new actions
find app/Modules -name "*.php" -path "*/Actions/*" | wc -l

# Count new tests
find tests -name "*Test.php" -newer CLAUDE.md | wc -l

# Count API routes
php artisan route:list --compact 2>/dev/null | wc -l

# Run test suite for final count
php artisan test 2>&1 | tail -3
```

### 4. Clean Up Planning Artifacts

Delete the temporary planning files for this phase (they served their purpose — the code is the source of truth now):

```bash
rm -f .planning/phase-$ARGUMENTS-plan.md
rm -f .planning/phase-$ARGUMENTS-validation.md
```

### 5. Write Phase Summary

Add a brief completion note at the end of the Phase $ARGUMENTS section in ROADMAP.md:

```markdown
**Completed:** YYYY-MM-DD
**Deliverables:** X migrations, Y models, Z actions, W tests, N API endpoints
```

## Rules

- Do NOT modify `.planning/architecture.md` — it's the reference spec, not a living doc
- Do NOT add new tasks to ROADMAP.md — that happens during the next phase's planning stage
- Do NOT modify any application code — this stage is documentation only
- Be accurate — only check off tasks that are genuinely complete
