# Helm — Implementation Roadmap

> Granular task tracking for each build phase.
> Architecture reference: `architecture.md` (same directory)
> Dev guide: `../CLAUDE.md`

---

## Phase 0: Foundation

**Status: COMPLETE**

- [x] Configure PostgreSQL as default DB, create `helm` database
- [x] Configure Redis for sessions, cache, queues in `.env`
- [x] Add S3/artifacts filesystem disk (`config/filesystems.php`)
- [x] Create `config/helm.php` (agent, runner, pipeline, deployment, retention settings)
- [x] Create `HasUlid` trait (`app/Support/Concerns/HasUlid.php`)
- [x] Create `HasStateMachine` trait (`app/Support/Concerns/HasStateMachine.php`)
- [x] Create `QueueName` enum (`app/Support/Enums/QueueName.php`)
- [x] Create `ObjectStorageService` (`app/Support/Services/ObjectStorage/`)
- [x] Create module directory structure (8 modules × subdirectories)
- [x] Create route files: `api.php`, `agent.php`, `runner.php`, `webhooks.php`
- [x] Register route files in `bootstrap/app.php`
- [x] Create HTTP controller/request/resource directory structure
- [x] Create test directory structure (Unit/Feature/Integration/Contract)
- [x] Update User model to use ULID primary key
- [x] Update users migration to `ulid('id')->primary()`
- [x] Fix `ProfileValidationRules` for ULID user IDs
- [x] Update `.env.example`
- [x] All 40 existing tests passing
- [x] Pint style check passing

---

## Phase 1: Auth, Projects, Audit Logs

**Status: COMPLETE**

### 1.1 Personal Access Tokens

- [x] Migration: `create_personal_access_tokens_table` (ulid PK, user_id, name, token hash, abilities jsonb, last_used_at, expires_at)
- [x] Model: `app/Models/PersonalAccessToken.php` (stays at root, used by auth guard)
- [x] Action: `CreatePersonalAccessToken` (hash token, return plaintext once)
- [x] Action: `RevokePersonalAccessToken`
- [x] Middleware: `AuthenticateWithToken` (Bearer token lookup, set auth user)
- [x] Register middleware in `bootstrap/app.php` for API routes
- [x] Controller: `app/Http/Controllers/Api/PersonalAccessTokenController.php` (index, store, destroy)
- [x] FormRequest: `CreateTokenRequest`
- [x] Resource: `PersonalAccessTokenResource`
- [x] Factory: `PersonalAccessTokenFactory`
- [x] Feature test: token CRUD endpoints
- [x] Feature test: API auth via token
- [x] UI: tokens list page under settings
- [x] UI: create token modal (show plaintext once)
- [x] UI: revoke token button

### 1.2 SSH Keys

- [x] Migration: `create_ssh_keys_table` (ulid PK, user_id, name, public_key text, fingerprint unique)
- [x] Model: `app/Models/SshKey.php` (stays at root)
- [x] Action: `AddSshKey` (compute fingerprint from public key, validate format)
- [x] Action: `RemoveSshKey`
- [x] Controller: `app/Http/Controllers/Api/SshKeyController.php` (index, store, destroy)
- [x] FormRequest: `AddSshKeyRequest` (validate public key format)
- [x] Resource: `SshKeyResource`
- [x] Factory: `SshKeyFactory`
- [x] Feature test: SSH key CRUD
- [x] Feature test: duplicate fingerprint rejection
- [x] UI: SSH keys list page under settings
- [x] UI: add key form, delete button

### 1.3 Projects

- [x] Migration: `create_projects_table` (ulid PK, name, slug unique, description nullable, timestamps, soft_deletes)
- [x] Model: `app/Modules/AppPlatform/Models/Project.php`
- [x] Action: `CreateProject` (generate slug from name)
- [x] Action: `UpdateProject`
- [x] Action: `DeleteProject` (soft delete)
- [x] Event: `ProjectCreated`
- [x] Controller: `app/Http/Controllers/Api/AppPlatform/ProjectController.php` (index, store, show, update, destroy)
- [x] FormRequest: `CreateProjectRequest`, `UpdateProjectRequest`
- [x] Resource: `ProjectResource`
- [x] Factory: `ProjectFactory`
- [x] Feature test: project CRUD
- [x] Feature test: slug uniqueness
- [x] UI: projects list page (cards with name, description, app count)
- [x] UI: create project modal
- [x] UI: project detail page (will hold apps later)

### 1.4 Audit Logs

- [x] Migration: `create_audit_logs_table` (ulid PK, user_id nullable, action varchar, auditable_type, auditable_id, old_values jsonb, new_values jsonb, ip_address inet, user_agent text, created_at — NO updated_at)
- [x] Model: `app/Modules/Operations/Models/AuditLog.php` (no HasUlid timestamps override needed — only created_at)
- [x] Action: `RecordAuditLog`
- [x] Listener: `app/Modules/Operations/Listeners/RecordAuditLog.php` (listens to all domain events)
- [x] Register listener in `EventServiceProvider` or via event discovery
- [x] Controller: `app/Http/Controllers/Api/Operations/AuditLogController.php` (index only — read-only)
- [x] Resource: `AuditLogResource`
- [x] Feature test: audit log creation on events
- [x] Feature test: audit log list endpoint with filters
- [x] UI: activity feed page (chronological list, filterable)

### 1.5 Sidebar Navigation + Dashboard

- [x] Update Inertia layout with sidebar navigation matching architecture (Dashboard, Servers, Clusters, Projects, Runners, Activity, Settings)
- [x] Dashboard page: placeholder cards for servers, apps, recent deploys, recent pipelines
- [x] Settings layout with sub-nav: Profile, SSH Keys, API Tokens
- [x] Wire up existing profile/security settings pages into new layout
- [x] Move provider settings into Settings section (placeholder for Phase 2)

### 1.6 Phase 1 Completion

- [x] All new tests passing
- [x] Pint passing
- [x] Update CLAUDE.md phase table to mark Phase 1 complete
- [x] Update this roadmap

