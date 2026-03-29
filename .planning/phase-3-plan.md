# Phase 3: Applications, Environments, Secrets - Implementation Plan

## 1. Summary

Phase 3 adds the App Platform layer that turns projects and infrastructure into deployable application definitions: git connections, applications, environments, environment variables, secrets, process definitions, and webhook registration. This phase matters because it creates the first complete app configuration workflow needed before pipelines, releases, and deployments can exist.

## 2. Dependencies

Verified existing prerequisites in the codebase:

- `app/Modules/AppPlatform/Models/Project.php` exists and Phase 1 project CRUD is complete.
- `app/Modules/Infrastructure/Models/Cluster.php` exists and Phase 2 cluster CRUD is complete.
- `routes/api.php`, `routes/web.php`, `routes/settings.php`, and `routes/webhooks.php` already exist.
- `app/Modules/Operations/Listeners/RecordAuditLog.php` and `app/Providers/AppServiceProvider.php` already register domain-event audit logging.
- `App\Support\Enums\QueueName` already includes `Pipeline`, which future webhook ingestion depends on.
- Existing UI patterns for project list/detail and settings pages already exist under `resources/js/pages/`.

Missing or cross-phase prerequisites that must be handled explicitly:

- Architecture includes `environments.active_release_id`, but Phase 5 roadmap separately says that column is added later. Recommendation: do not add `active_release_id` in Phase 3; add it in Phase 5 as the roadmap already states.
- Architecture Phase 3 deliverables do not include `database_instances`, `cache_instances`, or `service_bindings`; architecture marks those tables post-MVP. Recommendation: exclude roadmap section 3.7 from implementation until architecture and roadmap are reconciled.
- Webhook ingestion in architecture dispatches `ProcessWebhook`, but that job belongs to Phase 4. Recommendation: Phase 3 should implement webhook registration, signature verification, and a safe placeholder response path, while deferring full ingestion processing to Phase 4 unless the roadmap is updated.
- OAuth callback routing is required for GitHub auth but is not defined in Section 13. Recommendation: add a web route for the callback and flag the architecture omission.

## 3. Migration Plan

### `database/migrations/2026_04_03_000001_create_git_connections_table.php`

- Table: `git_connections`
- Columns:
    - `id`: `ulid`, primary key
    - `provider`: `varchar(20)`, not null
    - `access_token`: `text`, not null, encrypted cast in model
    - `refresh_token`: `text`, nullable, encrypted cast in model
    - `token_expires_at`: `timestamp`, nullable
    - `account_name`: `varchar(255)`, not null
    - `created_at`: `timestamp`
    - `updated_at`: `timestamp`
- Indexes:
    - none explicitly defined by architecture
- Unique constraints:
    - none explicitly defined by architecture
- Foreign keys:
    - none
- Notes:
    - Introduce a PHP enum for provider values instead of a DB enum.

### `database/migrations/2026_04_03_000002_create_applications_table.php`

- Table: `applications`
- Columns:
    - `id`: `ulid`, primary key
    - `project_id`: `ulid`, not null, foreign key
    - `name`: `varchar(255)`, not null
    - `slug`: `varchar(255)`, not null
    - `runtime`: `varchar(20)`, not null, default `'php'`
    - `repository_url`: `varchar(500)`, nullable
    - `repository_branch`: `varchar(255)`, default `'main'`
    - `git_connection_id`: `ulid`, nullable, foreign key
    - `settings`: `jsonb`, default `'{}'`
    - `created_at`: `timestamp`
    - `updated_at`: `timestamp`
    - `deleted_at`: `timestamp`, nullable
- Indexes:
    - index on `project_id`
    - index on `git_connection_id`
- Unique constraints:
    - none defined in architecture
- Foreign keys:
    - `project_id` -> `projects.id`
    - `git_connection_id` -> `git_connections.id`
- Decisions to flag:
    - architecture gives `slug` but no uniqueness rule; route binding should use ULID unless the spec is updated
    - recommended FK behavior: `project_id` constrained, `git_connection_id` nullable with `nullOnDelete()`

### `database/migrations/2026_04_03_000003_create_environments_table.php`

- Table: `environments`
- Columns:
    - `id`: `ulid`, primary key
    - `application_id`: `ulid`, not null, foreign key
    - `cluster_id`: `ulid`, not null, foreign key
    - `name`: `varchar(100)`, not null
    - `type`: `varchar(20)`, not null
    - `is_auto_deploy`: `boolean`, default `false`
    - `branch`: `varchar(255)`, nullable
    - `created_at`: `timestamp`
    - `updated_at`: `timestamp`
- Indexes:
    - index on `application_id`
    - index on `cluster_id`
- Unique constraints:
    - unique(`application_id`, `name`)
- Foreign keys:
    - `application_id` -> `applications.id`
    - `cluster_id` -> `clusters.id`
- Explicit exclusion:
    - do not include `active_release_id` in this migration because Phase 5 roadmap already schedules it separately

### `database/migrations/2026_04_03_000004_create_environment_variables_table.php`

- Table: `environment_variables`
- Columns:
    - `id`: `ulid`, primary key
    - `environment_id`: `ulid`, not null, foreign key
    - `key`: `varchar(255)`, not null
    - `value`: `text`, not null
    - `is_build_arg`: `boolean`, default `false`
    - `created_at`: `timestamp`
    - `updated_at`: `timestamp`
- Indexes:
    - index on `environment_id`
- Unique constraints:
    - unique(`environment_id`, `key`)
- Foreign keys:
    - `environment_id` -> `environments.id`

### `database/migrations/2026_04_03_000005_create_secrets_table.php`

- Table: `secrets`
- Columns:
    - `id`: `ulid`, primary key
    - `environment_id`: `ulid`, not null, foreign key
    - `key`: `varchar(255)`, not null
    - `encrypted_value`: `text`, not null
    - `version`: `integer`, default `1`
    - `created_at`: `timestamp`
    - `updated_at`: `timestamp`
