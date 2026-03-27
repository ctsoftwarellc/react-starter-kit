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
**Status: NOT STARTED**

### 1.1 Personal Access Tokens
- [ ] Migration: `create_personal_access_tokens_table` (ulid PK, user_id, name, token hash, abilities jsonb, last_used_at, expires_at)
- [ ] Model: `app/Models/PersonalAccessToken.php` (stays at root, used by auth guard)
- [ ] Action: `CreatePersonalAccessToken` (hash token, return plaintext once)
- [ ] Action: `RevokePersonalAccessToken`
- [ ] Middleware: `AuthenticateWithToken` (Bearer token lookup, set auth user)
- [ ] Register middleware in `bootstrap/app.php` for API routes
- [ ] Controller: `app/Http/Controllers/Api/PersonalAccessTokenController.php` (index, store, destroy)
- [ ] FormRequest: `CreateTokenRequest`
- [ ] Resource: `PersonalAccessTokenResource`
- [ ] Factory: `PersonalAccessTokenFactory`
- [ ] Feature test: token CRUD endpoints
- [ ] Feature test: API auth via token
- [ ] UI: tokens list page under settings
- [ ] UI: create token modal (show plaintext once)
- [ ] UI: revoke token button

### 1.2 SSH Keys
- [ ] Migration: `create_ssh_keys_table` (ulid PK, user_id, name, public_key text, fingerprint unique)
- [ ] Model: `app/Models/SshKey.php` (stays at root)
- [ ] Action: `AddSshKey` (compute fingerprint from public key, validate format)
- [ ] Action: `RemoveSshKey`
- [ ] Controller: `app/Http/Controllers/Api/SshKeyController.php` (index, store, destroy)
- [ ] FormRequest: `AddSshKeyRequest` (validate public key format)
- [ ] Resource: `SshKeyResource`
- [ ] Factory: `SshKeyFactory`
- [ ] Feature test: SSH key CRUD
- [ ] Feature test: duplicate fingerprint rejection
- [ ] UI: SSH keys list page under settings
- [ ] UI: add key form, delete button

### 1.3 Projects
- [ ] Migration: `create_projects_table` (ulid PK, name, slug unique, description nullable, timestamps, soft_deletes)
- [ ] Model: `app/Modules/AppPlatform/Models/Project.php`
- [ ] Action: `CreateProject` (generate slug from name)
- [ ] Action: `UpdateProject`
- [ ] Action: `DeleteProject` (soft delete)
- [ ] Event: `ProjectCreated`
- [ ] Controller: `app/Http/Controllers/Api/AppPlatform/ProjectController.php` (index, store, show, update, destroy)
- [ ] FormRequest: `CreateProjectRequest`, `UpdateProjectRequest`
- [ ] Resource: `ProjectResource`
- [ ] Factory: `ProjectFactory`
- [ ] Feature test: project CRUD
- [ ] Feature test: slug uniqueness
- [ ] UI: projects list page (cards with name, description, app count)
- [ ] UI: create project modal
- [ ] UI: project detail page (will hold apps later)

### 1.4 Audit Logs
- [ ] Migration: `create_audit_logs_table` (ulid PK, user_id nullable, action varchar, auditable_type, auditable_id, old_values jsonb, new_values jsonb, ip_address inet, user_agent text, created_at — NO updated_at)
- [ ] Model: `app/Modules/Operations/Models/AuditLog.php` (no HasUlid timestamps override needed — only created_at)
- [ ] Action: `RecordAuditLog`
- [ ] Listener: `app/Modules/Operations/Listeners/RecordAuditLog.php` (listens to all domain events)
- [ ] Register listener in `EventServiceProvider` or via event discovery
- [ ] Controller: `app/Http/Controllers/Api/Operations/AuditLogController.php` (index only — read-only)
- [ ] Resource: `AuditLogResource`
- [ ] Feature test: audit log creation on events
- [ ] Feature test: audit log list endpoint with filters
- [ ] UI: activity feed page (chronological list, filterable)

### 1.5 Sidebar Navigation + Dashboard
- [ ] Update Inertia layout with sidebar navigation matching architecture (Dashboard, Servers, Clusters, Projects, Runners, Activity, Settings)
- [ ] Dashboard page: placeholder cards for servers, apps, recent deploys, recent pipelines
- [ ] Settings layout with sub-nav: Profile, SSH Keys, API Tokens
- [ ] Wire up existing profile/security settings pages into new layout
- [ ] Move provider settings into Settings section (placeholder for Phase 2)