---

## Phase 2: Infrastructure

**Status: COMPLETE**

### 2.1 Providers

- [x] Migration: `create_providers_table` (ulid PK, name, type varchar, credentials encrypted text, is_active bool)
- [x] Model: `app/Modules/Infrastructure/Models/Provider.php`
- [x] Enum: `ProviderType` (digitalocean, hetzner, vultr, aws, manual)
- [x] Action: `CreateProvider`, `UpdateProvider`, `DeleteProvider`, `TestProviderConnection`
- [x] Service interface: `app/Modules/Infrastructure/Services/Providers/ProviderInterface.php`
- [x] Service: `DigitalOceanProvider.php` (list regions, list sizes, create droplet, delete droplet)
- [x] Service: `ManualProvider.php` (no-op — manual servers don't use provider API)
- [x] Controller: `ProviderController` (index, store, update, destroy, test)
- [x] FormRequest: `CreateProviderRequest`, `UpdateProviderRequest`
- [x] Resource: `ProviderResource` (NEVER expose credentials)
- [x] Factory: `ProviderFactory`
- [x] Feature tests: CRUD, test connection
- [x] UI: provider management in settings

### 2.2 Servers

- [x] Migration: `create_servers_table` (full schema from architecture doc Section 6)
- [x] Model: `app/Modules/Infrastructure/Models/Server.php` (HasStateMachine, relationships, casts)
- [x] Enum: `ServerStatus` (pending, provisioning, bootstrapping, active, draining, cordoned, maintenance, decommissioning, decommissioned, failed)
- [x] Enum: `NodeRole` (web, worker, db, cache, queue, bastion)
- [x] State machine: transitions map in Server model (from architecture doc Section 7.1)
- [x] Action: `RegisterServer` (manual registration — IP, SSH creds, name)
- [x] Action: `ProvisionServer` (create via provider API)
- [x] Action: `DrainNode`, `CordonNode`, `ActivateNode`, `DecommissionNode`
- [x] Event: `ServerRegistered`, `ServerBootstrapped`, `ServerHealthChanged`
- [x] DTO: `RegisterServerData`
- [x] Controller: `ServerController` (index, store, show, update, destroy + bootstrap/drain/cordon/activate)
- [x] FormRequests, Resources
- [x] Factory: `ServerFactory`
- [x] Unit tests: state machine transitions (all valid + all invalid)
- [x] Feature tests: server CRUD, state transitions
- [x] UI: server list (table with status, IP, cluster, roles, heartbeat)
- [x] UI: server detail (status badge, actions, metadata)
- [x] UI: register server form

### 2.3 SSH Bootstrap

- [x] Service: `app/Modules/Infrastructure/Services/SshService.php` (connect, execute command, upload file, disconnect)
- [x] Service: `app/Modules/Infrastructure/Services/ServerBootstrapper.php` (orchestrate full bootstrap sequence)
- [x] Action: `BootstrapServer` (generate agent token, call bootstrapper, update status)
- [x] Job: `app/Modules/Infrastructure/Jobs/BootstrapServer.php` (async, on infrastructure queue)
- [x] Job: `app/Modules/Infrastructure/Jobs/ProvisionServer.php` (create via provider API, then bootstrap)
- [x] Job: `app/Modules/Infrastructure/Jobs/PushSshKeys.php` (update authorized_keys on server)
- [x] Bootstrap script template (install packages, configure firewall, create helm user, install agent)
- [x] Feature test: bootstrap job (mock SSH)
- [x] UI: bootstrap button on server detail, progress indication

### 2.4 Clusters

- [x] Migration: `create_clusters_table`, `create_cluster_node_table`
- [x] Model: `app/Modules/Infrastructure/Models/Cluster.php` (HasStateMachine)
- [x] Enum: `ClusterStatus` (pending, provisioning, active, updating, scaling, degraded, maintenance, decommissioning, decommissioned)
- [x] State machine: transitions in Cluster model (from architecture doc Section 7.2)
- [x] Action: `CreateCluster`, `UpdateCluster`, `DeleteCluster`
- [x] Action: `AddNodeToCluster`, `RemoveNodeFromCluster`, `UpdateNodeRole`
- [x] Event: `ClusterTopologyChanged`
- [x] Controller: `ClusterController` (CRUD + node management endpoints)
- [x] FormRequests, Resources
- [x] Factory: `ClusterFactory`
- [x] Unit tests: cluster state machine
- [x] Feature tests: cluster CRUD, node assignment
- [x] UI: cluster list (name, status, node count by role)
- [x] UI: cluster detail (node list, add/remove nodes, role assignment)

### 2.5 Agent API

- [x] Middleware: `AuthenticateAgent` (validate server agent token from Bearer header)
- [x] Migration or column: `agent_commands` table (id, server_id, type, payload jsonb, status, result jsonb, expires_at, created_at, completed_at)
- [x] Controller: `app/Http/Controllers/Api/Agent/AgentController.php` (heartbeat, commands/pending, commands/{id}/result)
- [x] Update `routes/agent.php` with actual routes + middleware
- [x] Heartbeat handler: update server `last_heartbeat_at`, store basic metrics
- [x] Command queue: create commands for agents, agents poll and execute
- [x] Contract tests: agent API request/response shapes

### 2.6 Phase 2 Completion

- [x] All tests passing
- [x] Pint passing
- [x] Update CLAUDE.md + this roadmap

---

## Phase 3: Applications, Environments, Secrets

**Status: COMPLETE**

### 3.1 Git Connections

- [x] Migration: `create_git_connections_table`
- [x] Model: `GitConnection.php`
- [x] GitHub OAuth flow (authorize, callback, store tokens)
- [x] Action: `CreateGitConnection`, `DeleteGitConnection`
- [x] Controller + routes for OAuth flow
- [x] UI: connect GitHub account in app creation flow

### 3.2 Applications

- [x] Migration: `create_applications_table`
- [x] Model: `Application.php`
- [x] Enum: `Runtime` (php, node, python, go)
- [x] Action: `CreateApplication`, `UpdateApplication`, `DeleteApplication`
- [x] Event: `ApplicationCreated` with default-environment listener when a default cluster is supplied
- [x] Controller, FormRequests, Resources
- [x] Factory
- [x] Tests
- [x] UI: app list within project, app creation form, app detail page

### 3.3 Environments

- [x] Migration: `create_environments_table`
- [x] Model: `Environment.php`
- [x] Enum: `EnvironmentType` (production, staging, preview)
- [x] Action: `CreateEnvironment`, `UpdateEnvironment`, `DeleteEnvironment`
- [x] Controller, FormRequests, Resources
- [x] Factory
- [x] Tests
- [x] UI: environment list in app detail, environment detail page

### 3.4 Environment Variables

- [x] Migration: `create_environment_variables_table`
- [x] Model, Action (`SetEnvironmentVariable`, `DeleteEnvironmentVariable`), Controller
- [x] UI: inline key-value editor on environment detail page

### 3.5 Secrets

- [x] Migration: `create_secrets_table`
- [x] Model: `Secret.php` (encrypted_value cast)
- [x] Action: `CreateSecret`, `UpdateSecret`, `DeleteSecret`, `RevealSecret` (audit logged)
- [x] Controller (CRUD + reveal endpoint)
- [x] Tests: ensure values never leaked in normal API responses
- [x] UI: secret key list, reveal button, create/edit/delete

### 3.6 Process Definitions

- [x] Migration: `create_process_definitions_table`
- [x] Model, Enum (`ProcessType`: web, worker, scheduler, custom)
- [x] Action, Controller
- [x] UI: process list on environment detail

### 3.7 Service Bindings + Managed Services

- [x] Migration: `create_database_instances_table`
- [x] Migration: `create_cache_instances_table`
- [x] Migration: `create_service_bindings_table`
- [x] Model: `DatabaseInstance.php`, `CacheInstance.php`, `ServiceBinding.php`
- [x] Enum: `DatabaseEngine` (`postgres`, `mysql`)
- [x] Enum: `CacheEngine` (`redis`, `valkey`)
- [x] Action: `ProvisionDatabase`, `DeleteDatabase`, `RotateDatabaseCredentials`
- [x] Action: `ProvisionCache`, `DeleteCache`, `RotateCacheCredentials`
- [x] Action: `BindServiceToEnvironment`, `UnbindServiceFromEnvironment`
- [x] Service: `DatabaseProvisioner` interface with `PostgresProvisioner` and `MysqlProvisioner`
- [x] Service: `CacheProvisioner` interface with `RedisProvisioner`
- [x] Support db-role nodes for dedicated database servers
- [x] Support cache-role nodes for dedicated cache servers
- [x] Migration: `create_storage_buckets_table` (ulid PK, name, provider varchar, region varchar, access_key encrypted, secret_key encrypted, bucket_name, timestamps)
- [x] Model: `StorageBucket.php`
- [x] Action: `ProvisionStorageBucket`, `DeleteStorageBucket`, `RotateStorageCredentials`
- [ ] Action: `BindStorageToEnvironment`, `UnbindStorageFromEnvironment`
- [x] Store generated service credentials as secrets automatically
- [x] Controller, FormRequests, Resources
- [x] Tests: engine selection, provisioning flow, credential rotation, environment binding, storage provisioning
- [x] UI: service provisioning cards on environment detail (Add database, Add cache, Add bucket) with picker modals
- [ ] UI: database cluster picker modal (select existing or create new, show engine version badge)
- [ ] UI: cache picker modal (select existing or create new)
- [ ] UI: storage bucket picker modal (select existing or create new)

### 3.8 Webhooks

- [x] Migration: `create_webhooks_table`
- [x] Model: `Webhook.php`
- [x] Action: `SetupWebhook` (register webhook on GitHub via API)
- [x] Webhook signature verification middleware
- [x] Update `routes/webhooks.php` with actual route
- [x] Controller: `WebhookController` (Phase 3 placeholder receive/verify path; full `ProcessWebhook` ingestion deferred to Phase 4)
- [x] Tests: signature verification, payload parsing

### 3.9 Phase 3 Completion

- [x] All tests passing, pint passing
- [x] Update CLAUDE.md + this roadmap

---

## Phase 4: Pipelines, Runners, Artifacts

**Status: COMPLETE**

### 4.1 Pipelines

- [x] Migration: `create_pipelines_table`
- [x] Model: `Pipeline.php` (definition jsonb, trigger config)
- [x] Action: `CreatePipeline`, `UpdatePipeline`, `DeletePipeline`
- [x] Pipeline definition JSON schema validation
- [x] Controller, FormRequests, Resources
- [x] Tests
- [x] UI: pipeline list in app detail, pipeline editor (JSON form builder or raw JSON)

### 4.2 Pipeline Runs

- [x] Migration: `create_pipeline_runs_table`
- [x] Model: `PipelineRun.php` (HasStateMachine)
- [x] Enum: `PipelineRunStatus` (pending, running, succeeded, failed, cancelled, timed_out)
- [x] Enum: `TriggerType` (push, tag, pull_request, manual, api, schedule)
- [x] State machine transitions (architecture doc Section 7.3)
- [x] Action: `TriggerPipelineRun` (snapshot definition, create run + jobs)
- [x] Service: `PipelineOrchestrator` (stage sequencing, job status tracking)
- [x] Job: `ProcessWebhook` (parse payload, evaluate triggers, create runs)
- [x] Job: `OrchestrateRun` (coordinate stages, advance on job completion)
- [x] Job: `CheckJobTimeout` (scheduled every minute)
- [x] Event: `PipelineRunStarted`, `PipelineRunCompleted`
- [x] Controller: trigger, list runs, show run, cancel, retry
- [x] Tests: orchestration logic, trigger evaluation, timeout handling
- [x] UI: run list with status badges, run detail with stage visualization

### 4.3 Pipeline Jobs

- [x] Migration: `create_pipeline_jobs_table`
- [x] Model: `PipelineJob.php` (HasStateMachine)
- [x] Enum: `PipelineJobStatus` (pending, queued, assigned, running, succeeded, failed, cancelled, timed_out, skipped)
- [x] State machine transitions (architecture doc Section 7.4)
- [x] Action: `AssignJobToRunner`, `CompletePipelineJob`, `FailPipelineJob`
- [x] Event: `PipelineJobCompleted`
- [x] Service: `LogStreamer` (append log chunks to S3, read with byte-range)
- [x] Controller: show job, stream log
- [x] UI: job cards in run detail, click-to-expand log viewer

### 4.4 Runners

- [x] Migration: `create_runners_table`
- [x] Model: `Runner.php`
- [x] Enum: `RunnerStatus` (online, offline, busy, draining)
- [x] Action: `RegisterRunner` (generate token, return plaintext once)
- [x] Middleware: `AuthenticateRunner` (validate runner token)
- [x] Controller: `app/Http/Controllers/Api/Runner/RunnerController.php` (jobs/next, jobs/{id}/status, jobs/{id}/log, jobs/{id}/artifact, heartbeat)
- [x] Update `routes/runner.php` with actual routes + middleware
- [x] Admin controller: `app/Http/Controllers/Api/Pipeline/RunnerController.php` (index, store, show, destroy)
- [x] Contract tests: runner API request/response shapes
- [x] UI: runner list, register runner (show token once)

### 4.5 Artifacts

- [x] Migration: `create_artifacts_table`
- [x] Model: `Artifact.php` (HasStateMachine)
- [x] Enum: `ArtifactStatus` (building, ready, deployed, superseded, expired, failed)
- [x] State machine transitions (architecture doc Section 7.5)
- [x] Action: `CreateArtifact` (from runner upload — store to S3, compute hash)
- [x] Job: `CleanupOldArtifacts` (scheduled daily, apply retention policy)
- [x] Controller: list artifacts per app, show artifact detail
- [x] Tests
- [x] UI: artifact info in pipeline run detail

### 4.6 Phase 4 Completion

- [x] All tests passing, pint passing
- [x] Update CLAUDE.md + this roadmap

**Completed:** 2026-03-30
**Deliverables:** 5 migrations, 5 models, 15 actions, 25 tests, 23 API endpoints

---

## Phase 5: Deployment Engine

**Status: COMPLETE**

### 5.1 Releases

- [x] Migration: `create_releases_table`
- [x] Model: `Release.php` (HasStateMachine, config_snapshot jsonb)
- [x] Enum: `ReleaseStatus` (pending, deploying, active, superseded, rolled_back, failed)
- [x] State machine transitions (architecture doc Section 7.7)
- [x] Action: `CreateRelease` (snapshot env vars, secrets keys, processes, runtime config)
- [x] Controller, Resources
- [x] Tests

### 5.2 Deployments

- [x] Migration: `create_deployments_table`, `create_deployment_steps_table`
- [x] Model: `Deployment.php` (HasStateMachine), `DeploymentStep.php`
- [x] Enum: `DeploymentStatus` (pending, preparing, deploying, verifying, succeeded, failed, cancelled, rolled_back)
- [x] Enum: `DeploymentStrategy` (rolling, blue_green, canary)
- [x] State machine transitions (architecture doc Section 7.6)
- [x] Action: `InitiateDeployment` (create release if needed, create deployment + steps)
- [x] Action: `DeployToNode` (send command to agent)
- [x] Action: `ActivateRelease` (mark active, supersede previous)
- [x] Service: `DeploymentCoordinator` (rolling deploy logic — resolve nodes, sequence, coordinate)
- [x] Service: `RollbackManager` (automatic + manual rollback logic)
- [x] Job: `ExecuteDeployment` (on deployment queue — orchestrate full rolling deploy)
- [x] Job: `RunPostDeployHealthCheck`
- [x] Job: `ExecuteRollback`
- [x] Event: `DeploymentStarted`, `DeploymentCompleted`, `DeploymentFailed`, `RollbackCompleted`
- [x] Controller: initiate deploy, show deployment, cancel, rollback
- [x] Tests: rolling deploy coordination, health check pass/fail, rollback flow
- [x] UI: deploy button on environment detail
- [x] UI: deployment detail (per-node progress, status badges)
- [x] UI: deployment history list
- [x] UI: rollback button + release selector

### 5.3 Application Server Configuration

- [x] Migration: `create_runtime_profiles_table`
- [x] Migration: `create_server_role_profiles_table`
- [x] Model: `RuntimeProfile.php`, `ServerRoleProfile.php`
- [x] Support configurable runtime stacks per application/environment (`php-fpm`, `nginx`, `caddy`, `node`, `supervisor`)
- [x] Support role-specific install profiles for `web`, `worker`, `queue`, `db`, and `cache` nodes
- [x] Action: `CreateRuntimeProfile`, `UpdateRuntimeProfile`, `ApplyRuntimeProfile`
- [x] Action: `CreateServerRoleProfile`, `UpdateServerRoleProfile`, `ApplyServerRoleProfile`
- [x] Agent command type: `configure_runtime`
- [x] Agent command type: `configure_service`
- [x] Generate role-specific config for app servers, workers, databases, and caches
- [x] Support dedicated client stacks by assigning an environment to its own cluster and service set
- [x] Tests: runtime rendering, role profile application, dedicated-stack isolation
- [x] UI: runtime profile editor and role profile editor
- [x] UI: environment option to use shared cluster or dedicated client infrastructure

### 5.4 Health Checks

- [x] Migration: `create_health_checks_table`
- [x] Model: `HealthCheck.php`
- [x] Action: `RunHealthCheck` (HTTP GET to target, evaluate thresholds)
- [x] Configure health check per environment
- [x] UI: health check config on environment detail

### 5.5 Agent Deploy Commands

- [x] Define `deploy` command type in agent command system
- [x] Deploy command payload: artifact URL, hash, config, processes, pre/post activate hooks
- [x] Agent contract test: deploy command request/response shape
- [x] Define `rollback` command type
- [x] Agent contract test: rollback command

### 5.6 Remote Commands (Commands Tab)

- [x] Migration: `create_remote_commands_table` (ulid PK, environment_id FK, server_id FK nullable, command text, status varchar, output text nullable, exit_code int nullable, started_at, finished_at, created_at)
- [x] Model: `app/Modules/Deployment/Models/RemoteCommand.php`
- [x] Enum: `RemoteCommandStatus` (pending, running, succeeded, failed, timed_out)
- [x] Action: `ExecuteRemoteCommand` (dispatch command to environment's cluster nodes via agent command system, collect output)
- [x] Predefined command templates: run migrations, clear cache, restart workers, artisan tinker, custom command
- [x] Controller: `RemoteCommandController` (store — execute command, index — command history, show — command output)
- [x] FormRequest: `ExecuteRemoteCommandRequest` (validate command, sanitize input)
- [x] Event: `RemoteCommandExecuted` (audit logged)
- [x] Tests: command dispatch, output collection, timeout handling
- [x] UI: Commands tab on environment detail — command history list, execute command form with template picker, live output display
- [x] Security: audit log every command execution, restrict to safe commands by default with opt-in for arbitrary shell

### 5.7 Migration: add active_release_id to environments

- [x] Migration: `add_active_release_to_environments_table`
- [x] Update Environment model relationship

### 5.8 Phase 5 Completion

- [x] Integration test: full deployment workflow (create release → deploy → health check → activate)
- [x] Integration test: rollback workflow (deploy → fail → rollback)
- [x] All tests passing, pint passing
- [x] Update CLAUDE.md + this roadmap

**Completed:** 2026-03-30
**Deliverables:** 9 migrations, 7 models, 21 actions, 21 tests, 17 API endpoints

---

## Phase 6: Networking, Operations, Polish

**Status: NOT STARTED**

### 6.1 Domains

- [ ] Migration: `create_domains_table`
- [ ] Model: `Domain.php`
- [ ] Action: `AssignDomain`, `RemoveDomain`, `VerifyDomain`
- [ ] Service: `DnsVerifier` (check DNS records for verification token)
- [ ] Job: `VerifyDomain`
- [ ] Event: `DomainAssigned`, `DomainVerified`
- [ ] Controller, tests
- [ ] UI: domain list on environment detail, add domain, verify status

### 6.2 Certificates

- [ ] Migration: `create_certificates_table`
- [ ] Model: `Certificate.php`
- [ ] Enum: `CertificateStatus` (pending, active, expired, failed)
- [ ] With Caddy, certificates are mostly automatic — this tracks status
- [ ] Job: `RenewExpiringCertificates` (scheduled daily)
- [ ] UI: certificate status next to each domain

### 6.3 Caddy Proxy Config

- [ ] Service: `CaddyConfigGenerator` (generate Caddyfile from domains + environments)
- [ ] Job: `PushProxyConfig` (send config to web-role nodes via agent)
- [ ] Listener: `RegenerateProxyConfigs` on `DomainVerified`, `ClusterTopologyChanged`
- [ ] Agent command type: `update_proxy_config`

### 6.4 Bootstrap Hardening + Server Security

- [ ] Expand bootstrap script template for fresh Ubuntu 22.04/24.04 instances
- [ ] Create non-root `helm` system user with least-privilege sudo for managed operations
- [ ] Disable password SSH authentication after key install succeeds
- [ ] Disable root SSH login by default with explicit opt-out for recovery workflows
- [ ] Lock down `ufw` rules by role (`web`, `worker`, `db`, `cache`, `bastion`)
- [ ] Bind Postgres/MySQL/Redis to private interfaces by default
- [ ] Install and configure `fail2ban` baseline for SSH
- [ ] Configure unattended security upgrades
- [ ] Write agent config to `/etc/helm/agent.conf` with locked-down permissions
- [ ] Rotate agent tokens and support server rekey workflow
- [ ] Push operator SSH keys to managed hosts and support authorized key rotation
- [ ] Add server hardening audit/check action to detect drift from baseline
- [ ] Tests: bootstrap hardening on clean Ubuntu images, SSH lockout prevention, role firewall rules

### 6.5 Backups + Recovery

- [ ] Migration: `create_backups_table`
- [ ] Model, Enum (`BackupStatus`), State machine
- [ ] Action: `CreateBackup`, `RestoreBackup`
- [ ] Support database backups for both PostgreSQL and MySQL
- [ ] Support file backups for application volumes and uploaded assets
- [ ] Support restore verification workflow before marking backup healthy
- [ ] Job: `ExecuteBackup`, `ApplyRetentionPolicy`
- [ ] Controller, tests
- [ ] UI: backup list, trigger backup, restore

### 6.6 Dashboard

- [ ] Real data for dashboard cards: server count by status, cluster health, app count
- [ ] Recent deployments list (last 10)
- [ ] Recent pipeline runs list (last 10)
- [ ] Service overview cards: database instances, cache instances, backup status
- [ ] Wire up all navigation links

### 6.7 Polish

- [ ] Error pages (404, 500, 503)
- [ ] Loading states and skeleton screens
- [ ] Toast notifications for async operations
- [ ] Empty states for all list pages
- [ ] Responsive sidebar
- [ ] Keyboard shortcuts (post-MVP)

### 6.8 Phase 6 Completion

- [ ] Full E2E dedicated client flow works: provision cluster → choose Postgres/MySQL → bind cache → deploy → backup → restore test
- [ ] Full E2E flow works: register server → bootstrap → cluster → app → pipeline → deploy → domain + SSL
- [ ] All tests passing, pint passing
- [ ] Update CLAUDE.md to mark MVP complete
- [ ] Update this roadmap

---

## Phase 7: Observability & Monitoring

**Status: NOT STARTED**

### 7.1 Server Metrics Collection

- [ ] Migration: `create_server_metrics_table` (bigint PK auto-increment, server_id ulid FK, cpu_percent, memory_percent, disk_percent, load_avg_1m, load_avg_5m, network_rx_bytes, network_tx_bytes, recorded_at timestamp)
- [ ] Index: (server_id, recorded_at DESC)
- [ ] Model: `app/Modules/Observability/Models/ServerMetric.php` (bigint PK, no HasUlid, belongsTo Server)
- [ ] Update agent heartbeat handler to persist metrics to `server_metrics` table (heartbeat already sends cpu/mem/disk/load data)
- [ ] Action: `RecordServerMetrics` (store metrics from heartbeat payload)
- [ ] Job: `RollupServerMetrics` (scheduled hourly — downsample 1-min data older than 24h to 5-min, 5-min data older than 7d to 1-hour, drop data older than 90d)
- [ ] Controller: `ServerMetricController` (index — query by server_id, time range, resolution)
- [ ] Resource: `ServerMetricResource`
- [ ] Tests: metric recording, rollup logic, API filtering by time range

### 7.2 Alert Rules & Alerts

- [ ] Migration: `create_alert_rules_table` (ulid PK, name, target_type enum server/cluster, target_id nullable, metric varchar, operator varchar, threshold decimal, duration_seconds int, severity varchar, notification_channels jsonb, is_active bool, timestamps)
- [ ] Migration: `create_alerts_table` (ulid PK, alert_rule_id FK, target_type, target_id, metric, value decimal, threshold decimal, severity varchar, status varchar, acknowledged_at nullable, resolved_at nullable, created_at, updated_at)
- [ ] Model: `app/Modules/Observability/Models/AlertRule.php`
- [ ] Model: `app/Modules/Observability/Models/Alert.php` (HasStateMachine)
- [ ] Enum: `AlertSeverity` (info, warning, critical)
- [ ] Enum: `AlertStatus` (firing, acknowledged, resolved)
- [ ] Action: `CreateAlertRule`, `UpdateAlertRule`, `DeleteAlertRule`
- [ ] Action: `EvaluateAlertRules` (check recent metrics against thresholds, fire alerts)
- [ ] Action: `AcknowledgeAlert`, `ResolveAlert`
- [ ] Event: `AlertFired`, `AlertResolved`
- [ ] Job: `EvaluateAlertRules` (scheduled every minute — evaluate all active rules against recent metrics)
- [ ] Controller: `AlertRuleController` (CRUD), `AlertController` (index, show, acknowledge, resolve)
- [ ] FormRequests, Resources
- [ ] Tests: rule evaluation logic, threshold breach detection, alert lifecycle

### 7.3 Server Metrics Dashboard

- [ ] UI: server detail metrics page — time-range selector (1h, 6h, 24h, 7d, 30d), charts for CPU, memory, disk, load, network (use Recharts)
- [ ] UI: dashboard health grid — colored tiles (green/yellow/red) per server showing current CPU/memory/disk at a glance, click to drill into server detail
- [ ] UI: dashboard deployment timeline — last 24h of deploys across all environments, success/failure color-coded
- [ ] UI: dashboard active alerts banner — top of page, dismissible, link to alert detail
- [ ] UI: cluster overview — aggregate resource utilization sparklines for each cluster
- [ ] UI: alert rules management page (CRUD)
- [ ] UI: alerts list with severity badges, acknowledge/resolve actions

### 7.4 Phase 7 Completion

- [ ] All tests passing, pint passing
- [ ] Update CLAUDE.md + this roadmap

---

## Phase 8: Log Management & Search

**Status: NOT STARTED**

### 8.1 Structured Log Storage

- [ ] Update `LogStreamer` service to store logs as structured JSONL (timestamp, level, message, source, metadata) instead of raw text
- [ ] Migration: `create_log_indexes_table` (ulid PK, source_type varchar (pipeline_job, deployment, server), source_id ulid, s3_path varchar, byte_offset_start bigint, byte_offset_end bigint, line_count int, min_timestamp, max_timestamp, created_at)
- [ ] Model: `app/Modules/Observability/Models/LogIndex.php`
- [ ] Action: `AppendLogChunk` (write JSONL to S3, create/update log index entry)
- [ ] Action: `QueryLogs` (resolve S3 chunks by time range, stream and filter)
- [ ] Service: `LogSearchService` — PostgreSQL full-text search across log content with tsvector indexing on a `log_lines` summary table, or optional Meilisearch integration for high-volume search
- [ ] Tests: JSONL format, index creation, search queries

### 8.2 Log Viewer API

- [ ] Controller: `LogController` (index — paginated, search, stream)
- [ ] API: `GET /api/v1/logs?source_type=pipeline_job&source_id={id}&q=error&level=error&after=...&before=...`
- [ ] Cursor-based pagination for log lines (not offset-based — logs can be huge)
- [ ] Support regex search in query parameter
- [ ] Support level filtering (debug, info, warning, error)
- [ ] FormRequest: `QueryLogsRequest` (validate source_type, date ranges, pagination cursor)
- [ ] Resource: `LogEntryResource`
- [ ] Tests: pagination, search, level filtering, time range queries

### 8.3 Real-Time Log Tailing

- [ ] Install and configure Laravel Reverb for WebSocket support
- [ ] Broadcasting: `LogChunkAppended` event broadcast on private channel per source (e.g., `log.pipeline_job.{id}`)
- [ ] Update `AppendLogChunk` action to broadcast new chunks
- [ ] Frontend WebSocket client for live log following
- [ ] Graceful fallback to polling if WebSocket unavailable
- [ ] Tests: broadcast event shape, channel authorization

### 8.4 CloudWatch-Style Log Viewer UI

- [ ] UI: log viewer component with virtual scrolling (react-window) — render only visible lines, fetch pages on scroll
- [ ] UI: search bar with regex support, highlight matches, jump-to-next/prev match
- [ ] UI: level filter toggles (debug, info, warning, error) with color-coded lines
- [ ] UI: time range picker (absolute and relative — "last 1h", "last 24h", custom range)
- [ ] UI: collapsible log groups by pipeline stage or deployment step
- [ ] UI: download full log as file button
- [ ] UI: shareable permalink to specific line ranges (e.g., `/logs/pipeline-job/{id}?line=142-158`)
- [ ] UI: auto-scroll toggle for live tail mode (scroll locked to bottom while tailing, unlock on manual scroll up)
- [ ] Wire into pipeline job detail, deployment detail, and server detail pages

### 8.5 Log Retention

- [ ] Action: `ApplyLogRetention` (delete S3 objects and log_indexes older than configured retention)
- [ ] Job: `CleanupExpiredLogs` (scheduled daily)
- [ ] Config: `config/helm.php` log retention settings (default 30 days, configurable per source type)
- [ ] Tests: retention policy application

### 8.6 Phase 8 Completion

- [ ] All tests passing, pint passing
- [ ] Update CLAUDE.md + this roadmap

---

## Phase 9: Notifications & Developer Experience

**Status: NOT STARTED**

### 9.1 Notification System

- [ ] Migration: `create_notification_channels_table` (ulid PK, type varchar (email, slack, discord, webhook), name, config encrypted jsonb, is_active bool, timestamps)
- [ ] Migration: `create_notifications_table` (ulid PK, channel_id FK, type varchar, subject, body text, metadata jsonb, status varchar (pending, sent, failed), sent_at nullable, created_at)
- [ ] Model: `app/Modules/Observability/Models/NotificationChannel.php`
- [ ] Model: `app/Modules/Observability/Models/Notification.php`
- [ ] Enum: `NotificationChannelType` (email, slack, discord, webhook)
- [ ] Service: `NotificationDispatcher` (route notification to correct channel handler)
- [ ] Service: `SlackNotifier`, `DiscordNotifier`, `EmailNotifier`, `WebhookNotifier`
- [ ] Action: `CreateNotificationChannel`, `UpdateNotificationChannel`, `DeleteNotificationChannel`, `TestNotificationChannel`
- [ ] Action: `SendNotification` (create record, dispatch to channel handler)
- [ ] Listener: subscribe to `AlertFired`, `AlertResolved`, `DeploymentFailed`, `DeploymentCompleted`, `PipelineRunCompleted` — dispatch notifications based on configured rules
- [ ] Job: `DispatchNotification` (async send via queue)
- [ ] Controller: `NotificationChannelController` (CRUD + test), `NotificationController` (index — history)
- [ ] Tests: channel CRUD, dispatch routing, individual notifier output

### 9.2 Uptime Monitoring

- [ ] Migration: `create_uptime_monitors_table` (ulid PK, name, url varchar, method varchar default GET, expected_status int default 200, interval_seconds int default 60, timeout_seconds int default 10, is_active bool, last_checked_at, last_status varchar, timestamps)
- [ ] Migration: `create_uptime_checks_table` (bigint PK auto-increment, monitor_id ulid FK, status_code int nullable, response_time_ms int nullable, is_up bool, error text nullable, checked_at timestamp)
- [ ] Model: `app/Modules/Observability/Models/UptimeMonitor.php`
- [ ] Model: `app/Modules/Observability/Models/UptimeCheck.php` (bigint PK, no HasUlid — high volume)
- [ ] Action: `CreateUptimeMonitor`, `UpdateUptimeMonitor`, `DeleteUptimeMonitor`
- [ ] Action: `RunUptimeCheck` (HTTP request to URL, record response time and status)
- [ ] Job: `RunUptimeChecks` (scheduled every minute — check all active monitors whose interval has elapsed)
- [ ] Job: `RollupUptimeChecks` (scheduled daily — aggregate into hourly summaries, apply retention)
- [ ] Event: `UptimeMonitorDown`, `UptimeMonitorRecovered` (triggers notifications)
- [ ] Controller: `UptimeMonitorController` (CRUD + checks history)
- [ ] Tests: check execution, down/recovery detection, rollup

### 9.3 Web Terminal

- [ ] Install xterm.js + xterm-addon-fit + xterm-addon-web-links
- [ ] Backend: WebSocket endpoint for SSH proxy (Laravel Reverb channel, authenticated)
- [ ] Service: `WebTerminalService` (open SSH connection to server via stored credentials, bridge WebSocket ↔ SSH stdin/stdout)
- [ ] Action: `OpenWebTerminal` (validate server is active, audit log the session)
- [ ] UI: terminal modal/page with xterm.js, server selector, connection status indicator
- [ ] UI: one-click "SSH" button on server detail page and server list
- [ ] Security: audit log all terminal sessions (open, close, duration), require active server status
- [ ] Tests: connection lifecycle, auth verification, audit logging

### 9.4 Notification Preferences UI

- [ ] UI: notification channels management page (add Slack webhook, Discord webhook, email, custom webhook)
- [ ] UI: test notification button per channel
- [ ] UI: notification rules — which events trigger which channels (e.g., "send deploy failures to Slack, all alerts to email")
- [ ] UI: notification history page with status badges

### 9.5 Uptime Monitoring UI

- [ ] UI: uptime monitors list (name, URL, current status, uptime percentage, last response time)
- [ ] UI: uptime monitor detail — response time chart, uptime percentage over time, incident history
- [ ] UI: create/edit monitor form
- [ ] UI: dashboard uptime widget — small status indicators for each monitor

### 9.6 Phase 9 Completion

- [ ] All tests passing, pint passing
- [ ] Update CLAUDE.md + this roadmap

---

## Phase 10: Server Virtualization (LXC Containers)

**Status: NOT STARTED**

### 10.1 Host Server Model

- [ ] Migration: add `parent_server_id` (ulid FK nullable, self-referencing) to `servers` table
- [ ] Migration: add `resource_allocation` (jsonb nullable) to `servers` table — `{"cpus": 8, "memory_mb": 16384, "disk_gb": 50}`
- [ ] Migration: add `container_id` (varchar nullable) to `servers` table — LXC container name on host
- [ ] Migration: add `virtualization_enabled` (bool default false) to `servers` table
- [ ] Update Server model: `children()` hasMany(Server, 'parent_server_id'), `parentServer()` belongsTo(Server, 'parent_server_id')
- [ ] Scope: `scopeHosts()` — servers with no parent (physical/VPS), `scopeContainers()` — servers with a parent
- [ ] Scope: `scopeOnHost($hostId)` — all containers on a given host
- [ ] Validation: container resource_allocation cannot exceed host's total resources minus other containers' allocations
- [ ] Tests: model relationships, scopes, resource allocation validation

### 10.2 LXC Management Service

- [ ] Service: `app/Modules/Infrastructure/Services/LxcManager.php`
    - `createContainer(Server $host, CreateContainerData $data): string` — returns container ID
    - `destroyContainer(Server $host, string $containerId): void`
    - `resizeContainer(Server $host, string $containerId, ResourceAllocation $data): void`
    - `startContainer(Server $host, string $containerId): void`
    - `stopContainer(Server $host, string $containerId): void`
    - `listContainers(Server $host): array`
    - `getContainerStatus(Server $host, string $containerId): array`
- [ ] DTO: `CreateContainerData` (name, cpus, memory_mb, disk_gb, os_template default ubuntu-24.04)
- [ ] DTO: `ResourceAllocation` (cpus, memory_mb, disk_gb)
- [ ] All operations execute via agent commands on the host server
- [ ] Tests: mock agent commands, validate LXC command generation

### 10.3 Container Lifecycle Actions

- [ ] Action: `CreateVirtualServer` — validate resource availability on host, send LXC create command via agent, create server record with parent_server_id, bootstrap agent inside container
- [ ] Action: `ResizeVirtualServer` — validate new allocation fits, send LXC resize command, update resource_allocation
- [ ] Action: `DestroyVirtualServer` — drain from clusters, send LXC destroy command, soft-delete server record
- [ ] Action: `MigrateVirtualServer` — move container from one host to another (LXC live migration or stop-copy-start)
- [ ] Job: `CreateVirtualServer` (async — LXC creation + bootstrap can take minutes)
- [ ] Job: `MigrateVirtualServer` (async)
- [ ] Event: `VirtualServerCreated`, `VirtualServerResized`, `VirtualServerMigrated`
- [ ] Tests: full lifecycle, resource limit enforcement, migration flow

### 10.4 Agent LXC Commands

- [ ] Define `lxc_create` command type — payload: container name, OS template, resource limits, network config
- [ ] Define `lxc_destroy` command type — payload: container ID
- [ ] Define `lxc_resize` command type — payload: container ID, new limits
- [ ] Define `lxc_start` / `lxc_stop` command types
- [ ] Define `lxc_status` command type — returns container resource usage, state
- [ ] Agent contract tests for all LXC command types
- [ ] Host agent must have LXC/LXD installed and configured during bootstrap

### 10.5 Container Networking

- [ ] Agent: configure LXC bridge networking — each container gets its own IP on a private bridge
- [ ] Agent: set up NAT/port forwarding for containers that need public access
- [ ] Agent: configure firewall rules between containers on the same host (isolation by default)
- [ ] Support private networking between containers on the same host (e.g., web container → db container via private IP)
- [ ] Store container IPs in server record (`public_ip` for forwarded, `private_ip` for bridge)
- [ ] Tests: network isolation verification, inter-container communication

### 10.6 Resource Monitoring for Containers

- [ ] Agent: report per-container resource usage in heartbeat (LXC provides cgroup stats)
- [ ] Update `RecordServerMetrics` action to handle container metrics from host agent heartbeat
- [ ] UI: host server detail shows resource breakdown — total capacity, per-container allocation, per-container actual usage
- [ ] UI: visual resource allocation bar (used / allocated / free) per resource type (CPU, memory, disk)
- [ ] Alert rule support: alert when a container approaches its resource limit

### 10.7 Host Bootstrap Enhancement

- [ ] Update `ServerBootstrapper` to optionally install LXC/LXD on host servers marked for virtualization
- [ ] LXD init with storage pool (ZFS or dir backend), network bridge, default profile
- [ ] Pre-configure OS image cache (ubuntu:24.04) so container creation is fast
- [ ] Support enabling virtualization on existing active servers (install LXC without disrupting running services)

### 10.8 Virtual Server UI

- [ ] UI: host server detail — "Virtual Servers" section showing containers with resource bars (CPU, memory, disk usage vs allocation)
- [ ] UI: create virtual server form — select host, name, OS template, resource sliders (CPU cores, memory GB, disk GB) with validation against available capacity
- [ ] UI: resize virtual server modal — adjust resource sliders, show impact on host available capacity
- [ ] UI: server list — distinguish hosts vs containers with icon/badge, show parent host name for containers
- [ ] UI: host capacity overview on dashboard — per-host utilization donut charts
- [ ] UI: migrate container modal — select destination host, show resource compatibility

### 10.9 Phase 10 Completion

- [ ] Integration test: create host → enable virtualization → create containers → bootstrap agents → assign to cluster → deploy app
- [ ] Integration test: resize container, verify resource limits applied
- [ ] Integration test: destroy container, verify cleanup
- [ ] All tests passing, pint passing
- [ ] Update CLAUDE.md + this roadmap

---

## Post-MVP Backlog

These are tracked here for reference but are NOT part of the current build plan.

- [ ] Multiple git providers (GitLab, Bitbucket)
- [ ] Multiple cloud providers (Hetzner, Vultr, AWS)
- [ ] Artifact promotion between environments
- [ ] Blue-green deployments
- [ ] Canary deployments
- [ ] MFA enforcement
- [ ] Pipeline caching
- [ ] Pipeline concurrency control
- [ ] Multiple runners / runner pools
- [ ] Preview environments (auto-create per PR)
- [ ] Maintenance mode workflows
- [ ] Scheduled deployments
- [ ] Manual approval gates in pipelines
- [ ] Cost tracking — pull monthly spend from cloud provider APIs, display on dashboard
- [ ] Server comparison view — overlay metrics from multiple servers on same chart
- [ ] Log analytics — aggregate error rates, trending log patterns
- [ ] Mobile-responsive dashboard for on-the-go monitoring