- Indexes:
    - index on `environment_id`
- Unique constraints:
    - unique(`environment_id`, `key`)
- Foreign keys:
    - `environment_id` -> `environments.id`

### `database/migrations/2026_04_03_000006_create_process_definitions_table.php`

- Table: `process_definitions`
- Columns:
    - `id`: `ulid`, primary key
    - `environment_id`: `ulid`, not null, foreign key
    - `type`: `varchar(20)`, not null
    - `command`: `text`, not null
    - `instances`: `integer`, default `1`
    - `created_at`: `timestamp`
    - `updated_at`: `timestamp`
- Indexes:
    - index on `environment_id`
- Unique constraints:
    - none defined in architecture
- Foreign keys:
    - `environment_id` -> `environments.id`

### `database/migrations/2026_04_03_000007_create_webhooks_table.php`

- Table: `webhooks`
- Columns:
    - `id`: `ulid`, primary key
    - `application_id`: `ulid`, not null, foreign key
    - `provider`: `varchar(20)`, not null
    - `secret`: `text`, not null, encrypted cast in model
    - `is_active`: `boolean`, default `true`
    - `created_at`: `timestamp`
    - `updated_at`: `timestamp`
- Indexes:
    - index on `application_id`
- Unique constraints:
    - unique(`application_id`, `provider`) because each application has one secret per provider
- Foreign keys:
    - `application_id` -> `applications.id`
- Module note:
    - the table is part of Phase 3 scope, but the model/action/controller should live under `Pipeline` to match architecture ownership

## 4. Enum Plan

### `App\Modules\AppPlatform\Enums\GitProvider`

- `Github = 'github'`
- `Gitlab = 'gitlab'`
- `Bitbucket = 'bitbucket'`

### `App\Modules\AppPlatform\Enums\Runtime`

- `Php = 'php'`
- `Node = 'node'`
- `Python = 'python'`
- `Go = 'go'`

### `App\Modules\AppPlatform\Enums\EnvironmentType`

- `Production = 'production'`
- `Staging = 'staging'`
- `Preview = 'preview'`

### `App\Modules\AppPlatform\Enums\ProcessType`

- `Web = 'web'`
- `Worker = 'worker'`
- `Scheduler = 'scheduler'`
- `Custom = 'custom'`

## 5. Model Plan

### `App\Modules\AppPlatform\Models\GitConnection`

- Traits: `HasFactory`, `HasUlid`
- `$guarded = []`
- Casts:
    - `provider` => `GitProvider::class`
    - `access_token` => `'encrypted'`
    - `refresh_token` => `'encrypted'`
    - `token_expires_at` => `'datetime'`
- Relationships:
    - `applications(): HasMany`

### `App\Modules\AppPlatform\Models\Application`

- Traits: `HasFactory`, `HasUlid`, `SoftDeletes`
- `$guarded = []`
- Casts:
    - `runtime` => `Runtime::class`
    - `settings` => `'json'`
- Relationships:
    - `project(): BelongsTo`
    - `gitConnection(): BelongsTo`
    - `environments(): HasMany`
    - `webhooks(): HasMany` to `App\Modules\Pipeline\Models\Webhook`
- Route binding:
    - default ULID route binding unless slug uniqueness is added later
- Scope ideas:
    - `scopeForProject($query, Project $project)`

### `App\Modules\AppPlatform\Models\Environment`

- Traits: `HasFactory`, `HasUlid`
- `$guarded = []`
- Casts:
    - `type` => `EnvironmentType::class`
    - `is_auto_deploy` => `'boolean'`
- Relationships:
    - `application(): BelongsTo`
    - `cluster(): BelongsTo`
    - `variables(): HasMany`
    - `secrets(): HasMany`
    - `processDefinitions(): HasMany`
- Deferred relationships because related modules do not exist yet:
    - `activeRelease()`
    - `deployments()`
    - `domains()`

### `App\Modules\AppPlatform\Models\EnvironmentVariable`

- Traits: `HasFactory`, `HasUlid`
- `$guarded = []`
- Casts:
    - `is_build_arg` => `'boolean'`
- Relationships:
    - `environment(): BelongsTo`

### `App\Modules\AppPlatform\Models\Secret`

- Traits: `HasFactory`, `HasUlid`
- `$guarded = []`
- Casts:
    - `encrypted_value` => `'encrypted'`
    - `version` => `'integer'`
- Relationships:
    - `environment(): BelongsTo`
- Accessor guidance:
    - do not expose decrypted plaintext in normal serialization

### `App\Modules\AppPlatform\Models\ProcessDefinition`

- Traits: `HasFactory`, `HasUlid`
- `$guarded = []`
- Casts:
    - `type` => `ProcessType::class`
    - `instances` => `'integer'`
- Relationships:
    - `environment(): BelongsTo`

### `App\Modules\Pipeline\Models\Webhook`

- Traits: `HasFactory`, `HasUlid`
- `$guarded = []`
- Casts:
    - `provider` => `GitProvider::class`
    - `secret` => `'encrypted'`
    - `is_active` => `'boolean'`
- Relationships:
    - `application(): BelongsTo`

### Existing model updates

#### `App\Modules\AppPlatform\Models\Project`

- Add relationship:
    - `applications(): HasMany`

#### `App\Modules\Infrastructure\Models\Cluster`

- Add relationship:
    - `environments(): HasMany`

## 6. Action Plan

### `App\Modules\AppPlatform\Actions\CreateGitConnection`

- Signature: `execute(GitProvider $provider, string $accessToken, ?string $refreshToken, ?CarbonInterface $tokenExpiresAt, string $accountName): GitConnection`
- Steps:
    1. Create or update the connection record for the authenticated install/user context.
    2. Persist encrypted tokens and account name.
    3. Dispatch `GitConnectionEstablished`.
- Transaction: no, single-model write
- Events: `GitConnectionEstablished`
- Jobs queued: none