### 1.6 Phase 1 Completion
- [ ] All new tests passing
- [ ] Pint passing
- [ ] Update CLAUDE.md phase table to mark Phase 1 complete
- [ ] Update this roadmap

---

## Phase 2: Infrastructure
**Status: NOT STARTED**

### 2.1 Providers
- [ ] Migration: `create_providers_table` (ulid PK, name, type varchar, credentials encrypted text, is_active bool)
- [ ] Model: `app/Modules/Infrastructure/Models/Provider.php`
- [ ] Enum: `ProviderType` (digitalocean, hetzner, vultr, aws, manual)
- [ ] Action: `CreateProvider`, `UpdateProvider`, `DeleteProvider`, `TestProviderConnection`
- [ ] Service interface: `app/Modules/Infrastructure/Services/Providers/ProviderInterface.php`
- [ ] Service: `DigitalOceanProvider.php` (list regions, list sizes, create droplet, delete droplet)
- [ ] Service: `ManualProvider.php` (no-op — manual servers don't use provider API)
- [ ] Controller: `ProviderController` (index, store, update, destroy, test)
- [ ] FormRequest: `CreateProviderRequest`, `UpdateProviderRequest`
- [ ] Resource: `ProviderResource` (NEVER expose credentials)
- [ ] Factory: `ProviderFactory`
- [ ] Feature tests: CRUD, test connection
- [ ] UI: provider management in settings

### 2.2 Servers
- [ ] Migration: `create_servers_table` (full schema from architecture doc Section 6)
- [ ] Model: `app/Modules/Infrastructure/Models/Server.php` (HasStateMachine, relationships, casts)
- [ ] Enum: `ServerStatus` (pending, provisioning, bootstrapping, active, draining, cordoned, maintenance, decommissioning, decommissioned, failed)
- [ ] Enum: `NodeRole` (web, worker, db, cache, queue, bastion)
- [ ] State machine: transitions map in Server model (from architecture doc Section 7.1)
- [ ] Action: `RegisterServer` (manual registration — IP, SSH creds, name)
- [ ] Action: `ProvisionServer` (create via provider API)
- [ ] Action: `DrainNode`, `CordonNode`, `ActivateNode`, `DecommissionNode`
- [ ] Event: `ServerRegistered`, `ServerBootstrapped`, `ServerHealthChanged`
- [ ] DTO: `RegisterServerData`
- [ ] Controller: `ServerController` (index, store, show, update, destroy + bootstrap/drain/cordon/activate)
- [ ] FormRequests, Resources
- [ ] Factory: `ServerFactory`
- [ ] Unit tests: state machine transitions (all valid + all invalid)
- [ ] Feature tests: server CRUD, state transitions
- [ ] UI: server list (table with status, IP, cluster, roles, heartbeat)
- [ ] UI: server detail (status badge, actions, metadata)
- [ ] UI: register server form

### 2.3 SSH Bootstrap
- [ ] Service: `app/Modules/Infrastructure/Services/SshService.php` (connect, execute command, upload file, disconnect)
- [ ] Service: `app/Modules/Infrastructure/Services/ServerBootstrapper.php` (orchestrate full bootstrap sequence)
- [ ] Action: `BootstrapServer` (generate agent token, call bootstrapper, update status)
- [ ] Job: `app/Modules/Infrastructure/Jobs/BootstrapServer.php` (async, on infrastructure queue)
- [ ] Job: `app/Modules/Infrastructure/Jobs/ProvisionServer.php` (create via provider API, then bootstrap)
- [ ] Job: `app/Modules/Infrastructure/Jobs/PushSshKeys.php` (update authorized_keys on server)
- [ ] Bootstrap script template (install packages, configure firewall, create helm user, install agent)
- [ ] Feature test: bootstrap job (mock SSH)
- [ ] UI: bootstrap button on server detail, progress indication

### 2.4 Clusters
- [ ] Migration: `create_clusters_table`, `create_cluster_node_table`
- [ ] Model: `app/Modules/Infrastructure/Models/Cluster.php` (HasStateMachine)
- [ ] Enum: `ClusterStatus` (pending, provisioning, active, updating, scaling, degraded, maintenance, decommissioning, decommissioned)
- [ ] State machine: transitions in Cluster model (from architecture doc Section 7.2)
- [ ] Action: `CreateCluster`, `UpdateCluster`, `DeleteCluster`
- [ ] Action: `AddNodeToCluster`, `RemoveNodeFromCluster`, `UpdateNodeRole`
- [ ] Event: `ClusterTopologyChanged`
- [ ] Controller: `ClusterController` (CRUD + node management endpoints)
- [ ] FormRequests, Resources
- [ ] Factory: `ClusterFactory`
- [ ] Unit tests: cluster state machine
- [ ] Feature tests: cluster CRUD, node assignment
- [ ] UI: cluster list (name, status, node count by role)
- [ ] UI: cluster detail (node list, add/remove nodes, role assignment)

### 2.5 Agent API
- [ ] Middleware: `AuthenticateAgent` (validate server agent token from Bearer header)
- [ ] Migration or column: `agent_commands` table (id, server_id, type, payload jsonb, status, result jsonb, expires_at, created_at, completed_at)
- [ ] Controller: `app/Http/Controllers/Api/Agent/AgentController.php` (heartbeat, commands/pending, commands/{id}/result)
- [ ] Update `routes/agent.php` with actual routes + middleware
- [ ] Heartbeat handler: update server `last_heartbeat_at`, store basic metrics
- [ ] Command queue: create commands for agents, agents poll and execute
- [ ] Contract tests: agent API request/response shapes

### 2.6 Phase 2 Completion
- [ ] All tests passing
- [ ] Pint passing
- [ ] Update CLAUDE.md + this roadmap

---

## Phase 3: Applications, Environments, Secrets
**Status: NOT STARTED**

### 3.1 Git Connections
- [ ] Migration: `create_git_connections_table`
- [ ] Model: `GitConnection.php`
- [ ] GitHub OAuth flow (authorize, callback, store tokens)
- [ ] Action: `CreateGitConnection`, `DeleteGitConnection`
- [ ] Controller + routes for OAuth flow
- [ ] UI: connect GitHub account in settings or app creation

### 3.2 Applications
- [ ] Migration: `create_applications_table`
- [ ] Model: `Application.php` (belongsTo Project, GitConnection; hasMany Environment, Pipeline)
- [ ] Enum: `Runtime` (php, node, python, go)
- [ ] Action: `CreateApplication`, `UpdateApplication`, `DeleteApplication`
- [ ] Event: `ApplicationCreated` → listener creates default production environment
- [ ] Controller, FormRequests, Resources
- [ ] Factory
- [ ] Tests
- [ ] UI: app list within project, app creation form, app detail with tabs

### 3.3 Environments
- [ ] Migration: `create_environments_table`
- [ ] Model: `Environment.php` (belongsTo Application, Cluster; hasMany variables, secrets, processes, domains, deployments)
- [ ] Enum: `EnvironmentType` (production, staging, preview)
- [ ] Action: `CreateEnvironment`, `UpdateEnvironment`, `DeleteEnvironment`
- [ ] Controller, FormRequests, Resources
- [ ] Factory
- [ ] Tests
- [ ] UI: environment list in app detail, environment detail page

### 3.4 Environment Variables
- [ ] Migration: `create_environment_variables_table`
- [ ] Model, Action (SetEnvironmentVariable, DeleteEnvironmentVariable), Controller
- [ ] UI: inline key-value editor on environment detail page

### 3.5 Secrets
- [ ] Migration: `create_secrets_table`
- [ ] Model: `Secret.php` (encrypted_value cast)
- [ ] Action: `CreateSecret`, `UpdateSecret`, `DeleteSecret`, `RevealSecret` (audit logged)
- [ ] Controller (CRUD + reveal endpoint)
- [ ] Tests: ensure values never leaked in normal API responses
- [ ] UI: secret key list, reveal button, create/edit/delete

### 3.6 Process Definitions
- [ ] Migration: `create_process_definitions_table`
- [ ] Model, Enum (`ProcessType`: web, worker, scheduler, custom)
- [ ] Action, Controller
- [ ] UI: process list on environment detail

### 3.7 Webhooks
- [ ] Migration: `create_webhooks_table`
- [ ] Model: `Webhook.php`
- [ ] Action: `SetupWebhook` (register webhook on GitHub via API)
- [ ] Webhook signature verification middleware
- [ ] Update `routes/webhooks.php` with actual route
- [ ] Controller: `WebhookController` (receive, verify signature, dispatch ProcessWebhook job)
- [ ] Tests: signature verification, payload parsing

### 3.8 Phase 3 Completion
- [ ] All tests passing, pint passing
- [ ] Update CLAUDE.md + this roadmap

---

## Phase 4: Pipelines, Runners, Artifacts
**Status: NOT STARTED**

### 4.1 Pipelines
- [ ] Migration: `create_pipelines_table`
- [ ] Model: `Pipeline.php` (definition jsonb, trigger config)
- [ ] Action: `CreatePipeline`, `UpdatePipeline`, `DeletePipeline`
- [ ] Pipeline definition JSON schema validation
- [ ] Controller, FormRequests, Resources
- [ ] Tests
- [ ] UI: pipeline list in app detail, pipeline editor (JSON form builder or raw JSON)

### 4.2 Pipeline Runs
- [ ] Migration: `create_pipeline_runs_table`
- [ ] Model: `PipelineRun.php` (HasStateMachine)
- [ ] Enum: `PipelineRunStatus` (pending, running, succeeded, failed, cancelled, timed_out)
- [ ] Enum: `TriggerType` (push, tag, pull_request, manual, api, schedule)
- [ ] State machine transitions (architecture doc Section 7.3)
- [ ] Action: `TriggerPipelineRun` (snapshot definition, create run + jobs)
- [ ] Service: `PipelineOrchestrator` (stage sequencing, job status tracking)
- [ ] Job: `ProcessWebhook` (parse payload, evaluate triggers, create runs)
- [ ] Job: `OrchestrateRun` (coordinate stages, advance on job completion)
- [ ] Job: `CheckJobTimeout` (scheduled every minute)
- [ ] Event: `PipelineRunStarted`, `PipelineRunCompleted`
- [ ] Controller: trigger, list runs, show run, cancel, retry
- [ ] Tests: orchestration logic, trigger evaluation, timeout handling
- [ ] UI: run list with status badges, run detail with stage visualization

### 4.3 Pipeline Jobs
- [ ] Migration: `create_pipeline_jobs_table`
- [ ] Model: `PipelineJob.php` (HasStateMachine)
- [ ] Enum: `PipelineJobStatus` (pending, queued, assigned, running, succeeded, failed, cancelled, timed_out, skipped)
- [ ] State machine transitions (architecture doc Section 7.4)
- [ ] Action: `AssignJobToRunner`, `CompletePipelineJob`, `FailPipelineJob`
- [ ] Event: `PipelineJobCompleted`
- [ ] Service: `LogStreamer` (append log chunks to S3, read with byte-range)
- [ ] Controller: show job, stream log
- [ ] UI: job cards in run detail, click-to-expand log viewer

### 4.4 Runners
- [ ] Migration: `create_runners_table`
- [ ] Model: `Runner.php`
- [ ] Enum: `RunnerStatus` (online, offline, busy, draining)
- [ ] Action: `RegisterRunner` (generate token, return plaintext once)
- [ ] Middleware: `AuthenticateRunner` (validate runner token)
- [ ] Controller: `app/Http/Controllers/Api/Runner/RunnerController.php` (jobs/next, jobs/{id}/status, jobs/{id}/log, jobs/{id}/artifact, heartbeat)
- [ ] Update `routes/runner.php` with actual routes + middleware
- [ ] Admin controller: `app/Http/Controllers/Api/Pipeline/RunnerController.php` (index, store, show, destroy)
- [ ] Contract tests: runner API request/response shapes
- [ ] UI: runner list, register runner (show token once)

### 4.5 Artifacts
- [ ] Migration: `create_artifacts_table`
- [ ] Model: `Artifact.php` (HasStateMachine)
- [ ] Enum: `ArtifactStatus` (building, ready, deployed, superseded, expired, failed)
- [ ] State machine transitions (architecture doc Section 7.5)
- [ ] Action: `CreateArtifact` (from runner upload — store to S3, compute hash)
- [ ] Job: `CleanupOldArtifacts` (scheduled daily, apply retention policy)
- [ ] Controller: list artifacts per app, show artifact detail
- [ ] Tests
- [ ] UI: artifact info in pipeline run detail

### 4.6 Phase 4 Completion
- [ ] All tests passing, pint passing
- [ ] Update CLAUDE.md + this roadmap

---

## Phase 5: Deployment Engine
**Status: NOT STARTED**

### 5.1 Releases
- [ ] Migration: `create_releases_table`
- [ ] Model: `Release.php` (HasStateMachine, config_snapshot jsonb)
- [ ] Enum: `ReleaseStatus` (pending, deploying, active, superseded, rolled_back, failed)
- [ ] State machine transitions (architecture doc Section 7.7)
- [ ] Action: `CreateRelease` (snapshot env vars, secrets keys, processes, runtime config)
- [ ] Controller, Resources
- [ ] Tests

### 5.2 Deployments
- [ ] Migration: `create_deployments_table`, `create_deployment_steps_table`
- [ ] Model: `Deployment.php` (HasStateMachine), `DeploymentStep.php`
- [ ] Enum: `DeploymentStatus` (pending, preparing, deploying, verifying, succeeded, failed, cancelled, rolled_back)
- [ ] Enum: `DeploymentStrategy` (rolling, blue_green, canary)
- [ ] State machine transitions (architecture doc Section 7.6)
- [ ] Action: `InitiateDeployment` (create release if needed, create deployment + steps)
- [ ] Action: `DeployToNode` (send command to agent)
- [ ] Action: `ActivateRelease` (mark active, supersede previous)
- [ ] Service: `DeploymentCoordinator` (rolling deploy logic — resolve nodes, sequence, coordinate)
- [ ] Service: `RollbackManager` (automatic + manual rollback logic)
- [ ] Job: `ExecuteDeployment` (on deployment queue — orchestrate full rolling deploy)
- [ ] Job: `RunPostDeployHealthCheck`
- [ ] Job: `ExecuteRollback`
- [ ] Event: `DeploymentStarted`, `DeploymentCompleted`, `DeploymentFailed`, `RollbackCompleted`
- [ ] Controller: initiate deploy, show deployment, cancel, rollback
- [ ] Tests: rolling deploy coordination, health check pass/fail, rollback flow
- [ ] UI: deploy button on environment detail
- [ ] UI: deployment detail (per-node progress, status badges)
- [ ] UI: deployment history list
- [ ] UI: rollback button + release selector

### 5.3 Health Checks
- [ ] Migration: `create_health_checks_table`
- [ ] Model: `HealthCheck.php`
- [ ] Action: `RunHealthCheck` (HTTP GET to target, evaluate thresholds)
- [ ] Configure health check per environment
- [ ] UI: health check config on environment detail

### 5.4 Agent Deploy Commands
- [ ] Define `deploy` command type in agent command system
- [ ] Deploy command payload: artifact URL, hash, config, processes, pre/post activate hooks
- [ ] Agent contract test: deploy command request/response shape
- [ ] Define `rollback` command type
- [ ] Agent contract test: rollback command

### 5.5 Migration: add active_release_id to environments
- [ ] Migration: `add_active_release_to_environments_table`
- [ ] Update Environment model relationship

### 5.6 Phase 5 Completion
- [ ] Integration test: full deployment workflow (create release → deploy → health check → activate)
- [ ] Integration test: rollback workflow (deploy → fail → rollback)
- [ ] All tests passing, pint passing
- [ ] Update CLAUDE.md + this roadmap

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

### 6.4 Backups (post-MVP stretch)
- [ ] Migration: `create_backups_table`
- [ ] Model, Enum (`BackupStatus`), State machine
- [ ] Action: `CreateBackup`, `RestoreBackup`
- [ ] Job: `ExecuteBackup`, `ApplyRetentionPolicy`
- [ ] Controller, tests
- [ ] UI: backup list, trigger backup, restore

### 6.5 Dashboard
- [ ] Real data for dashboard cards: server count by status, cluster health, app count
- [ ] Recent deployments list (last 10)
- [ ] Recent pipeline runs list (last 10)
- [ ] Wire up all navigation links

### 6.6 Polish
- [ ] Error pages (404, 500, 503)
- [ ] Loading states and skeleton screens
- [ ] Toast notifications for async operations
- [ ] Empty states for all list pages
- [ ] Responsive sidebar
- [ ] Keyboard shortcuts (post-MVP)

### 6.7 Phase 6 Completion
- [ ] Full E2E flow works: register server → bootstrap → cluster → app → pipeline → deploy → domain + SSL
- [ ] All tests passing, pint passing
- [ ] Update CLAUDE.md to mark MVP complete
- [ ] Update this roadmap

---

## Post-MVP Backlog

These are tracked here for reference but are NOT part of the MVP build.

- [ ] Multiple git providers (GitLab, Bitbucket)
- [ ] Multiple cloud providers (Hetzner, Vultr, AWS)
- [ ] Artifact promotion between environments
- [ ] Blue-green deployments
- [ ] Canary deployments
- [ ] Database provisioning workflows
- [ ] Redis provisioning
- [ ] Alert rules and notifications
- [ ] MFA enforcement
- [ ] Pipeline caching
- [ ] Pipeline concurrency control
- [ ] Node metrics dashboard (CPU, memory, disk charts)
- [ ] Multiple runners / runner pools
- [ ] Preview environments (auto-create per PR)
- [ ] Maintenance mode workflows
- [ ] Scheduled deployments
- [ ] Manual approval gates in pipelines
- [ ] Server metrics table + TimescaleDB
- [ ] WebSocket/SSE for real-time log streaming