### `App\Modules\AppPlatform\Actions\DeleteGitConnection`

- Signature: `execute(GitConnection $connection): void`
- Steps:
    1. Ensure no applications still depend on the connection or define the desired nulling behavior.
    2. Delete the connection or null app references first if allowed.
- Transaction: yes if related applications are updated
- Events: none required by architecture
- Decision to flag: whether delete should be blocked when linked applications exist

### `App\Modules\AppPlatform\Actions\CreateApplication`

- Signature: `execute(Project $project, CreateApplicationData $data): Application`
- Steps:
    1. Generate a slug from the name.
    2. Create the application with runtime, repo details, and settings.
    3. Dispatch `ApplicationCreated`.
- Transaction: yes
- Events: `ApplicationCreated`
- Jobs queued: none

### `App\Modules\AppPlatform\Actions\UpdateApplication`

- Signature: `execute(Application $application, UpdateApplicationData $data): Application`
- Steps:
    1. Apply changed attributes.
    2. Regenerate slug if name changes.
    3. Save and return fresh model.
    4. If git/runtime/settings changed, optionally dispatch `EnvironmentConfigChanged` only if the spec wants audit coverage.
- Transaction: no, single-model write

### `App\Modules\AppPlatform\Actions\DeleteApplication`

- Signature: `execute(Application $application): void`
- Steps:
    1. Soft delete the application.
    2. Leave environments for cascading soft-delete decision; recommended to hard cascade child env config rows only when application is hard deleted.
- Transaction: yes if child cleanup is added

### `App\Modules\AppPlatform\Actions\CreateEnvironment`

- Signature: `execute(Application $application, CreateEnvironmentData $data): Environment`
- Steps:
    1. Create the environment bound to the selected cluster.
    2. Return the created environment.
    3. Dispatch `EnvironmentConfigChanged` if created environments should be audited uniformly.
- Transaction: no, single-model write
- Events: recommended `EnvironmentConfigChanged`

### `App\Modules\AppPlatform\Actions\UpdateEnvironment`

- Signature: `execute(Environment $environment, UpdateEnvironmentData $data): Environment`
- Steps:
    1. Update cluster, branch, type, and auto-deploy flag.
    2. Dispatch `EnvironmentConfigChanged`.
- Transaction: no
- Events: `EnvironmentConfigChanged`

### `App\Modules\AppPlatform\Actions\DeleteEnvironment`

- Signature: `execute(Environment $environment): void`
- Steps:
    1. Delete the environment.
    2. Cascade deletion to variables, secrets, and process definitions via FK constraints.
    3. Dispatch `EnvironmentConfigChanged` if delete audit should use the same event channel.
- Transaction: yes

### `App\Modules\AppPlatform\Actions\SetEnvironmentVariable`

- Signature: `execute(Environment $environment, SetEnvironmentVariableData $data): EnvironmentVariable`
- Steps:
    1. Upsert by `environment_id + key`.
    2. Persist value and build-arg flag.
    3. Dispatch `EnvironmentConfigChanged`.
- Transaction: no
- Events: `EnvironmentConfigChanged`

### `App\Modules\AppPlatform\Actions\DeleteEnvironmentVariable`

- Signature: `execute(EnvironmentVariable $variable): void`
- Steps:
    1. Delete the variable.
    2. Dispatch `EnvironmentConfigChanged` for the owning environment.
- Transaction: no
- Events: `EnvironmentConfigChanged`

### `App\Modules\AppPlatform\Actions\CreateSecret`

- Signature: `execute(Environment $environment, CreateSecretData $data): Secret`
- Steps:
    1. Encrypt and store the value.
    2. Initialize version to `1`.
    3. Dispatch `SecretUpdated`.
- Transaction: no
- Events: `SecretUpdated`

### `App\Modules\AppPlatform\Actions\UpdateSecret`

- Signature: `execute(Secret $secret, UpdateSecretData $data): Secret`
- Steps:
    1. Update encrypted value.
    2. Increment version.
    3. Dispatch `SecretUpdated`.
- Transaction: no
- Events: `SecretUpdated`

### `App\Modules\AppPlatform\Actions\DeleteSecret`

- Signature: `execute(Secret $secret): void`
- Steps:
    1. Delete the secret.
    2. Dispatch `SecretUpdated` with metadata only.
- Transaction: no
- Events: `SecretUpdated`

### `App\Modules\AppPlatform\Actions\RevealSecret`

- Signature: `execute(Secret $secret): string`
- Steps:
    1. Decrypt and return plaintext.
    2. Record an audit log entry without persisting the plaintext.
- Transaction: no
- Events: architecture does not define one; recommended decision is either add `SecretRevealed` or call `RecordAuditLogAction` directly

### `App\Modules\AppPlatform\Actions\CreateProcessDefinition`

- Signature: `execute(Environment $environment, ProcessDefinitionData $data): ProcessDefinition`
- Steps:
    1. Create process definition with type, command, and instances.
    2. Dispatch `EnvironmentConfigChanged`.
- Transaction: no

### `App\Modules\AppPlatform\Actions\UpdateProcessDefinition`

- Signature: `execute(ProcessDefinition $processDefinition, ProcessDefinitionData $data): ProcessDefinition`
- Steps:
    1. Update process definition fields.
    2. Dispatch `EnvironmentConfigChanged`.
- Transaction: no

### `App\Modules\AppPlatform\Actions\DeleteProcessDefinition`

- Signature: `execute(ProcessDefinition $processDefinition): void`
- Steps:
    1. Delete the process definition.
    2. Dispatch `EnvironmentConfigChanged`.
- Transaction: no

### `App\Modules\Pipeline\Actions\SetupWebhook`

- Signature: `execute(Application $application, GitProvider $provider = GitProvider::Github): Webhook`
- Steps:
    1. Generate a random secret.
    2. Create or update the `webhooks` record for the application/provider pair.
    3. If a compatible git connection exists, register the webhook with GitHub via API.
    4. Return the stored webhook record.
- Transaction: yes
- Events: none required
- Jobs queued: none in Phase 3

## 7. Event Plan

### `App\Modules\AppPlatform\Events\ApplicationCreated`

- Constructor: `__construct(public Application $application)`
- Listeners:
    - `CreateDefaultEnvironment`
    - `SetupApplicationWebhook`
    - `RecordAuditLog`

### `App\Modules\AppPlatform\Events\EnvironmentConfigChanged`

- Constructor: `__construct(public Environment $environment, public string $changeType, public array $changes = [])`
- Listeners:
    - `RecordAuditLog`

### `App\Modules\AppPlatform\Events\GitConnectionEstablished`

- Constructor: `__construct(public GitConnection $gitConnection)`
- Listeners:
    - `RecordAuditLog`

### `App\Modules\AppPlatform\Events\SecretUpdated`

- Constructor: `__construct(public ?Secret $secret, public string $changeType, public array $metadata = [])`
- Listeners:
    - `RecordAuditLog`

### Listener classes to add

#### `App\Modules\AppPlatform\Listeners\CreateDefaultEnvironment`

- Handles: `ApplicationCreated`
- Calls: `CreateEnvironment`
- Default recommendation:
    - create one `production` environment named `production`
    - cluster assignment is ambiguous because environment requires `cluster_id`; see decisions section

#### `App\Modules\AppPlatform\Listeners\SetupApplicationWebhook`

- Handles: `ApplicationCreated`
- Calls: `SetupWebhook`
- Guard:
    - only run when a git connection and repository URL are present

## 8. Job Plan

Phase 3 should not introduce new production jobs unless the roadmap is expanded.

Deferred job dependency:

### `App\Modules\Pipeline\Jobs\ProcessWebhook` (Phase 4 dependency)

- Queue: `QueueName::Pipeline`
- Planned behavior: parse payload, evaluate triggers, create pipeline runs
- Phase 3 handling:
    - do not implement yet in this phase plan
    - webhook controller should either no-op with accepted status or dispatch only if the class already exists later

## 9. Service Plan

### `App\Modules\AppPlatform\Services\GitHubOAuthService`

- Purpose: build auth URL, exchange callback code, fetch authenticated account name
- Public methods:
    - `authorizationUrl(string $state): string`
    - `exchangeCode(string $code): GitHubTokenData`
    - `fetchAccountName(string $accessToken): string`
- External dependencies:
    - Laravel `Http` client
    - `config/services.php` GitHub credentials
- Test mocking:
    - `Http::fake()` for token exchange and user lookup

### `App\Modules\Pipeline\Services\GitHubWebhookService`

- Purpose: register repository webhook and manage callback secret
- Public methods:
    - `registerWebhook(Application $application, GitConnection $connection, string $secret): void`
- External dependencies:
    - Laravel `Http` client
    - application repository metadata
- Test mocking:
    - `Http::fake()` for GitHub webhook API responses

### `App\Http\Middleware\VerifyWebhookSignature`

- Purpose: verify incoming HMAC-SHA256 signatures for provider webhooks
- Public behavior:
    - resolve the `Webhook` record by `{application}` + `{provider}`
    - compare provider-specific signature header against stored secret
    - reject with `401` on mismatch
- Test mocking:
    - no external dependency; request payload + headers only

### Config updates

- Modify `config/services.php` to add GitHub OAuth/app credentials:
    - `client_id`
    - `client_secret`
    - `redirect`
- Modify `.env.example` to add:
    - `GITHUB_CLIENT_ID`
    - `GITHUB_CLIENT_SECRET`
    - `GITHUB_REDIRECT_URI`

## 10. Controller + Route Plan

### API controllers (`routes/api.php`)

#### `App\Http\Controllers\Api\AppPlatform\GitConnectionController`

- `index()` -> `GET /api/v1/git-connections`
    - no FormRequest
    - returns `GitConnectionResource::collection(...)`
- `destroy(GitConnection $gitConnection)` -> `DELETE /api/v1/git-connections/{gitConnection}`
    - no FormRequest
    - calls `DeleteGitConnection`
    - returns `204`

#### `App\Http\Controllers\Api\AppPlatform\ApplicationController`

- `index(Project $project)` -> `GET /api/v1/projects/{project}/applications`
    - no FormRequest
    - returns `ApplicationResource::collection(...)`
- `store(CreateApplicationRequest $request, Project $project)` -> `POST /api/v1/projects/{project}/applications`
    - FormRequest: `CreateApplicationRequest`
    - Action: `CreateApplication`
    - Resource: `ApplicationResource`
- `show(Application $application)` -> `GET /api/v1/applications/{application}`
    - Resource: `ApplicationResource`
- `update(UpdateApplicationRequest $request, Application $application)` -> `PUT /api/v1/applications/{application}`
    - Action: `UpdateApplication`
    - Resource: `ApplicationResource`
- `destroy(Application $application)` -> `DELETE /api/v1/applications/{application}`
    - Action: `DeleteApplication`
    - returns `204`

#### `App\Http\Controllers\Api\AppPlatform\EnvironmentController`

- `index(Application $application)` -> `GET /api/v1/applications/{application}/environments`
- `store(CreateEnvironmentRequest $request, Application $application)` -> `POST /api/v1/applications/{application}/environments`
- `show(Environment $environment)` -> `GET /api/v1/environments/{environment}`
- `update(UpdateEnvironmentRequest $request, Environment $environment)` -> `PUT /api/v1/environments/{environment}`
- `destroy(Environment $environment)` -> `DELETE /api/v1/environments/{environment}`
- Resource: `EnvironmentResource`

#### `App\Http\Controllers\Api\AppPlatform\EnvironmentVariableController`

- `index(Environment $environment)` -> `GET /api/v1/environments/{environment}/variables`
- `store(SetEnvironmentVariableRequest $request, Environment $environment)` -> `POST /api/v1/environments/{environment}/variables`
- `update(SetEnvironmentVariableRequest $request, Environment $environment, EnvironmentVariable $variable)` -> `PUT /api/v1/environments/{environment}/variables/{variable}`
- `destroy(Environment $environment, EnvironmentVariable $variable)` -> `DELETE /api/v1/environments/{environment}/variables/{variable}`
- Resource: `EnvironmentVariableResource`

#### `App\Http\Controllers\Api\AppPlatform\SecretController`

- `index(Environment $environment)` -> `GET /api/v1/environments/{environment}/secrets`
- `store(CreateSecretRequest $request, Environment $environment)` -> `POST /api/v1/environments/{environment}/secrets`
- `update(UpdateSecretRequest $request, Environment $environment, Secret $secret)` -> `PUT /api/v1/environments/{environment}/secrets/{secret}`
- `destroy(Environment $environment, Secret $secret)` -> `DELETE /api/v1/environments/{environment}/secrets/{secret}`
- `reveal(Environment $environment, Secret $secret)` -> `GET /api/v1/environments/{environment}/secrets/{secret}/reveal`
- Resource: `SecretResource` for normal endpoints; dedicated reveal payload for plaintext response

#### `App\Http\Controllers\Api\AppPlatform\ProcessDefinitionController`

- `index(Environment $environment)` -> `GET /api/v1/environments/{environment}/processes`
- `store(ProcessDefinitionRequest $request, Environment $environment)` -> `POST /api/v1/environments/{environment}/processes`
- `update(ProcessDefinitionRequest $request, Environment $environment, ProcessDefinition $processDefinition)` -> `PUT /api/v1/environments/{environment}/processes/{processDefinition}`
- `destroy(Environment $environment, ProcessDefinition $processDefinition)` -> `DELETE /api/v1/environments/{environment}/processes/{processDefinition}`
- Resource: `ProcessDefinitionResource`

### Web controllers (`routes/web.php` or `routes/settings.php`)

#### `App\Http\Controllers\Web\GitConnectionWebController`

- `authorizeGithub()` -> `POST /git-connections/github/authorize`
    - builds redirect via `GitHubOAuthService`
- `handleGithubCallback()` -> `GET /git-connections/github/callback`
    - exchanges code, calls `CreateGitConnection`, redirects back to app creation/settings flow

#### `App\Http\Controllers\Web\ApplicationWebController`

- `create(Project $project)` -> `GET /projects/{project}/applications/create`
- `store(CreateApplicationRequest $request, Project $project)` -> `POST /projects/{project}/applications`
- `show(Application $application)` -> `GET /applications/{application}`
- `update(UpdateApplicationRequest $request, Application $application)` -> `PUT /applications/{application}`
- `destroy(Application $application)` -> `DELETE /applications/{application}`

#### `App\Http\Controllers\Web\EnvironmentWebController`

- `store(CreateEnvironmentRequest $request, Application $application)` -> `POST /applications/{application}/environments`
- `show(Environment $environment)` -> `GET /environments/{environment}`
- `update(UpdateEnvironmentRequest $request, Environment $environment)` -> `PUT /environments/{environment}`
- `destroy(Environment $environment)` -> `DELETE /environments/{environment}`
- inline config mutations can continue to post to API endpoints via Inertia forms or have matching web handlers if route-action generation requires them

#### Existing web controller updates

##### `App\Http\Controllers\Web\ProjectWebController`

- Update `show()` to load applications and app counts instead of Phase 2 placeholder text.

### Webhook controller (`routes/webhooks.php`)

#### `App\Http\Controllers\Api\Pipeline\WebhookController`

- `handle(Application $application, GitProvider|string $provider)` -> `POST /webhooks/{application}/{provider}`
    - Middleware: `VerifyWebhookSignature`
    - Phase 3 behavior:
        - verify signature
        - return `202 Accepted`
        - if Phase 4 job exists later, dispatch `ProcessWebhook`

## 11. Frontend Plan

### Type additions

- Modify `resources/js/types/models.ts` to add:
    - `GitConnection`
    - `Application`
    - `Environment`
    - `EnvironmentVariable`
    - `Secret`
    - `ProcessDefinition`
    - optional lightweight `Webhook`

### Pages and components

#### `resources/js/pages/projects/show.tsx`

- Replace the Phase 2 placeholder card.
- Props:
    - `project`
    - `applications`
- UI:
    - applications list/cards
    - button to create an application
    - app runtime/repo summary

#### `resources/js/pages/applications/create.tsx`

- Props:
    - `project`
    - `gitConnections`
    - `clusters`
- UI:
    - app name/runtime/repo fields
    - GitHub connection picker
    - connect GitHub CTA when no connection exists
    - optional default environment creation fields if the workflow bundles app + first env

#### `resources/js/pages/applications/show.tsx`

- Props:
    - `application`
    - `project`
    - `environments`
    - `gitConnections`
- UI:
    - tabbed layout matching architecture: Overview, Environments, Pipelines, Deployments, Settings
    - Overview tab shows repo, runtime, current config summary
    - Environments tab lists environments with cluster and branch
    - Pipelines/Deployments tabs can show Phase 4/5 placeholders if those modules are not built yet
    - Settings tab allows runtime/repo edits and delete action

#### `resources/js/pages/environments/show.tsx`

- Props:
    - `environment`
    - `application`
    - `variables`
    - `secrets`
    - `processDefinitions`
    - `clusters`
- UI:
    - environment summary header
    - inline env var editor
    - secrets list with reveal button
    - process definitions section
    - placeholders for domains/health/deploy actions until later phases

#### Optional reusable components

- `resources/js/components/applications/application-header.tsx`
- `resources/js/components/applications/application-tabs.tsx`
- `resources/js/components/environments/environment-variable-editor.tsx`
- `resources/js/components/environments/secret-list.tsx`
- `resources/js/components/environments/process-definition-list.tsx`
- `resources/js/components/git-connections/git-connection-picker.tsx`

### Existing navigation updates

- Keep sidebar unchanged.
- Add application breadcrumbs within project detail and application detail pages.
- No settings-nav change unless a dedicated git connections settings page is added after scope clarification.

## 12. Test Plan

### Unit tests

#### `tests/Unit/Modules/AppPlatform/Actions/CreateGitConnectionTest.php`

- `test_it_creates_git_connection_with_encrypted_tokens`
- `test_it_dispatches_git_connection_established_event`

#### `tests/Unit/Modules/AppPlatform/Actions/DeleteGitConnectionTest.php`

- `test_it_deletes_unused_git_connection`
- `test_it_rejects_deletion_when_applications_are_attached` or `test_it_nulls_application_connections_before_delete` depending on final decision

#### `tests/Unit/Modules/AppPlatform/Actions/CreateApplicationTest.php`

- `test_it_creates_application_for_project`
- `test_it_generates_slug_from_name`
- `test_it_dispatches_application_created_event`

#### `tests/Unit/Modules/AppPlatform/Actions/UpdateApplicationTest.php`

- `test_it_updates_application_fields`
- `test_it_regenerates_slug_when_name_changes`

#### `tests/Unit/Modules/AppPlatform/Actions/DeleteApplicationTest.php`

- `test_it_soft_deletes_application`

#### `tests/Unit/Modules/AppPlatform/Actions/CreateEnvironmentTest.php`

- `test_it_creates_environment_for_application`
- `test_it_enforces_unique_name_per_application`

#### `tests/Unit/Modules/AppPlatform/Actions/UpdateEnvironmentTest.php`

- `test_it_updates_cluster_branch_and_auto_deploy`

#### `tests/Unit/Modules/AppPlatform/Actions/DeleteEnvironmentTest.php`

- `test_it_deletes_environment`

#### `tests/Unit/Modules/AppPlatform/Actions/SetEnvironmentVariableTest.php`

- `test_it_creates_variable`
- `test_it_updates_existing_variable_by_key`

#### `tests/Unit/Modules/AppPlatform/Actions/DeleteEnvironmentVariableTest.php`

- `test_it_deletes_variable`

#### `tests/Unit/Modules/AppPlatform/Actions/CreateSecretTest.php`

- `test_it_stores_secret_with_encrypted_value`
- `test_it_dispatches_secret_updated_event`

#### `tests/Unit/Modules/AppPlatform/Actions/UpdateSecretTest.php`

- `test_it_rotates_secret_value_and_increments_version`

#### `tests/Unit/Modules/AppPlatform/Actions/DeleteSecretTest.php`

- `test_it_deletes_secret_without_leaking_plaintext`

#### `tests/Unit/Modules/AppPlatform/Actions/RevealSecretTest.php`

- `test_it_returns_decrypted_secret_value`
- `test_it_records_audit_log_for_reveal`

#### `tests/Unit/Modules/AppPlatform/Actions/CreateProcessDefinitionTest.php`

- `test_it_creates_process_definition`

#### `tests/Unit/Modules/AppPlatform/Actions/UpdateProcessDefinitionTest.php`

- `test_it_updates_process_definition`

#### `tests/Unit/Modules/AppPlatform/Actions/DeleteProcessDefinitionTest.php`

- `test_it_deletes_process_definition`

#### `tests/Unit/Modules/Pipeline/Actions/SetupWebhookTest.php`

- `test_it_creates_webhook_record_with_secret`
- `test_it_registers_webhook_with_github_when_connection_exists`

#### `tests/Unit/Modules/AppPlatform/Listeners/CreateDefaultEnvironmentTest.php`

- `test_it_creates_default_environment_when_application_is_created`

### Feature tests

#### `tests/Feature/Api/AppPlatform/GitConnectionControllerTest.php`

- `test_can_list_git_connections`
- `test_can_delete_git_connection`
- `test_unauthenticated_requests_return_401`

#### `tests/Feature/Api/AppPlatform/ApplicationControllerTest.php`

- `test_can_list_project_applications`
- `test_can_create_application`
- `test_can_show_application`
- `test_can_update_application`
- `test_can_delete_application`
- `test_validation_fails_for_invalid_runtime`
- `test_unauthenticated_requests_return_401`

#### `tests/Feature/Api/AppPlatform/EnvironmentControllerTest.php`

- `test_can_list_application_environments`
- `test_can_create_environment`
- `test_can_show_environment`
- `test_can_update_environment`
- `test_can_delete_environment`
- `test_duplicate_environment_name_within_application_returns_422`

#### `tests/Feature/Api/AppPlatform/EnvironmentVariableControllerTest.php`

- `test_can_list_environment_variables`
- `test_can_set_environment_variable`
- `test_can_update_environment_variable`
- `test_can_delete_environment_variable`

#### `tests/Feature/Api/AppPlatform/SecretControllerTest.php`

- `test_can_list_secrets_without_plaintext_values`
- `test_can_create_secret`
- `test_can_update_secret`
- `test_can_delete_secret`
- `test_can_reveal_secret_explicitly`
- `test_normal_secret_endpoints_never_leak_plaintext`

#### `tests/Feature/Api/AppPlatform/ProcessDefinitionControllerTest.php`

- `test_can_list_process_definitions`
- `test_can_create_process_definition`
- `test_can_update_process_definition`
- `test_can_delete_process_definition`

#### `tests/Feature/Api/Pipeline/WebhookControllerTest.php`

- `test_valid_signature_returns_202`
- `test_invalid_signature_returns_401`
- `test_unknown_application_or_provider_returns_404`

### Factories required

- `database/factories/GitConnectionFactory.php`
- `database/factories/ApplicationFactory.php`
- `database/factories/EnvironmentFactory.php`
- `database/factories/EnvironmentVariableFactory.php`
- `database/factories/SecretFactory.php`
- `database/factories/ProcessDefinitionFactory.php`
- `database/factories/WebhookFactory.php`

## 13. File Creation Order

1. Modify `config/services.php`
2. Modify `.env.example`
3. Create `database/migrations/2026_04_03_000001_create_git_connections_table.php`
4. Create `database/migrations/2026_04_03_000002_create_applications_table.php`
5. Create `database/migrations/2026_04_03_000003_create_environments_table.php`
6. Create `database/migrations/2026_04_03_000004_create_environment_variables_table.php`
7. Create `database/migrations/2026_04_03_000005_create_secrets_table.php`
8. Create `database/migrations/2026_04_03_000006_create_process_definitions_table.php`
9. Create `database/migrations/2026_04_03_000007_create_webhooks_table.php`
10. Create `app/Modules/AppPlatform/Enums/GitProvider.php`
11. Create `app/Modules/AppPlatform/Enums/Runtime.php`
12. Create `app/Modules/AppPlatform/Enums/EnvironmentType.php`
13. Create `app/Modules/AppPlatform/Enums/ProcessType.php`
14. Create `app/Modules/AppPlatform/Models/GitConnection.php`
15. Create `app/Modules/AppPlatform/Models/Application.php`
16. Create `app/Modules/AppPlatform/Models/Environment.php`
17. Create `app/Modules/AppPlatform/Models/EnvironmentVariable.php`
18. Create `app/Modules/AppPlatform/Models/Secret.php`
19. Create `app/Modules/AppPlatform/Models/ProcessDefinition.php`
20. Create `app/Modules/Pipeline/Models/Webhook.php`
21. Modify `app/Modules/AppPlatform/Models/Project.php`
22. Modify `app/Modules/Infrastructure/Models/Cluster.php`
23. Create `app/Modules/AppPlatform/DTOs/CreateApplicationData.php`
24. Create `app/Modules/AppPlatform/DTOs/UpdateApplicationData.php`
25. Create `app/Modules/AppPlatform/DTOs/CreateEnvironmentData.php`
26. Create `app/Modules/AppPlatform/DTOs/UpdateEnvironmentData.php`
27. Create `app/Modules/AppPlatform/DTOs/SetEnvironmentVariableData.php`
28. Create `app/Modules/AppPlatform/DTOs/CreateSecretData.php`
29. Create `app/Modules/AppPlatform/DTOs/UpdateSecretData.php`
30. Create `app/Modules/AppPlatform/DTOs/ProcessDefinitionData.php`
31. Create `app/Modules/AppPlatform/Events/ApplicationCreated.php`
32. Create `app/Modules/AppPlatform/Events/EnvironmentConfigChanged.php`
33. Create `app/Modules/AppPlatform/Events/GitConnectionEstablished.php`
34. Create `app/Modules/AppPlatform/Events/SecretUpdated.php`
35. Create `app/Modules/AppPlatform/Services/GitHubOAuthService.php`
36. Create `app/Modules/Pipeline/Services/GitHubWebhookService.php`
37. Create `app/Modules/AppPlatform/Actions/CreateGitConnection.php`
38. Create `app/Modules/AppPlatform/Actions/DeleteGitConnection.php`
39. Create `app/Modules/AppPlatform/Actions/CreateApplication.php`
40. Create `app/Modules/AppPlatform/Actions/UpdateApplication.php`
41. Create `app/Modules/AppPlatform/Actions/DeleteApplication.php`
42. Create `app/Modules/AppPlatform/Actions/CreateEnvironment.php`
43. Create `app/Modules/AppPlatform/Actions/UpdateEnvironment.php`
44. Create `app/Modules/AppPlatform/Actions/DeleteEnvironment.php`
45. Create `app/Modules/AppPlatform/Actions/SetEnvironmentVariable.php`
46. Create `app/Modules/AppPlatform/Actions/DeleteEnvironmentVariable.php`
47. Create `app/Modules/AppPlatform/Actions/CreateSecret.php`
48. Create `app/Modules/AppPlatform/Actions/UpdateSecret.php`
49. Create `app/Modules/AppPlatform/Actions/DeleteSecret.php`
50. Create `app/Modules/AppPlatform/Actions/RevealSecret.php`
51. Create `app/Modules/AppPlatform/Actions/CreateProcessDefinition.php`
52. Create `app/Modules/AppPlatform/Actions/UpdateProcessDefinition.php`
53. Create `app/Modules/AppPlatform/Actions/DeleteProcessDefinition.php`
54. Create `app/Modules/Pipeline/Actions/SetupWebhook.php`
55. Create `app/Modules/AppPlatform/Listeners/CreateDefaultEnvironment.php`
56. Create `app/Modules/AppPlatform/Listeners/SetupApplicationWebhook.php`
57. Modify `app/Modules/Operations/Listeners/RecordAuditLog.php`
58. Modify `app/Providers/AppServiceProvider.php`
59. Create `app/Http/Middleware/VerifyWebhookSignature.php`
60. Create `database/factories/GitConnectionFactory.php`
61. Create `database/factories/ApplicationFactory.php`
62. Create `database/factories/EnvironmentFactory.php`
63. Create `database/factories/EnvironmentVariableFactory.php`
64. Create `database/factories/SecretFactory.php`
65. Create `database/factories/ProcessDefinitionFactory.php`
66. Create `database/factories/WebhookFactory.php`
67. Create `app/Http/Requests/AppPlatform/CreateApplicationRequest.php`
68. Create `app/Http/Requests/AppPlatform/UpdateApplicationRequest.php`
69. Create `app/Http/Requests/AppPlatform/CreateEnvironmentRequest.php`
70. Create `app/Http/Requests/AppPlatform/UpdateEnvironmentRequest.php`
71. Create `app/Http/Requests/AppPlatform/SetEnvironmentVariableRequest.php`
72. Create `app/Http/Requests/AppPlatform/CreateSecretRequest.php`
73. Create `app/Http/Requests/AppPlatform/UpdateSecretRequest.php`
74. Create `app/Http/Requests/AppPlatform/ProcessDefinitionRequest.php`
75. Create `app/Http/Resources/AppPlatform/GitConnectionResource.php`
76. Create `app/Http/Resources/AppPlatform/ApplicationResource.php`
77. Create `app/Http/Resources/AppPlatform/EnvironmentResource.php`
78. Create `app/Http/Resources/AppPlatform/EnvironmentVariableResource.php`
79. Create `app/Http/Resources/AppPlatform/SecretResource.php`
80. Create `app/Http/Resources/AppPlatform/ProcessDefinitionResource.php`
81. Create `app/Http/Controllers/Api/AppPlatform/GitConnectionController.php`
82. Create `app/Http/Controllers/Api/AppPlatform/ApplicationController.php`
83. Create `app/Http/Controllers/Api/AppPlatform/EnvironmentController.php`
84. Create `app/Http/Controllers/Api/AppPlatform/EnvironmentVariableController.php`
85. Create `app/Http/Controllers/Api/AppPlatform/SecretController.php`
86. Create `app/Http/Controllers/Api/AppPlatform/ProcessDefinitionController.php`
87. Create `app/Http/Controllers/Api/Pipeline/WebhookController.php`
88. Create `app/Http/Controllers/Web/GitConnectionWebController.php`
89. Create `app/Http/Controllers/Web/ApplicationWebController.php`
90. Create `app/Http/Controllers/Web/EnvironmentWebController.php`
91. Modify `app/Http/Controllers/Web/ProjectWebController.php`
92. Modify `routes/api.php`
93. Modify `routes/web.php`
94. Modify `routes/webhooks.php`
95. Modify `resources/js/types/models.ts`
96. Create `resources/js/pages/applications/create.tsx`
97. Create `resources/js/pages/applications/show.tsx`
98. Create `resources/js/pages/environments/show.tsx`
99. Modify `resources/js/pages/projects/show.tsx`
100. Optionally create reusable app/environment components under `resources/js/components/`
101. Create `tests/Unit/Modules/AppPlatform/Actions/CreateGitConnectionTest.php`
102. Create `tests/Unit/Modules/AppPlatform/Actions/DeleteGitConnectionTest.php`
103. Create `tests/Unit/Modules/AppPlatform/Actions/CreateApplicationTest.php`
104. Create `tests/Unit/Modules/AppPlatform/Actions/UpdateApplicationTest.php`
105. Create `tests/Unit/Modules/AppPlatform/Actions/DeleteApplicationTest.php`
106. Create `tests/Unit/Modules/AppPlatform/Actions/CreateEnvironmentTest.php`
107. Create `tests/Unit/Modules/AppPlatform/Actions/UpdateEnvironmentTest.php`
108. Create `tests/Unit/Modules/AppPlatform/Actions/DeleteEnvironmentTest.php`
109. Create `tests/Unit/Modules/AppPlatform/Actions/SetEnvironmentVariableTest.php`
110. Create `tests/Unit/Modules/AppPlatform/Actions/DeleteEnvironmentVariableTest.php`
111. Create `tests/Unit/Modules/AppPlatform/Actions/CreateSecretTest.php`
112. Create `tests/Unit/Modules/AppPlatform/Actions/UpdateSecretTest.php`
113. Create `tests/Unit/Modules/AppPlatform/Actions/DeleteSecretTest.php`
114. Create `tests/Unit/Modules/AppPlatform/Actions/RevealSecretTest.php`
115. Create `tests/Unit/Modules/AppPlatform/Actions/CreateProcessDefinitionTest.php`
116. Create `tests/Unit/Modules/AppPlatform/Actions/UpdateProcessDefinitionTest.php`
117. Create `tests/Unit/Modules/AppPlatform/Actions/DeleteProcessDefinitionTest.php`
118. Create `tests/Unit/Modules/AppPlatform/Listeners/CreateDefaultEnvironmentTest.php`
119. Create `tests/Unit/Modules/Pipeline/Actions/SetupWebhookTest.php`
120. Create `tests/Feature/Api/AppPlatform/GitConnectionControllerTest.php`
121. Create `tests/Feature/Api/AppPlatform/ApplicationControllerTest.php`
122. Create `tests/Feature/Api/AppPlatform/EnvironmentControllerTest.php`
123. Create `tests/Feature/Api/AppPlatform/EnvironmentVariableControllerTest.php`
124. Create `tests/Feature/Api/AppPlatform/SecretControllerTest.php`
125. Create `tests/Feature/Api/AppPlatform/ProcessDefinitionControllerTest.php`
126. Create `tests/Feature/Api/Pipeline/WebhookControllerTest.php`

## Conflicts / Decisions To Resolve Before Implementation

1. Roadmap 3.7 conflicts with architecture: `database_instances`, `cache_instances`, and `service_bindings` are post-MVP in architecture and have no table definitions in Section 6. Recommended resolution: remove them from Phase 3 implementation scope until architecture is updated.
2. `environments.active_release_id` conflicts with roadmap Phase 5.6. Recommended resolution: omit the column from Phase 3 and add it in Phase 5.
3. GitHub OAuth callback route is required but missing from Section 13 API design. Recommended resolution: add `GET /git-connections/github/callback` in `routes/web.php`.
4. `ApplicationCreated -> CreateDefaultEnvironments` conflicts with required `cluster_id` on `environments`. Recommended resolution: create a default production environment only when the application creation form also selects a cluster; otherwise skip the listener until a cluster is available.
5. Application slug is not unique in architecture. Recommended resolution: use ULID route binding for applications unless a unique constraint is added.
6. Webhook ingestion belongs to Phase 4 because `ProcessWebhook` is a Phase 4 job. Recommended resolution: Phase 3 implements registration + verification only, with the route returning `202` until pipeline ingestion lands.
7. Secret reveal must be audit-logged, but architecture does not define a `SecretRevealed` event. Recommended resolution: add a small event and listener mapping or explicitly call `RecordAuditLogAction` from `RevealSecret`.
