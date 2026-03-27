# Helm — Platform Architecture Document

> Personal self-hosted VPS cluster management and application delivery platform
> Single-user tool — NOT multi-tenant SaaS
> Laravel 13 · Inertia v3 + React/TS · PostgreSQL · Redis

---

## SECTION 1 — PRODUCT DECOMPOSITION

### 1.1 Auth & User

**Purpose:** Authentication, API tokens, and SSH key management for the platform operator.

**Responsibilities:**
- User login, password reset, MFA
- Personal access tokens for API auth (used by CLI, scripts)
- SSH key management
- User profile/settings

**Key Entities:** User, PersonalAccessToken, SshKey

**Key Workflows:**
- Login → session-based web access
- Create API token → use for agent/runner/CLI auth
- Upload SSH key → pushed to managed servers

**Does NOT own:** Server SSH keys (that's Infrastructure), deployment approvals (that's Deployment), audit log storage (that's Operations).

**Notes:** No organizations, teams, memberships, RBAC, or invitation flows. This is a personal tool. Auth is "is authenticated" — if you're logged in, you have full access. If multi-user is ever needed later, it's a simple `users` table expansion, not a multi-tenant rewrite.

---

### 1.2 Infrastructure

**Purpose:** Server lifecycle, cluster topology, and node management.

**Responsibilities:**
- Provider credential storage (DO, Hetzner, Vultr, AWS, manual)
- Server/node registration (manual or API-provisioned)
- Server bootstrap via SSH (install dependencies, configure firewall, install agent)
- Cluster creation and topology management
- Node role assignment (web, worker, db, cache, queue, bastion)
- Node health tracking via agent heartbeats
- Drain, cordon, maintenance mode workflows
- Node decommissioning
- Agent registration and authentication

**Key Entities:** Provider, Server, Cluster, ClusterNode, Agent, ServerMetric

**Key Workflows:**
- Register provider credentials → create server → bootstrap → join cluster → assign roles
- Health degradation → alert → drain → maintenance → replace → rejoin
- Cluster scaling: add node → bootstrap → join → rebalance
- Rolling update: cordon node → update → uncordon → next node

**Does NOT own:** Application deployment logic, CI/CD, domain management, database provisioning.

---

### 1.3 Application Platform

**Purpose:** Application definitions, environments, configuration, and git integration.

**Responsibilities:**
- Application CRUD within projects
- Git repository connection (GitHub, GitLab, Bitbucket)
- Environment definitions (production, staging, preview)
- Environment variable management
- Encrypted secrets
- Process definitions (web, worker, scheduler, queue)
- Runtime configuration (PHP version, Node version, etc.)
- Application-to-cluster binding

**Key Entities:** Project, Application, Environment, EnvironmentVariable, Secret, ProcessDefinition, GitConnection

**Key Workflows:**
- Create app → connect repo → define environments → set env vars → configure processes
- Environment promotion: staging config → production config
- Secret rotation workflow

**Does NOT own:** Build/test/deploy execution, artifact storage, domain/SSL, server management.

---

### 1.4 Pipeline (CI/CD)

**Purpose:** Build, test, and package applications through automated pipelines.

**Responsibilities:**
- Pipeline definitions per application
- Stage and job modeling
- Webhook ingestion from git providers
- Trigger evaluation (branch, tag, PR rules)
- Job queuing and runner assignment
- Runner registration and health
- Artifact creation from successful builds
- Log capture and storage
- Timeout and retry policies
- Concurrency control

**Key Entities:** Pipeline, PipelineStage, PipelineRun, PipelineJob, Runner, Artifact, Webhook

**Key Workflows:**
- Push event → webhook → trigger evaluation → create pipeline run → queue jobs → assign to runner → execute → create artifact
- Manual trigger → same flow
- Timeout → mark failed → notify

**Does NOT own:** Deployment execution, release management, server management, domain config.

---

### 1.5 Deployment Engine

**Purpose:** Coordinate artifact deployment to clusters with safety guarantees.

**Responsibilities:**
- Release creation from artifact + config snapshot
- Deployment orchestration across cluster nodes
- Rolling deployment sequencing
- Health check verification
- Rollback execution
- Migration and queue restart coordination
- Deployment history and tracking
- Blue-green / canary preparation (architecture only for MVP)

**Key Entities:** Deployment, Release, DeploymentStep, HealthCheck

**Key Workflows:**
- Pipeline succeeds → create release → initiate deployment → rolling deploy per node → health check → activate
- Health check fails → automatic rollback → mark failed → notify
- Manual rollback → create new deployment targeting previous release

**Does NOT own:** Building artifacts, pipeline execution, server bootstrapping, process management.

---

### 1.6 Networking

**Purpose:** Domain management, SSL, and reverse proxy configuration.

**Responsibilities:**
- Domain assignment to environments
- DNS verification
- Let's Encrypt certificate issuance and renewal
- Reverse proxy config generation (Caddy)
- Firewall baseline rules

**Key Entities:** Domain, Certificate

**Key Workflows:**
- Assign domain → verify DNS → issue cert → generate proxy config → push to nodes
- Certificate approaching expiry → renew → push updated config
- Remove domain → update proxy config

**Does NOT own:** Application config, server bootstrap, deployment.

---

### 1.7 Service Management

**Purpose:** Manage databases, caches, queues, and other attached services.

**Responsibilities:**
- Database provisioning workflows on db-role nodes
- Redis instance management
- Queue worker lifecycle
- Cron job scheduling
- Process supervision config generation
- Service-to-environment binding

**Key Entities:** DatabaseInstance, CacheInstance, QueueWorker, CronEntry, ServiceBinding

**Key Workflows:**
- Provision database → create user → set credentials as secrets → bind to environment
- Scale queue workers up/down
- Manage cron entries for application schedulers

**Does NOT own:** Monitoring, backup execution, deployment.

---

### 1.8 Observability

**Purpose:** Metrics collection, health monitoring, log aggregation, and alerting.

**Responsibilities:**
- Node metric collection (CPU, memory, disk, network)
- Application health checks
- Job and deployment log aggregation
- Alert rule definitions and evaluation
- Notification dispatch (email, Slack, webhook)

**Key Entities:** ServerMetric, HealthCheck, AlertRule, Alert, Notification

**Key Workflows:**
- Agent reports metrics → store → evaluate alert rules → fire alert → notify
- Health check fails → mark degraded → trigger configured response
- Scheduled metric cleanup / rollup

**Does NOT own:** Server management actions, deployment decisions, CI/CD.

---

### 1.9 Operations

**Purpose:** Backups, audit logging, maintenance windows, and operational tooling.

**Responsibilities:**
- Backup scheduling and execution
- Backup storage and retention
- Audit log recording for all mutations
- Activity stream for UI

**Key Entities:** Backup, AuditLog, Activity

**Key Workflows:**
- Scheduled backup → execute on target node → upload to object storage → record result → apply retention policy
- Every mutation → record audit log entry

**Does NOT own:** The actual operations being audited, metric collection, alerting.

---

## SECTION 2 — MVP SCOPE

### Must-Have for MVP

**Auth:**
- User login (Fortify, already set up)
- Personal access tokens for API auth
- SSH key management

**Infrastructure:**
- Manual server registration (IP, SSH credentials)
- DigitalOcean API integration (single provider for MVP)
- Server bootstrap via SSH (install packages, configure firewall, install agent)
- Cluster creation with node role assignment (web, worker, db)
- Agent installation and heartbeat
- Basic health status tracking

**Application Platform:**
- Project CRUD
- Application CRUD with git repo connection
- GitHub repo connection via OAuth + webhooks
- Production and staging environments
- Environment variables (plaintext and encrypted)
- PHP/Laravel process definitions (web via Caddy + FPM, queue worker, scheduler)

**Pipeline:**
- Pipeline definition per app (JSON stored in DB)
- GitHub webhook ingestion
- Build, test, deploy stages
- Single Docker-based runner
- Artifact creation (tar.gz upload to S3-compatible storage)
- Pipeline run logs (stored in object storage, streamed to UI)
- Basic retry (manual re-run)

**Deployment:**
- Artifact-based deployment (symlink release pattern)
- Sequential rolling deployment
- Environment/config injection at deploy time
- Artisan migrate execution
- Queue restart after deploy
- Manual rollback to previous release
- Basic health check (HTTP 200 on configured endpoint)

**Networking:**
- Domain assignment to environments
- Caddy reverse proxy config generation
- Let's Encrypt SSL via Caddy automatic HTTPS

**Operations:**
- Audit log for all mutations
- Activity feed in dashboard

**UI:**
- Dashboard (servers, apps, recent deploys, pipeline status)
- Server/cluster management screens
- Application management with environment tabs
- Pipeline run list with log viewer
- Deployment history
- Settings (profile, SSH keys, tokens)

### Should-Have After MVP

- Multiple git providers (GitLab, Bitbucket)
- Multiple cloud provider APIs (Hetzner, Vultr, AWS)
- Artifact promotion between environments
- Blue-green deployments
- Canary deployments
- Database provisioning workflows (automated MySQL/Postgres setup)
- Redis provisioning
- Backup system (database + file backups)
- Alert rules and notifications
- MFA
- Pipeline caching (dependency caches between runs)
- Pipeline concurrency control
- Node metrics dashboard (CPU, memory, disk graphs)
- Multiple runners / runner pools
- Preview environments (auto-create per PR)
- Maintenance mode workflows
- Scheduled deployments

### Explicitly Out of Scope

- Multi-tenancy / organizations / teams
- RBAC / granular permissions
- Kubernetes support
- Multi-region cluster federation
- Custom buildpack marketplace
- Billing / subscription management
- Public developer API (external consumers)
- Mobile app
- Windows server support
- Non-Docker runtime environments for CI
- Log aggregation product (use external: Loki, etc.)
- Full APM solution

---

## SECTION 3 — SYSTEM ARCHITECTURE

### 3.1 Modular Monolith

Single Laravel application with domain boundaries enforced by directory structure. All modules share one database and one deployment unit. No microservices overhead — this is a personal tool, not a distributed team's product.

```
┌─────────────────────────────────────────────────────────────────┐
│                       Laravel Application                        │
│                                                                   │
│  ┌──────────┐ ┌──────────────┐ ┌─────────────┐ ┌────────────┐  │
│  │   Auth    │ │Infrastructure│ │  AppPlatform │ │  Pipeline  │  │
│  └──────────┘ └──────────────┘ └─────────────┘ └────────────┘  │
│  ┌──────────┐ ┌──────────────┐ ┌─────────────┐ ┌────────────┐  │
│  │Deployment│ │  Networking  │ │   Service    │ │Observability│  │
│  └──────────┘ └──────────────┘ └─────────────┘ └────────────┘  │
│  ┌──────────┐                                                    │
│  │Operations│                                                    │
│  └──────────┘                                                    │
│                                                                   │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                Shared: Auth, Events, Queue, Cache          │  │
│  └───────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
         │              │                │
    ┌────┴────┐   ┌────┴─────┐   ┌─────┴──────┐
    │PostgreSQL│   │  Redis   │   │ S3/MinIO   │
    └─────────┘   └──────────┘   └────────────┘
```

### 3.2 Synchronous vs Asynchronous Workflows

**Synchronous (HTTP request/response):**
- All CRUD operations
- Configuration reads/writes
- Auth checks
- API token validation
- Dashboard data queries
- Log/metric reads

**Asynchronous (queued jobs):**
- Server provisioning and bootstrap
- Pipeline job execution dispatch
- Deployment orchestration
- Certificate issuance/renewal
- Backup execution
- Health check polling
- Metric aggregation/cleanup
- Notification dispatch
- Agent command dispatch

### 3.3 Queue Architecture

Laravel queues backed by Redis, with named queues for priority isolation:

| Queue | Purpose | Workers | Priority |
|-------|---------|---------|----------|
| `default` | General purpose | 2+ | Normal |
| `infrastructure` | Server bootstrap, agent commands | 2+ | High |
| `pipeline` | Pipeline orchestration (not execution) | 2+ | High |
| `deployment` | Deployment coordination | 2+ | Critical |
| `notifications` | Emails, webhooks | 1+ | Low |
| `maintenance` | Backups, cleanup, metric rollup | 1 | Low |

Pipeline *execution* happens on runners, not queue workers. Queue jobs dispatch work to runners and track progress.

### 3.4 Event-Driven Architecture

Events are dispatched synchronously within the request (for audit logging, cache invalidation) and asynchronously for side effects:

```
ServerBootstrapped → [UpdateClusterTopology, RecordAuditLog]
PipelineRunCompleted → [CreateArtifact, TriggerDeployment]
DeploymentCompleted → [UpdateReleaseStatus, RecordAuditLog]
HealthCheckFailed → [EvaluateAlertRules, MarkNodeDegraded]
CertificateExpiring → [RenewCertificate]
```

Events are the primary mechanism for cross-module communication. Module A dispatches an event; Module B listens.

### 3.5 CI Runners

```
┌────────────────────┐         ┌──────────────────┐
│   Laravel App      │         │   CI Runner       │
│   (Control Plane)  │◄────────│   (Docker Host)   │
│                    │         │                    │
│  GET /api/runner/  │         │  - Polls for jobs  │
│    jobs/next       │────────►│  - Pulls repo      │
│                    │         │  - Runs in Docker   │
│  POST /api/runner/ │         │  - Streams logs     │
│    jobs/{id}/log   │◄────────│  - Uploads artifact │
│                    │         │  - Reports status   │
│  PUT /api/runner/  │         │                    │
│    jobs/{id}/status│◄────────│                    │
└────────────────────┘         └──────────────────┘
```

**Communication model:** Pull-based. Runner polls control plane for available jobs. Polling interval: 5 seconds.

**Authentication:** Runner registers with the control plane and receives a long-lived token. Token is sent as Bearer token on all API calls.

**Job execution:** Runner pulls the git repo, runs commands in a Docker container, captures stdout/stderr, uploads artifacts to object storage, and reports final status.

### 3.6 Node Agents

```
┌────────────────────┐         ┌──────────────────┐
│   Laravel App      │         │   Node Agent       │
│   (Control Plane)  │◄────────│   (on each server) │
│                    │         │                    │
│  POST /api/agent/  │         │  - Heartbeat       │
│    heartbeat       │◄────────│  - Execute commands │
│                    │         │  - Report metrics   │
│  GET /api/agent/   │         │  - Deploy releases  │
│    commands/pending│────────►│  - Manage processes │
│                    │         │  - Health checks    │
│  POST /api/agent/  │         │                    │
│    commands/{id}/  │◄────────│                    │
│    result          │         │                    │
└────────────────────┘         └──────────────────┘
```

**Communication model:** Pull-based with heartbeat. Agent polls for pending commands every 10 seconds and sends heartbeat with basic metrics. Control plane considers agent offline if no heartbeat for 60 seconds.

**Authentication:** Agent token generated during server bootstrap, stored on the server. Token is unique per server, revocable.

**Command model:** Control plane queues commands for agents (deploy, restart, config update). Agent polls, executes, reports result. Commands have TTL — expired commands are marked as timed out.

### 3.7 Artifact Flow

```
1. Pipeline job builds application
2. Runner creates tar.gz of built application (vendor installed, assets compiled, no .git)
3. Runner uploads tar.gz to S3-compatible storage with content hash
4. Control plane creates Artifact record: {hash, size, path, pipeline_run_id}
5. Artifact marked as "ready"

Deployment:
6. Release created referencing artifact + env config snapshot
7. Deployment job sends deploy command to each node's agent
8. Agent downloads artifact from S3 to /releases/{release_id}/
9. Agent extracts, runs activation hooks (migrate, cache clear)
10. Agent symlinks /current → /releases/{release_id}/
11. Agent reloads PHP-FPM, restarts workers
12. Agent reports success
```

### 3.8 Deployment Flow

```
Trigger (manual or auto after pipeline)
    │
    ▼
Create Release (artifact_id + config snapshot)
    │
    ▼
Create Deployment (release_id + target cluster + strategy)
    │
    ▼
Resolve target nodes (web-role nodes in cluster)
    │
    ▼
Sequential Rolling Deploy:
    ├── Node 1: drain → deploy → health check → activate → undrain
    ├── Node 2: drain → deploy → health check → activate → undrain
    └── Node N: ...
    │
    ▼ (all succeed)
Mark Release active, Deployment succeeded
Previous release marked superseded

    │ (any node fails health check)
    ▼
Rollback: revert failed node to previous release
Mark Deployment failed
```

---

## SECTION 4 — LARAVEL PROJECT STRUCTURE

### Design Decision

Hybrid approach: domain logic organized by module under `app/Modules/`, HTTP layer stays in standard Laravel locations under `app/Http/`. This gives strong domain boundaries while keeping HTTP concerns where Laravel developers expect them.

**Why not full module isolation (routes, controllers inside modules)?** Because Laravel's routing, middleware, and request lifecycle are tightly coupled to the framework. Fighting this creates friction. The domain layer is where isolation matters.

**Why not flat app/ with Models/Services/Actions?** At this scale (25+ models, 40+ actions), flat directories become unnavigable. Module grouping provides natural code organization.

```
helm/
├── app/
│   ├── Modules/
│   │   ├── Infrastructure/
│   │   │   ├── Actions/
│   │   │   │   ├── RegisterServer.php
│   │   │   │   ├── BootstrapServer.php
│   │   │   │   ├── CreateCluster.php
│   │   │   │   ├── JoinCluster.php
│   │   │   │   ├── DrainNode.php
│   │   │   │   ├── CordonNode.php
│   │   │   │   └── DecommissionNode.php
│   │   │   ├── Models/
│   │   │   │   ├── Provider.php
│   │   │   │   ├── Server.php
│   │   │   │   ├── Cluster.php
│   │   │   │   └── Agent.php
│   │   │   ├── Enums/
│   │   │   │   ├── ServerStatus.php
│   │   │   │   ├── ClusterStatus.php
│   │   │   │   ├── NodeRole.php
│   │   │   │   └── ProviderType.php
│   │   │   ├── Services/
│   │   │   │   ├── SshService.php
│   │   │   │   ├── ServerBootstrapper.php
│   │   │   │   └── Providers/
│   │   │   │       ├── ProviderInterface.php
│   │   │   │       ├── DigitalOceanProvider.php
│   │   │   │       └── ManualProvider.php
│   │   │   ├── Jobs/
│   │   │   │   ├── ProvisionServer.php
│   │   │   │   ├── BootstrapServer.php
│   │   │   │   └── CheckServerHealth.php
│   │   │   ├── Events/
│   │   │   │   ├── ServerRegistered.php
│   │   │   │   ├── ServerBootstrapped.php
│   │   │   │   ├── ServerHealthChanged.php
│   │   │   │   └── ClusterTopologyChanged.php
│   │   │   ├── StateMachines/
│   │   │   │   ├── ServerStateMachine.php
│   │   │   │   └── ClusterStateMachine.php
│   │   │   └── DTOs/
│   │   │       ├── RegisterServerData.php
│   │   │       └── BootstrapConfig.php
│   │   │
│   │   ├── AppPlatform/
│   │   │   ├── Actions/
│   │   │   │   ├── CreateApplication.php
│   │   │   │   ├── CreateEnvironment.php
│   │   │   │   ├── SetEnvironmentVariable.php
│   │   │   │   ├── EncryptSecret.php
│   │   │   │   └── ConnectGitRepository.php
│   │   │   ├── Models/
│   │   │   │   ├── Project.php
│   │   │   │   ├── Application.php
│   │   │   │   ├── Environment.php
│   │   │   │   ├── EnvironmentVariable.php
│   │   │   │   ├── Secret.php
│   │   │   │   ├── ProcessDefinition.php
│   │   │   │   └── GitConnection.php
│   │   │   ├── Enums/
│   │   │   │   ├── Runtime.php
│   │   │   │   ├── EnvironmentType.php
│   │   │   │   └── ProcessType.php
│   │   │   ├── Events/
│   │   │   │   ├── ApplicationCreated.php
│   │   │   │   ├── EnvironmentConfigChanged.php
│   │   │   │   └── GitConnectionEstablished.php
│   │   │   └── DTOs/
│   │   │       ├── CreateApplicationData.php
│   │   │       └── ProcessDefinitionData.php
│   │   │
│   │   ├── Pipeline/
│   │   │   ├── Actions/
│   │   │   │   ├── CreatePipeline.php
│   │   │   │   ├── TriggerPipelineRun.php
│   │   │   │   ├── AssignJobToRunner.php
│   │   │   │   ├── CompletePipelineJob.php
│   │   │   │   ├── FailPipelineJob.php
│   │   │   │   ├── RegisterRunner.php
│   │   │   │   └── CreateArtifact.php
│   │   │   ├── Models/
│   │   │   │   ├── Pipeline.php
│   │   │   │   ├── PipelineRun.php
│   │   │   │   ├── PipelineJob.php
│   │   │   │   ├── Runner.php
│   │   │   │   ├── Artifact.php
│   │   │   │   └── Webhook.php
│   │   │   ├── Enums/
│   │   │   │   ├── PipelineRunStatus.php
│   │   │   │   ├── PipelineJobStatus.php
│   │   │   │   ├── ArtifactStatus.php
│   │   │   │   ├── RunnerStatus.php
│   │   │   │   └── TriggerType.php
│   │   │   ├── Services/
│   │   │   │   ├── WebhookProcessor.php
│   │   │   │   ├── TriggerEvaluator.php
│   │   │   │   ├── PipelineOrchestrator.php
│   │   │   │   └── LogStreamer.php
│   │   │   ├── Jobs/
│   │   │   │   ├── ProcessWebhook.php
│   │   │   │   ├── OrchestrateRun.php
│   │   │   │   ├── CheckJobTimeout.php
│   │   │   │   └── CleanupOldArtifacts.php
│   │   │   ├── Events/
│   │   │   │   ├── PipelineRunStarted.php
│   │   │   │   ├── PipelineRunCompleted.php
│   │   │   │   ├── PipelineJobCompleted.php
│   │   │   │   └── ArtifactCreated.php
│   │   │   ├── StateMachines/
│   │   │   │   ├── PipelineRunStateMachine.php
│   │   │   │   ├── PipelineJobStateMachine.php
│   │   │   │   └── ArtifactStateMachine.php
│   │   │   └── DTOs/
│   │   │       ├── PipelineDefinition.php
│   │   │       ├── StageDefinition.php
│   │   │       └── JobDefinition.php
│   │   │
│   │   ├── Deployment/
│   │   │   ├── Actions/
│   │   │   │   ├── CreateRelease.php
│   │   │   │   ├── InitiateDeployment.php
│   │   │   │   ├── DeployToNode.php
│   │   │   │   ├── RollbackDeployment.php
│   │   │   │   ├── RunHealthCheck.php
│   │   │   │   └── ActivateRelease.php
│   │   │   ├── Models/
│   │   │   │   ├── Deployment.php
│   │   │   │   ├── Release.php
│   │   │   │   ├── DeploymentStep.php
│   │   │   │   └── HealthCheck.php
│   │   │   ├── Enums/
│   │   │   │   ├── DeploymentStatus.php
│   │   │   │   ├── ReleaseStatus.php
│   │   │   │   ├── DeploymentStrategy.php
│   │   │   │   └── HealthCheckStatus.php
│   │   │   ├── Services/
│   │   │   │   ├── DeploymentCoordinator.php
│   │   │   │   └── RollbackManager.php
│   │   │   ├── Jobs/
│   │   │   │   ├── ExecuteDeployment.php
│   │   │   │   ├── DeployToNode.php
│   │   │   │   ├── RunPostDeployHealthCheck.php
│   │   │   │   └── ExecuteRollback.php
│   │   │   ├── Events/
│   │   │   │   ├── DeploymentStarted.php
│   │   │   │   ├── DeploymentCompleted.php
│   │   │   │   ├── DeploymentFailed.php
│   │   │   │   └── RollbackCompleted.php
│   │   │   └── StateMachines/
│   │   │       ├── DeploymentStateMachine.php
│   │   │       └── ReleaseStateMachine.php
│   │   │
│   │   ├── Networking/
│   │   │   ├── Actions/
│   │   │   │   ├── AssignDomain.php
│   │   │   │   ├── IssueCertificate.php
│   │   │   │   ├── RenewCertificate.php
│   │   │   │   └── GenerateProxyConfig.php
│   │   │   ├── Models/
│   │   │   │   ├── Domain.php
│   │   │   │   └── Certificate.php
│   │   │   ├── Enums/
│   │   │   │   ├── CertificateStatus.php
│   │   │   │   └── DomainStatus.php
│   │   │   ├── Jobs/
│   │   │   │   ├── IssueCertificate.php
│   │   │   │   ├── RenewExpiringCertificates.php
│   │   │   │   └── PushProxyConfig.php
│   │   │   ├── Events/
│   │   │   │   ├── DomainAssigned.php
│   │   │   │   └── CertificateIssued.php
│   │   │   └── Services/
│   │   │       ├── CaddyConfigGenerator.php
│   │   │       └── DnsVerifier.php
│   │   │
│   │   ├── ServiceManagement/
│   │   │   ├── Actions/
│   │   │   │   ├── ProvisionDatabase.php
│   │   │   │   ├── ProvisionCache.php
│   │   │   │   └── ManageProcesses.php
│   │   │   ├── Models/
│   │   │   │   ├── DatabaseInstance.php
│   │   │   │   ├── CacheInstance.php
│   │   │   │   └── ServiceBinding.php
│   │   │   ├── Enums/
│   │   │   │   ├── DatabaseType.php
│   │   │   │   └── CacheType.php
│   │   │   └── Jobs/
│   │   │       └── ProvisionDatabase.php
│   │   │
│   │   ├── Observability/
│   │   │   ├── Actions/
│   │   │   │   ├── RecordMetric.php
│   │   │   │   └── EvaluateAlertRules.php
│   │   │   ├── Models/
│   │   │   │   ├── ServerMetric.php
│   │   │   │   ├── AlertRule.php
│   │   │   │   └── Alert.php
│   │   │   ├── Jobs/
│   │   │   │   ├── AggregateMetrics.php
│   │   │   │   └── PurgeOldMetrics.php
│   │   │   └── Events/
│   │   │       └── AlertFired.php
│   │   │
│   │   └── Operations/
│   │       ├── Actions/
│   │       │   ├── CreateBackup.php
│   │       │   ├── RestoreBackup.php
│   │       │   └── RecordAuditLog.php
│   │       ├── Models/
│   │       │   ├── Backup.php
│   │       │   ├── AuditLog.php
│   │       │   └── Activity.php
│   │       ├── Enums/
│   │       │   └── BackupStatus.php
│   │       ├── Jobs/
│   │       │   ├── ExecuteBackup.php
│   │       │   ├── ApplyRetentionPolicy.php
│   │       │   └── PurgeOldAuditLogs.php
│   │       ├── Listeners/
│   │       │   └── RecordAuditLog.php
│   │       └── StateMachines/
│   │           └── BackupStateMachine.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php
│   │   │   ├── Api/
│   │   │   │   ├── Infrastructure/
│   │   │   │   │   ├── ProviderController.php
│   │   │   │   │   ├── ServerController.php
│   │   │   │   │   └── ClusterController.php
│   │   │   │   ├── AppPlatform/
│   │   │   │   │   ├── ProjectController.php
│   │   │   │   │   ├── ApplicationController.php
│   │   │   │   │   ├── EnvironmentController.php
│   │   │   │   │   ├── EnvironmentVariableController.php
│   │   │   │   │   └── SecretController.php
│   │   │   │   ├── Pipeline/
│   │   │   │   │   ├── PipelineController.php
│   │   │   │   │   ├── PipelineRunController.php
│   │   │   │   │   ├── WebhookController.php
│   │   │   │   │   └── ArtifactController.php
│   │   │   │   ├── Deployment/
│   │   │   │   │   ├── DeploymentController.php
│   │   │   │   │   └── ReleaseController.php
│   │   │   │   ├── Networking/
│   │   │   │   │   ├── DomainController.php
│   │   │   │   │   └── CertificateController.php
│   │   │   │   ├── Operations/
│   │   │   │   │   ├── AuditLogController.php
│   │   │   │   │   └── ActivityController.php
│   │   │   │   ├── Agent/
│   │   │   │   │   └── AgentController.php
│   │   │   │   └── Runner/
│   │   │   │       └── RunnerController.php
│   │   │   └── Web/
│   │   │       ├── DashboardController.php
│   │   │       ├── Settings/
│   │   │       │   ├── ProfileController.php
│   │   │       │   └── SecurityController.php
│   │   │       └── PageController.php
│   │   ├── Middleware/
│   │   │   ├── HandleAppearance.php
│   │   │   ├── HandleInertiaRequests.php
│   │   │   └── AuthenticateAgent.php
│   │   ├── Requests/
│   │   │   ├── Infrastructure/
│   │   │   │   ├── RegisterServerRequest.php
│   │   │   │   └── CreateClusterRequest.php
│   │   │   ├── AppPlatform/
│   │   │   │   ├── CreateApplicationRequest.php
│   │   │   │   └── SetEnvironmentVariableRequest.php
│   │   │   ├── Pipeline/
│   │   │   │   ├── CreatePipelineRequest.php
│   │   │   │   └── TriggerRunRequest.php
│   │   │   ├── Deployment/
│   │   │   │   └── InitiateDeploymentRequest.php
│   │   │   └── Settings/
│   │   │       ├── ProfileUpdateRequest.php
│   │   │       ├── PasswordUpdateRequest.php
│   │   │       └── ProfileDeleteRequest.php
│   │   └── Resources/
│   │       ├── Infrastructure/
│   │       │   ├── ServerResource.php
│   │       │   └── ClusterResource.php
│   │       ├── AppPlatform/
│   │       │   ├── ApplicationResource.php
│   │       │   └── EnvironmentResource.php
│   │       ├── Pipeline/
│   │       │   ├── PipelineRunResource.php
│   │       │   └── ArtifactResource.php
│   │       └── Deployment/
│   │           ├── DeploymentResource.php
│   │           └── ReleaseResource.php
│   │
│   ├── Console/
│   │   └── Commands/
│   │       ├── BootstrapServerCommand.php
│   │       ├── RunnerRegisterCommand.php
│   │       └── CleanupArtifactsCommand.php
│   │
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   ├── FortifyServiceProvider.php
│   │   └── EventServiceProvider.php
│   │
│   ├── Support/
│   │   ├── Concerns/
│   │   │   ├── HasUlid.php
│   │   │   ├── HasStateMachine.php
│   │   │   └── Encryptable.php
│   │   ├── Enums/
│   │   │   └── QueueName.php
│   │   └── Services/
│   │       └── ObjectStorage/
│   │           └── ObjectStorageService.php
│   │
│   ├── Actions/
│   │   └── Fortify/
│   │       ├── CreateNewUser.php
│   │       └── ResetUserPassword.php
│   │
│   ├── Concerns/
│   │   ├── PasswordValidationRules.php
│   │   └── ProfileValidationRules.php
│   │
│   └── Models/
│       └── User.php
│
├── bootstrap/
├── config/
│   ├── helm.php              (app-specific config: agent intervals, retention, etc.)
│   └── ...
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
│
├── routes/
│   ├── web.php
│   ├── api.php
│   ├── agent.php             (agent API routes, separate auth)
│   ├── runner.php            (runner API routes, separate auth)
│   └── webhooks.php          (incoming webhooks, no auth)
│
├── resources/
│   ├── js/
│   │   ├── app.tsx
│   │   ├── components/
│   │   ├── hooks/
│   │   ├── layouts/
│   │   ├── lib/
│   │   ├── pages/
│   │   │   ├── dashboard.tsx
│   │   │   ├── servers/
│   │   │   ├── clusters/
│   │   │   ├── applications/
│   │   │   ├── pipelines/
│   │   │   ├── deployments/
│   │   │   └── settings/
│   │   └── types/
│   └── views/
│
├── tests/
│   ├── Unit/
│   │   └── Modules/
│   ├── Feature/
│   │   ├── Api/
│   │   └── Web/
│   └── Integration/
│
├── storage/
├── public/
├── vendor/
├── .planning/
├── composer.json
├── package.json
├── vite.config.ts
└── phpunit.xml
```

---

## SECTION 5 — DOMAIN MODEL

### User
**Purpose:** The platform operator (you).
**Fields:** id, name, email, password, email_verified_at, two_factor_secret, two_factor_recovery_codes, timestamps
**Relationships:** hasMany(PersonalAccessToken), hasMany(SshKey)
**Notes:** Single user for now. Fortify handles auth. No org/team scoping.

### PersonalAccessToken
**Purpose:** API authentication token for programmatic access, CLI, and scripts.
**Fields:** id, user_id, name, token (hashed), abilities (json), last_used_at, expires_at, timestamps
**Relationships:** belongsTo(User)
**Lifecycle:** Created by user. Expires or is revoked.

### SshKey
**Purpose:** User's SSH public keys for server access.
**Fields:** id, user_id, name, public_key, fingerprint, timestamps
**Relationships:** belongsTo(User)

### Provider
**Purpose:** Cloud provider credentials for server provisioning.
**Fields:** id, name, type (enum: digitalocean, hetzner, vultr, manual), credentials (encrypted json), is_active, timestamps
**Relationships:** hasMany(Server)
**Lifecycle:** Created when you connect a cloud provider. Credentials encrypted at rest.

### Project
**Purpose:** Logical grouping of related applications.
**Fields:** id, name, slug, description, timestamps, soft_deletes
**Relationships:** hasMany(Application)

### Server
**Purpose:** Physical or virtual server managed by the platform.
**Fields:** id, provider_id, name, hostname, public_ip, private_ip, ssh_port (default 22), ssh_user (default root), os, cpu_cores, memory_mb, disk_gb, region, status (enum), agent_token (encrypted), last_heartbeat_at, metadata (json), timestamps, soft_deletes
**Relationships:** belongsTo(Provider), belongsToMany(Cluster, 'cluster_node'), hasMany(ServerMetric)
**Lifecycle:** registered → provisioning → bootstrapping → active → [draining, cordoned, maintenance] → decommissioned

### Cluster
**Purpose:** Logical group of servers that host applications together.
**Fields:** id, name, slug, status (enum), settings (json), timestamps
**Relationships:** belongsToMany(Server, 'cluster_node'), hasMany(Environment)
**Lifecycle:** pending → active → [updating, scaling, degraded, maintenance] → decommissioned

### ClusterNode (pivot)
**Purpose:** Server's membership in a cluster with assigned role.
**Fields:** id, cluster_id, server_id, role (enum: web, worker, db, cache, queue, bastion), is_active, sort_order, timestamps
**Relationships:** belongsTo(Cluster), belongsTo(Server)

### Application
**Purpose:** A deployable application (codebase).
**Fields:** id, project_id, name, slug, runtime (enum: php, node, python, go), repository_url, repository_branch, git_connection_id, settings (json), timestamps, soft_deletes
**Relationships:** belongsTo(Project), belongsTo(GitConnection), hasMany(Environment), hasMany(Pipeline)

### GitConnection
**Purpose:** OAuth connection to a git provider.
**Fields:** id, provider (enum: github, gitlab, bitbucket), access_token (encrypted), refresh_token (encrypted), token_expires_at, account_name, timestamps
**Relationships:** hasMany(Application)

### Environment
**Purpose:** A deployment target for an application (production, staging, etc.).
**Fields:** id, application_id, cluster_id, name, type (enum: production, staging, preview), is_auto_deploy, branch, active_release_id, timestamps
**Relationships:** belongsTo(Application), belongsTo(Cluster), hasMany(EnvironmentVariable), hasMany(Secret), hasMany(ProcessDefinition), hasMany(Deployment), hasMany(Domain), belongsTo(Release, 'active_release')

### EnvironmentVariable
**Purpose:** Non-secret configuration for an environment.
**Fields:** id, environment_id, key, value, is_build_arg (bool), timestamps
**Relationships:** belongsTo(Environment)

### Secret
**Purpose:** Encrypted secret for an environment.
**Fields:** id, environment_id, key, encrypted_value, version, timestamps
**Relationships:** belongsTo(Environment)
**Notes:** Values encrypted with app key. Only decrypted on deploy or when explicitly revealed in UI.

### ProcessDefinition
**Purpose:** Defines a process to run for an environment (web server, queue worker, scheduler).
**Fields:** id, environment_id, type (enum: web, worker, scheduler, custom), command, instances (int), timestamps
**Relationships:** belongsTo(Environment)

### Pipeline
**Purpose:** CI/CD pipeline definition for an application.
**Fields:** id, application_id, name, definition (json), is_active, trigger_branches (json), trigger_events (json), timestamps
**Relationships:** belongsTo(Application), hasMany(PipelineRun)

### PipelineRun
**Purpose:** Single execution of a pipeline.
**Fields:** id, pipeline_id, environment_id, status (enum), trigger_type (enum: push, tag, manual, api), trigger_ref, trigger_sha, trigger_actor, definition_snapshot (json), started_at, finished_at, timestamps
**Relationships:** belongsTo(Pipeline), belongsTo(Environment), hasMany(PipelineJob), hasOne(Artifact)
**Lifecycle:** pending → running → succeeded/failed/cancelled/timed_out

### PipelineJob
**Purpose:** Individual job within a pipeline run.
**Fields:** id, pipeline_run_id, stage, name, status (enum), runner_id, commands (json), environment (json), started_at, finished_at, log_path (S3 path), exit_code, timestamps
**Relationships:** belongsTo(PipelineRun), belongsTo(Runner)
**Lifecycle:** pending → queued → assigned → running → succeeded/failed/cancelled/timed_out/skipped

### Runner
**Purpose:** CI job executor (Docker host that runs pipeline jobs).
**Fields:** id, name, token (encrypted), status (enum: online, offline, busy, draining), platform, last_heartbeat_at, metadata (json), timestamps
**Relationships:** hasMany(PipelineJob)

### Artifact
**Purpose:** Immutable build output ready for deployment.
**Fields:** id, pipeline_run_id, application_id, status (enum), storage_path, content_hash (sha256), size_bytes, metadata (json), timestamps
**Relationships:** belongsTo(PipelineRun), belongsTo(Application), hasMany(Release)
**Lifecycle:** building → ready → deployed/superseded/expired/failed. Immutable once ready.

### Release
**Purpose:** A versioned, deployable unit = artifact + config snapshot.
**Fields:** id, environment_id, artifact_id, version (auto-incrementing per environment), status (enum), config_snapshot (json), deployed_by, timestamps
**Relationships:** belongsTo(Environment), belongsTo(Artifact), belongsTo(User, 'deployed_by'), hasMany(Deployment)
**Lifecycle:** pending → deploying → active/superseded/rolled_back/failed

### Deployment
**Purpose:** A single attempt to deploy a release to an environment's cluster.
**Fields:** id, release_id, environment_id, status (enum), strategy (enum: rolling, blue_green, canary), total_nodes, completed_nodes, failed_nodes, started_at, finished_at, initiated_by, timestamps
**Relationships:** belongsTo(Release), belongsTo(Environment), belongsTo(User, 'initiated_by'), hasMany(DeploymentStep)
**Lifecycle:** pending → preparing → deploying → verifying → succeeded/failed/cancelled/rolled_back

### DeploymentStep
**Purpose:** Per-node deployment progress tracking.
**Fields:** id, deployment_id, server_id, status (enum: pending, deploying, deployed, health_checking, active, failed, rolled_back), started_at, finished_at, output (text), timestamps
**Relationships:** belongsTo(Deployment), belongsTo(Server)

### HealthCheck
**Purpose:** Health check definition for environments.
**Fields:** id, environment_id, type (enum: http, tcp, command), target, interval_seconds, timeout_seconds, healthy_threshold, unhealthy_threshold, is_active, timestamps
**Relationships:** belongsTo(Environment)

### Domain
**Purpose:** Domain name assigned to an environment.
**Fields:** id, environment_id, hostname, is_primary, is_verified, verification_token, timestamps
**Relationships:** belongsTo(Environment), hasOne(Certificate)

### Certificate
**Purpose:** SSL certificate for a domain.
**Fields:** id, domain_id, type (enum: auto, custom), status (enum: pending, active, expired, failed), issued_at, expires_at, timestamps
**Relationships:** belongsTo(Domain)
**Notes:** With Caddy, certificates are mostly automatic. This table tracks state for custom certs and monitoring.

### Webhook
**Purpose:** Webhook registration for incoming git events.
**Fields:** id, application_id, provider, secret (encrypted), is_active, timestamps
**Relationships:** belongsTo(Application)

### Backup (post-MVP)
**Purpose:** Scheduled or manual backup of databases/files.
**Fields:** id, server_id, type (enum: database, files), status (enum), storage_path, size_bytes, started_at, finished_at, retention_days, timestamps
**Relationships:** belongsTo(Server)
**Lifecycle:** pending → running → completed/failed → expired

### AuditLog
**Purpose:** Immutable record of every significant action.
**Fields:** id, user_id, action, auditable_type, auditable_id, old_values (json), new_values (json), ip_address, user_agent, created_at
**Relationships:** belongsTo(User), morphTo(auditable)
**Notes:** Append-only. Never updated or soft-deleted.

### ServerMetric (post-MVP)
**Purpose:** Time-series node metrics from agent heartbeats.
**Fields:** id (bigint), server_id, cpu_percent, memory_percent, disk_percent, load_avg_1m, load_avg_5m, network_rx_bytes, network_tx_bytes, recorded_at
**Relationships:** belongsTo(Server)

---

## SECTION 6 — DATABASE DESIGN

### Key Design Decisions

- **ULIDs** for all primary keys (sortable, URL-safe, globally unique). Exception: server_metrics uses bigint auto-increment (high-volume).
- **No organization_id anywhere.** Single-user tool. All resources are implicitly owned by the install.
- **jsonb** for flexible config fields (pipeline definitions, server metadata, settings).
- **Encrypted text** columns for credentials, tokens, secrets. Laravel `Crypt` facade.

### Table Definitions

#### Auth Tables

**personal_access_tokens**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| user_id | ulid (FK → users) | NOT NULL |
| name | varchar(255) | NOT NULL |
| token | varchar(64) | NOT NULL, UNIQUE |
| abilities | jsonb | DEFAULT '["*"]' |
| last_used_at | timestamp | nullable |
| expires_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

**ssh_keys**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| user_id | ulid (FK → users) | NOT NULL |
| name | varchar(255) | NOT NULL |
| public_key | text | NOT NULL |
| fingerprint | varchar(255) | NOT NULL, UNIQUE |
| created_at | timestamp | |
| updated_at | timestamp | |

#### Infrastructure Tables

**providers**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| name | varchar(255) | NOT NULL |
| type | varchar(20) | NOT NULL |
| credentials | text | NOT NULL (encrypted) |
| is_active | boolean | DEFAULT true |
| created_at | timestamp | |
| updated_at | timestamp | |

Enum `type`: digitalocean, hetzner, vultr, aws, manual

**servers**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| provider_id | ulid (FK) | nullable |
| name | varchar(255) | NOT NULL |
| hostname | varchar(255) | NOT NULL |
| public_ip | inet | NOT NULL |
| private_ip | inet | nullable |
| ssh_port | integer | DEFAULT 22 |
| ssh_user | varchar(50) | DEFAULT 'root' |
| os | varchar(100) | nullable |
| cpu_cores | integer | nullable |
| memory_mb | integer | nullable |
| disk_gb | integer | nullable |
| region | varchar(100) | nullable |
| status | varchar(30) | NOT NULL, DEFAULT 'pending' |
| agent_token | text | nullable (encrypted) |
| last_heartbeat_at | timestamp | nullable |
| metadata | jsonb | DEFAULT '{}' |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp | nullable |

Indexes: (status), (public_ip)

Enum `status`: pending, provisioning, bootstrapping, active, draining, cordoned, maintenance, decommissioning, decommissioned, failed

**clusters**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| name | varchar(255) | NOT NULL |
| slug | varchar(255) | NOT NULL, UNIQUE |
| status | varchar(30) | NOT NULL, DEFAULT 'pending' |
| settings | jsonb | DEFAULT '{}' |
| created_at | timestamp | |
| updated_at | timestamp | |

Enum `status`: pending, provisioning, active, updating, scaling, degraded, maintenance, decommissioning, decommissioned

**cluster_node** (pivot)
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| cluster_id | ulid (FK) | NOT NULL |
| server_id | ulid (FK) | NOT NULL |
| role | varchar(20) | NOT NULL |
| is_active | boolean | DEFAULT true |
| sort_order | integer | DEFAULT 0 |
| created_at | timestamp | |
| updated_at | timestamp | |
| | | UNIQUE(cluster_id, server_id) |

Enum `role`: web, worker, db, cache, queue, bastion

#### Application Tables

**projects**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| name | varchar(255) | NOT NULL |
| slug | varchar(255) | NOT NULL, UNIQUE |
| description | text | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp | nullable |

**git_connections**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| provider | varchar(20) | NOT NULL |
| access_token | text | NOT NULL (encrypted) |
| refresh_token | text | nullable (encrypted) |
| token_expires_at | timestamp | nullable |
| account_name | varchar(255) | NOT NULL |
| created_at | timestamp | |
| updated_at | timestamp | |

**applications**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| project_id | ulid (FK) | NOT NULL |
| name | varchar(255) | NOT NULL |
| slug | varchar(255) | NOT NULL |
| runtime | varchar(20) | NOT NULL, DEFAULT 'php' |
| repository_url | varchar(500) | nullable |
| repository_branch | varchar(255) | DEFAULT 'main' |
| git_connection_id | ulid (FK) | nullable |
| settings | jsonb | DEFAULT '{}' |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp | nullable |

Enum `runtime`: php, node, python, go

**environments**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| application_id | ulid (FK) | NOT NULL |
| cluster_id | ulid (FK) | NOT NULL |
| name | varchar(100) | NOT NULL |
| type | varchar(20) | NOT NULL |
| is_auto_deploy | boolean | DEFAULT false |
| branch | varchar(255) | nullable |
| active_release_id | ulid (FK) | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |
| | | UNIQUE(application_id, name) |

Enum `type`: production, staging, preview

**environment_variables**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| environment_id | ulid (FK) | NOT NULL |
| key | varchar(255) | NOT NULL |
| value | text | NOT NULL |
| is_build_arg | boolean | DEFAULT false |
| created_at | timestamp | |
| updated_at | timestamp | |
| | | UNIQUE(environment_id, key) |

**secrets**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| environment_id | ulid (FK) | NOT NULL |
| key | varchar(255) | NOT NULL |
| encrypted_value | text | NOT NULL |
| version | integer | DEFAULT 1 |
| created_at | timestamp | |
| updated_at | timestamp | |
| | | UNIQUE(environment_id, key) |

**process_definitions**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| environment_id | ulid (FK) | NOT NULL |
| type | varchar(20) | NOT NULL |
| command | text | NOT NULL |
| instances | integer | DEFAULT 1 |
| created_at | timestamp | |
| updated_at | timestamp | |

Enum `type`: web, worker, scheduler, custom

#### Pipeline Tables

**pipelines**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| application_id | ulid (FK) | NOT NULL |
| name | varchar(255) | NOT NULL |
| definition | jsonb | NOT NULL |
| is_active | boolean | DEFAULT true |
| trigger_branches | jsonb | DEFAULT '["main"]' |
| trigger_events | jsonb | DEFAULT '["push"]' |
| created_at | timestamp | |
| updated_at | timestamp | |

**pipeline_runs**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| pipeline_id | ulid (FK) | NOT NULL |
| environment_id | ulid (FK) | nullable |
| status | varchar(20) | NOT NULL, DEFAULT 'pending' |
| trigger_type | varchar(20) | NOT NULL |
| trigger_ref | varchar(255) | nullable |
| trigger_sha | varchar(40) | nullable |
| trigger_actor | varchar(255) | nullable |
| definition_snapshot | jsonb | NOT NULL |
| started_at | timestamp | nullable |
| finished_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

Indexes: (pipeline_id, status), (created_at DESC)

Enum `status`: pending, running, succeeded, failed, cancelled, timed_out
Enum `trigger_type`: push, tag, pull_request, manual, api, schedule

**pipeline_jobs**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| pipeline_run_id | ulid (FK) | NOT NULL |
| stage | varchar(100) | NOT NULL |
| name | varchar(255) | NOT NULL |
| status | varchar(20) | NOT NULL, DEFAULT 'pending' |
| runner_id | ulid (FK) | nullable |
| commands | jsonb | NOT NULL |
| environment | jsonb | DEFAULT '{}' |
| started_at | timestamp | nullable |
| finished_at | timestamp | nullable |
| log_path | varchar(500) | nullable |
| exit_code | integer | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

Indexes: (pipeline_run_id, stage), (runner_id, status)

Enum `status`: pending, queued, assigned, running, succeeded, failed, cancelled, timed_out, skipped

**runners**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| name | varchar(255) | NOT NULL |
| token | text | NOT NULL (encrypted) |
| status | varchar(20) | NOT NULL, DEFAULT 'offline' |
| platform | varchar(50) | nullable |
| last_heartbeat_at | timestamp | nullable |
| metadata | jsonb | DEFAULT '{}' |
| created_at | timestamp | |
| updated_at | timestamp | |

Enum `status`: online, offline, busy, draining

**artifacts**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| pipeline_run_id | ulid (FK) | NOT NULL |
| application_id | ulid (FK) | NOT NULL |
| status | varchar(20) | NOT NULL, DEFAULT 'building' |
| storage_path | varchar(500) | nullable |
| content_hash | varchar(64) | nullable |
| size_bytes | bigint | nullable |
| metadata | jsonb | DEFAULT '{}' |
| created_at | timestamp | |
| updated_at | timestamp | |

Enum `status`: building, ready, deployed, superseded, expired, failed

**webhooks**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| application_id | ulid (FK) | NOT NULL |
| provider | varchar(20) | NOT NULL |
| secret | text | NOT NULL (encrypted) |
| is_active | boolean | DEFAULT true |
| created_at | timestamp | |
| updated_at | timestamp | |

#### Deployment Tables

**releases**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| environment_id | ulid (FK) | NOT NULL |
| artifact_id | ulid (FK) | NOT NULL |
| version | integer | NOT NULL |
| status | varchar(20) | NOT NULL, DEFAULT 'pending' |
| config_snapshot | jsonb | NOT NULL |
| deployed_by | ulid (FK → users) | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |
| | | UNIQUE(environment_id, version) |

Enum `status`: pending, deploying, active, superseded, rolled_back, failed

**deployments**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| release_id | ulid (FK) | NOT NULL |
| environment_id | ulid (FK) | NOT NULL |
| status | varchar(20) | NOT NULL, DEFAULT 'pending' |
| strategy | varchar(20) | NOT NULL, DEFAULT 'rolling' |
| total_nodes | integer | NOT NULL, DEFAULT 0 |
| completed_nodes | integer | NOT NULL, DEFAULT 0 |
| failed_nodes | integer | NOT NULL, DEFAULT 0 |
| started_at | timestamp | nullable |
| finished_at | timestamp | nullable |
| initiated_by | ulid (FK → users) | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

Indexes: (environment_id, created_at DESC), (status)

Enum `status`: pending, preparing, deploying, verifying, succeeded, failed, cancelled, rolled_back
Enum `strategy`: rolling, blue_green, canary

**deployment_steps**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| deployment_id | ulid (FK) | NOT NULL |
| server_id | ulid (FK) | NOT NULL |
| status | varchar(20) | NOT NULL, DEFAULT 'pending' |
| started_at | timestamp | nullable |
| finished_at | timestamp | nullable |
| output | text | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

**health_checks**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| environment_id | ulid (FK) | NOT NULL |
| type | varchar(20) | NOT NULL, DEFAULT 'http' |
| target | varchar(500) | NOT NULL |
| interval_seconds | integer | DEFAULT 30 |
| timeout_seconds | integer | DEFAULT 5 |
| healthy_threshold | integer | DEFAULT 3 |
| unhealthy_threshold | integer | DEFAULT 2 |
| is_active | boolean | DEFAULT true |
| created_at | timestamp | |
| updated_at | timestamp | |

#### Networking Tables

**domains**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| environment_id | ulid (FK) | NOT NULL |
| hostname | varchar(255) | NOT NULL, UNIQUE |
| is_primary | boolean | DEFAULT false |
| is_verified | boolean | DEFAULT false |
| verification_token | varchar(64) | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

**certificates**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| domain_id | ulid (FK) | NOT NULL |
| type | varchar(20) | NOT NULL, DEFAULT 'auto' |
| status | varchar(20) | NOT NULL, DEFAULT 'pending' |
| issued_at | timestamp | nullable |
| expires_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

#### Operations Tables

**audit_logs**
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| user_id | ulid (FK) | nullable |
| action | varchar(100) | NOT NULL |
| auditable_type | varchar(255) | NOT NULL |
| auditable_id | ulid | NOT NULL |
| old_values | jsonb | nullable |
| new_values | jsonb | nullable |
| ip_address | inet | nullable |
| user_agent | text | nullable |
| created_at | timestamp | NOT NULL |

Indexes: (created_at DESC), (auditable_type, auditable_id)

No updated_at — audit logs are immutable.

**backups** (post-MVP)
| Column | Type | Constraints |
|--------|------|-------------|
| id | ulid (PK) | |
| server_id | ulid (FK) | NOT NULL |
| type | varchar(20) | NOT NULL |
| status | varchar(20) | NOT NULL, DEFAULT 'pending' |
| storage_path | varchar(500) | nullable |
| size_bytes | bigint | nullable |
| started_at | timestamp | nullable |
| finished_at | timestamp | nullable |
| retention_days | integer | DEFAULT 30 |
| created_at | timestamp | |
| updated_at | timestamp | |

**server_metrics** (post-MVP)
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint (PK) | auto-increment |
| server_id | ulid (FK) | NOT NULL |
| cpu_percent | decimal(5,2) | |
| memory_percent | decimal(5,2) | |
| disk_percent | decimal(5,2) | |
| load_avg_1m | decimal(8,2) | |
| load_avg_5m | decimal(8,2) | |
| network_rx_bytes | bigint | |
| network_tx_bytes | bigint | |
| recorded_at | timestamp | NOT NULL |

Indexes: (server_id, recorded_at DESC)

### MVP vs Later Tables

**MVP:**
users (exists), personal_access_tokens, ssh_keys, providers, servers, clusters, cluster_node, projects, git_connections, applications, environments, environment_variables, secrets, process_definitions, pipelines, pipeline_runs, pipeline_jobs, runners, artifacts, webhooks, releases, deployments, deployment_steps, health_checks, domains, certificates, audit_logs

**Post-MVP:**
backups, server_metrics, alert_rules, alerts, database_instances, cache_instances, service_bindings

---

## SECTION 7 — STATE MACHINES

### 7.1 Server State Machine

```
                    ┌─────────────────┐
                    │     pending      │
                    └────────┬────────┘
                             │ provision
                    ┌────────▼────────┐
              ┌─────│  provisioning   │─────┐
              │     └────────┬────────┘     │
              │              │ bootstrap    │ fail
              │     ┌────────▼────────┐     │
              │  ┌──│  bootstrapping  │──┐  │
              │  │  └────────┬────────┘  │  │
              │  │           │ complete   │  │
              │  │  ┌────────▼────────┐  │  │
              │  │  │     active      │◄─┘  │
              │  │  └──┬──┬──┬───────┘     │
              │  │     │  │  │              │
              │  │  drain cordon maintain   │
              │  │     │  │  │              │
              │  │     ▼  ▼  ▼              │
              │  │  ┌──────────────┐        │
              │  │  │  draining /  │        │
              │  │  │  cordoned /  │        │
              │  │  │ maintenance  │        │
              │  │  └──────┬──────┘        │
              │  │         │ activate       │
              │  │         ▼               │
              │  │     (→ active)           │
              │  │         │               │
              │  │    decommission          │
              │  │         │               │
              │  │  ┌──────▼──────┐        │
              │  │  │decommissioning│       │
              │  │  └──────┬──────┘        │
              │  │         │               │
              │  │  ┌──────▼──────┐  ┌─────▼─────┐
              │  │  │decommissioned│  │   failed   │
              │  │  └─────────────┘  └───────────┘
              │  │                        ▲
              └──┴────────────────────────┘
```

**Valid Transitions:**
| From | To | Trigger |
|------|----|---------|
| pending | provisioning | API provision command |
| pending | bootstrapping | Manual server (skip provisioning) |
| provisioning | bootstrapping | Provider API returns success |
| provisioning | failed | Provider API returns error |
| bootstrapping | active | Bootstrap script completes |
| bootstrapping | failed | Bootstrap script fails |
| active | draining | Initiate drain |
| active | cordoned | Cordon node |
| active | maintenance | Enable maintenance |
| active | decommissioning | Decommission |
| draining | active | Drain complete, reactivate |
| draining | decommissioning | Drain complete, decommission |
| cordoned | active | Uncordon |
| cordoned | decommissioning | Decommission |
| maintenance | active | Maintenance complete |
| maintenance | decommissioning | Decommission |
| decommissioning | decommissioned | Cleanup complete |
| failed | bootstrapping | Retry bootstrap |
| failed | decommissioning | Give up, decommission |

**Retry:** Failed bootstrapping can be retried up to 3 times.

### 7.2 Cluster State Machine

```
pending → provisioning → active ⇄ [updating, scaling, degraded, maintenance] → decommissioning → decommissioned
```

| From | To | Trigger |
|------|----|---------|
| pending | provisioning | First node added |
| provisioning | active | All initial nodes bootstrapped |
| active | updating | Rolling update started |
| active | scaling | Node added/removed |
| active | degraded | Node health check failed |
| active | maintenance | Maintenance window started |
| active | decommissioning | Decommission cluster |
| updating | active | Update complete |
| updating | degraded | Update failed on a node |
| scaling | active | Scaling complete |
| degraded | active | Failed node replaced/recovered |
| maintenance | active | Maintenance complete |
| decommissioning | decommissioned | All nodes removed |

### 7.3 PipelineRun State Machine

```
pending → running → succeeded / failed / cancelled / timed_out
```

| From | To | Trigger |
|------|----|---------|
| pending | running | First job starts |
| pending | cancelled | User cancels before start |
| running | succeeded | All jobs succeeded |
| running | failed | Any required job failed |
| running | cancelled | User cancels |
| running | timed_out | Run exceeds max duration |

Terminal states: succeeded, failed, cancelled, timed_out. No retries at run level — trigger a new run.

### 7.4 PipelineJob State Machine

```
pending → queued → assigned → running → succeeded / failed / cancelled / timed_out
(pending → skipped when upstream failed)
```

| From | To | Trigger |
|------|----|---------|
| pending | queued | Stage ready to execute |
| pending | skipped | Upstream job failed |
| queued | assigned | Runner picked up job |
| assigned | running | Runner started execution |
| running | succeeded | Exit code 0 |
| running | failed | Non-zero exit code |
| running | cancelled | Run cancelled |
| running | timed_out | Job exceeds timeout |

**Retry:** Failed jobs retried by creating a new job record. Max 2 retries per job.

### 7.5 Artifact State Machine

```
building → ready → deployed → superseded / expired
                 → expired
         → failed
```

| From | To | Trigger |
|------|----|---------|
| building | ready | Upload complete, hash verified |
| building | failed | Build/upload failed |
| ready | deployed | First deployment using this artifact |
| deployed | superseded | Newer artifact deployed |
| ready | expired | Retention policy |
| deployed | expired | Retention policy (superseded long enough) |

Immutable once `ready` — artifact file is never modified.

### 7.6 Deployment State Machine

```
pending → preparing → deploying → verifying → succeeded
                                             → failed → rolled_back
                                  → failed → rolled_back
                    → failed
         → cancelled
```

| From | To | Trigger |
|------|----|---------|
| pending | preparing | Deployment job starts |
| pending | cancelled | User cancels |
| preparing | deploying | Config resolved, nodes identified |
| preparing | failed | Config resolution failed |
| deploying | verifying | All nodes deployed |
| deploying | failed | Node deployment failed |
| verifying | succeeded | All health checks pass |
| verifying | failed | Health checks fail |
| failed | rolled_back | Rollback completes |

### 7.7 Release State Machine

```
pending → deploying → active → superseded
                    → failed → rolled_back
```

| From | To | Trigger |
|------|----|---------|
| pending | deploying | Deployment initiated |
| deploying | active | Deployment succeeded |
| deploying | failed | Deployment failed |
| active | superseded | New release becomes active |
| failed | rolled_back | Rollback to previous release |

### 7.8 Backup State Machine (post-MVP)

```
pending → running → completed → expired
                  → failed
```

| From | To | Trigger |
|------|----|---------|
| pending | running | Backup job starts |
| running | completed | Backup uploaded successfully |
| running | failed | Backup script/upload failed |
| completed | expired | Retention policy |

---

## SECTION 8 — CI/CD DESIGN

### 8.1 Pipeline Model

A Pipeline is a definition stored as JSON in the database. It defines stages (sequential) containing jobs (parallel within a stage).

**Example pipeline definition (`pipelines.definition` jsonb):**

```json
{
  "stages": [
    {
      "name": "build",
      "jobs": [
        {
          "name": "install-and-build",
          "image": "php:8.3-cli",
          "commands": [
            "composer install --no-dev --optimize-autoloader",
            "npm ci",
            "npm run build"
          ],
          "timeout_minutes": 15,
          "artifacts": {
            "paths": ["vendor/", "public/build/", "bootstrap/cache/"],
            "exclude": [".git/", "node_modules/", "tests/"]
          }
        }
      ]
    },
    {
      "name": "test",
      "jobs": [
        {
          "name": "phpunit",
          "image": "php:8.3-cli",
          "services": ["postgres:16"],
          "commands": [
            "cp .env.testing .env",
            "php artisan test --parallel"
          ],
          "timeout_minutes": 10
        },
        {
          "name": "pint",
          "image": "php:8.3-cli",
          "commands": ["./vendor/bin/pint --test"],
          "timeout_minutes": 5
        }
      ]
    },
    {
      "name": "package",
      "jobs": [
        {
          "name": "create-artifact",
          "image": "php:8.3-cli",
          "commands": ["helm-package"],
          "creates_artifact": true,
          "timeout_minutes": 5
        }
      ]
    }
  ]
}
```

### 8.2 Webhook Ingestion

```
GitHub push event
    │
    ▼
POST /webhooks/{application_id}/{provider}
    │
    ▼
Verify webhook signature (HMAC-SHA256)
    │
    ▼
Dispatch ProcessWebhook job (async)
    │
    ▼
Parse payload: extract ref, sha, actor, event type
    │
    ▼
Find matching pipelines for this application
    │
    ▼
For each pipeline, evaluate triggers:
  - Does the event type match? (push, tag, PR)
  - Does the branch/tag match trigger_branches?
  - Is the pipeline active?
    │
    ▼
For each match: create PipelineRun, dispatch OrchestrateRun job
```

Webhook routes are unauthenticated but signature-verified. Each application has a unique webhook secret per provider.

### 8.3 Pipeline Orchestration

The `OrchestrateRun` job coordinates execution:

```
OrchestrateRun job
    │
    ▼
Snapshot pipeline definition into pipeline_run.definition_snapshot
    │
    ▼
Create PipelineJob records for all jobs in all stages
    │
    ▼
Process stages sequentially:
    │
    ├── Stage 1: Mark all stage jobs as "queued"
    │   Wait for all jobs to reach terminal state
    │   If any job failed → fail remaining stages (mark as "skipped")
    │   If all succeeded → proceed to next stage
    │
    ├── Stage 2: Same pattern
    │   ...
    │
    └── Final stage complete → mark run as succeeded/failed
```

**Concurrency control:** One run per pipeline at a time for MVP. Additional pushes while a run is active are queued (status: pending).

### 8.4 Runner ↔ Control Plane Protocol

**Runner polls for work:**
```
GET /api/runner/jobs/next
Authorization: Bearer {runner_token}

Response (job available):
{
  "job_id": "01HX...",
  "pipeline_run_id": "01HX...",
  "repository_url": "https://github.com/user/repo.git",
  "ref": "refs/heads/main",
  "sha": "abc123",
  "image": "php:8.3-cli",
  "commands": ["composer install", "npm run build"],
  "environment": {"APP_ENV": "testing", "DB_HOST": "postgres"},
  "services": ["postgres:16"],
  "timeout_minutes": 15,
  "artifact_config": {"paths": ["vendor/"], "exclude": [".git/"]}
}

Response (no job): 204 No Content
```

**Runner reports progress:**
```
PUT /api/runner/jobs/{id}/status
{
  "status": "running",
  "exit_code": null,
  "started_at": "...",
  "finished_at": null
}
```

**Runner streams logs:**
```
POST /api/runner/jobs/{id}/log
Content-Type: text/plain

[chunked log output, appended to S3 object]
```

Log chunks buffered and appended to S3 every 5 seconds or 64KB. UI reads from S3 with range requests for live tailing.

**Runner uploads artifact:**
```
POST /api/runner/jobs/{id}/artifact
Content-Type: application/gzip

[tar.gz binary stream]
```

Control plane streams to S3, computes SHA-256, creates Artifact record.

### 8.5 Artifact Immutability

- Artifacts are write-once. Once status is `ready`, the file is never modified.
- Each artifact is addressed by content hash.
- **Promotion:** Deploying the same artifact to a different environment creates a new Release pointing to the same Artifact with different config_snapshot. The artifact itself doesn't change.

### 8.6 Secret Injection

Secrets are NOT baked into artifacts. Injected at two points:

1. **Build time (CI):** Build-time secrets (private package tokens) passed as environment variables to the runner. Decrypted on the control plane, sent over TLS.
2. **Deploy time:** Runtime secrets included in the Release's config_snapshot and decrypted by the agent on the target node when writing `.env`.

Secrets never appear in logs. Runner and agent strip matching env var values from output.

### 8.7 What Goes Where

| Data | Storage |
|------|---------|
| Pipeline definitions | PostgreSQL (jsonb) |
| Pipeline run metadata | PostgreSQL |
| Job metadata and status | PostgreSQL |
| Job logs | S3 (streamed, chunked) |
| Artifacts | S3 (tar.gz) |
| Webhook payloads | PostgreSQL (jsonb, 30-day retention) |
| Runner tokens | PostgreSQL (encrypted) |
| Build cache (post-MVP) | S3 |

---

## SECTION 9 — DEPLOYMENT ENGINE DESIGN

### 9.1 Build Once, Deploy Many

The artifact is environment-agnostic. Contains:
- Application code (compiled, optimized)
- Vendor dependencies (composer install --no-dev)
- Compiled frontend assets

Does NOT contain:
- `.env` file (injected at deploy time)
- Storage directories (symlinked to shared storage)
- `.git` directory
- `node_modules/` (only compiled output)
- Test files

### 9.2 Artifact Packaging

Runner executes `helm-package` (bundled script):
1. `composer install --no-dev --optimize-autoloader`
2. `npm ci && npm run build`
3. Remove: `.git/`, `node_modules/`, `tests/`, `.env*`
4. Create tar.gz
5. Compute SHA-256 hash
6. Upload to S3: `artifacts/{app_id}/{run_id}/{hash}.tar.gz`

### 9.3 Release Creation

```php
$release = Release::create([
    'environment_id' => $environment->id,
    'artifact_id' => $artifact->id,
    'version' => $environment->releases()->max('version') + 1,
    'status' => 'pending',
    'config_snapshot' => [
        'env_vars' => $environment->variables->pluck('value', 'key'),
        'secrets' => $environment->secrets->pluck('key'), // keys only
        'processes' => $environment->processDefinitions->toArray(),
        'runtime' => $application->runtime,
        'php_version' => $application->settings['php_version'] ?? '8.3',
    ],
    'deployed_by' => auth()->id(),
]);
```

Config snapshot captures the environment's state at release time. Even if env vars change later, this release deploys with the snapshotted config.

### 9.4 Deployment Coordination

```
ExecuteDeployment job:
    │
    ├── Resolve target nodes: cluster → web-role nodes (active, not cordoned)
    ├── Create DeploymentStep per node
    │
    ├── Sequential rolling (MVP):
    │   for each node in sort_order:
    │     1. Mark step as "deploying"
    │     2. Send deploy command to agent
    │     3. Wait for agent result (poll with timeout)
    │     4. If success: mark step "deployed", run health check
    │     5. If health check passes: mark step "active"
    │     6. If health check fails: mark step "failed", trigger rollback
    │
    ├── All succeeded → deployment "succeeded", release "active"
    │   Previous active release → "superseded"
    │
    └── Any failure → deployment "failed", trigger rollback
```

### 9.5 Agent Deploy Command

```json
{
  "command": "deploy",
  "release_id": "01HX...",
  "artifact_url": "https://s3.example.com/artifacts/...",
  "artifact_hash": "sha256:abc123...",
  "config": {
    "env_vars": {"APP_ENV": "production", "DB_HOST": "10.0.1.5"},
    "secrets": {"APP_KEY": "base64:...", "DB_PASSWORD": "..."},
    "processes": [
      {"type": "web", "command": "php-fpm", "instances": 1},
      {"type": "worker", "command": "php artisan queue:work --max-jobs=1000", "instances": 2},
      {"type": "scheduler", "command": "php artisan schedule:work", "instances": 1}
    ],
    "php_version": "8.3",
    "shared_dirs": ["storage"],
    "writable_dirs": ["storage/framework/views", "storage/logs", "bootstrap/cache"],
    "pre_activate": [
      "php artisan migrate --force",
      "php artisan config:cache",
      "php artisan route:cache",
      "php artisan view:cache"
    ],
    "post_activate": [
      "php artisan queue:restart"
    ]
  }
}
```

### 9.6 Agent Deploy Execution (on server)

```
/home/helm/
├── apps/
│   └── {app_slug}/
│       ├── releases/
│       │   ├── 20260327-001/     ← previous
│       │   ├── 20260327-002/     ← current
│       │   └── 20260328-001/     ← deploying
│       ├── current → releases/20260327-002/  (symlink)
│       ├── shared/
│       │   └── storage/
│       └── .env
```

Steps:
1. Download artifact from S3, verify SHA-256
2. Extract to `releases/{release_id}/`
3. Symlink shared directories (`storage/` → `../../shared/storage/`)
4. Write `.env` file from decrypted secrets + env vars
5. Set permissions
6. Run pre-activate commands (`migrate`, `config:cache`)
7. Atomic symlink swap: `current` → new release
8. Reload PHP-FPM
9. Run post-activate commands (`queue:restart`)
10. Report success

On failure:
- Pre-activate failure → don't swap symlink, report failure
- Post-activate failure → swap symlink back, report failure
- Cleanup failed release directory

### 9.7 Health Check After Deploy

1. Wait 5 seconds (PHP-FPM reload grace)
2. HTTP GET to configured endpoint
3. Retry up to `healthy_threshold` times
4. If all pass → healthy
5. If `unhealthy_threshold` consecutive failures → rollback

### 9.8 Rollback

**Automatic** (on deployment failure):
1. For each updated node: agent swaps symlink to previous release, reloads PHP-FPM
2. Mark deployment `rolled_back`, release `rolled_back`
3. Previous release stays `active`

**Manual:**
1. Select previous release → new Deployment created → normal rolling deploy

### 9.9 Migration Safety

Migrations run BEFORE symlink swap. If they fail, old code still serves. Migrations must be backward-compatible during rolling deploys (some nodes old code, some new). Destructive changes need two deploys: remove usage first, then remove column.

### 9.10 Release Retention

Keep last 5 releases per environment on disk. Older directories deleted by cleanup job. S3 artifacts: 30 days after superseded.

---

## SECTION 10 — AGENT AND RUNNER DESIGN

### 10.1 Node Agent

**Technology:** Lightweight Go binary. Single static binary, ~10MB. No runtime dependencies.

**Why Go?** Cross-compiles to Linux, static binaries, efficient, good stdlib for HTTP and system ops. The agent is intentionally not PHP — it needs zero dependencies on the target server.

**Responsibilities:**
- Heartbeat with basic system metrics
- Poll and execute commands from control plane
- Download and extract deployment artifacts
- Manage symlinks and permissions
- Write `.env` files
- Execute shell commands (migrations, cache, restarts)
- Generate systemd unit files for processes
- Report command results

**Command Flow:**
```
Control Plane                    Agent
     │                              │
     │  Queue command in DB         │
     │                              │
     │◄─── GET /api/agent/commands/pending (every 10s)
     │                              │
     │───► [{command_id, type, payload, ttl}]
     │                              │
     │                   Execute    │
     │                              │
     │◄─── POST /api/agent/commands/{id}/result
     │      {status, output, error} │
```

**Authentication:**
- Unique token generated during bootstrap, stored at `/etc/helm/agent.conf`
- Hashed in DB, sent as `Authorization: Bearer {token}`
- Revocable and rotatable from control plane

**Heartbeat (every 30s):**
```json
POST /api/agent/heartbeat
{
  "server_id": "01HX...",
  "cpu_percent": 23.5,
  "memory_percent": 67.2,
  "disk_percent": 45.0,
  "load_avg": [1.2, 0.8, 0.5],
  "uptime_seconds": 86400,
  "agent_version": "0.1.0"
}
```

**Security:**
- Runs as dedicated `helm` user with scoped sudo
- Config at `/etc/helm/agent.conf` with mode 0600
- TLS required
- Only accepts pre-defined command types
- File operations restricted to `/home/helm/`

**Process Management:**
Agent generates systemd service units:
```ini
[Unit]
Description=Helm - {app} web
After=network.target
[Service]
User=helm
Group=helm
WorkingDirectory=/home/helm/apps/{app}/current
ExecStart=/usr/bin/php-fpm --nodaemonize
Restart=always
[Install]
WantedBy=multi-user.target
```

### 10.2 CI Runner

**Technology:** Go binary that manages Docker containers.

**Responsibilities:**
- Poll for pipeline jobs
- Pull Docker images
- Clone git repos
- Run commands inside Docker containers
- Stream logs to control plane
- Upload artifacts to S3 via control plane
- Manage container lifecycle
- Support service containers (postgres, redis for tests)

**Job Execution Flow:**
1. Poll `GET /api/runner/jobs/next`
2. Pull Docker image
3. Start service containers on private Docker network
4. Clone repo at SHA
5. Mount repo, inject env vars
6. Execute commands sequentially
7. Stream stdout/stderr every 5s
8. If `creates_artifact`: package and upload
9. Report final status
10. Cleanup containers and workspace

**Authentication:** Same token model as agent.

**Heartbeat (every 15s):**
```json
POST /api/runner/heartbeat
{
  "runner_id": "01HX...",
  "status": "online",
  "current_job_id": null,
  "docker_available": true,
  "disk_free_gb": 50
}
```

**Log Streaming:**
- Capture Docker stdout/stderr
- Buffer 64KB chunks or 5-second windows
- POST to `/api/runner/jobs/{id}/log`
- Control plane appends to S3 multipart upload
- UI reads via byte-range requests

**Security:**
- Run on dedicated infrastructure (not app servers)
- Docker socket access required
- Job containers run as non-root
- Secrets as env vars only, never on disk
- Workspace cleaned after each job

**API Contract:**

```
# Agent endpoints (control plane serves)
POST   /api/agent/heartbeat
GET    /api/agent/commands/pending
POST   /api/agent/commands/{id}/result
POST   /api/agent/commands/{id}/log

# Runner endpoints (control plane serves)
GET    /api/runner/jobs/next
PUT    /api/runner/jobs/{id}/status
POST   /api/runner/jobs/{id}/log
POST   /api/runner/jobs/{id}/artifact
POST   /api/runner/heartbeat
```

---

## SECTION 11 — AUTHORIZATION AND SECURITY MODEL

### 11.1 Auth Model (Simplified)

This is a personal tool. No multi-tenancy, no RBAC, no teams.

**Web auth:** Laravel Fortify session-based. You log in, you have full access.
**API auth:** Personal access tokens. Bearer token on every request.
**Agent auth:** Per-server tokens. Separate middleware.
**Runner auth:** Per-runner tokens. Separate middleware.

No permission checks beyond "is authenticated" for web/API routes. Agent/runner routes validate the specific token matches a registered server/runner.

### 11.2 Secret Encryption

Laravel's built-in `Crypt` (AES-256-GCM via `APP_KEY`):
```php
$secret->encrypted_value = Crypt::encryptString($plaintext);
$plaintext = Crypt::decryptString($secret->encrypted_value);
```

Secret values never returned by API unless explicitly requested. Reveal action is audit-logged.

### 11.3 Audit Logging

Every mutation recorded:
```php
AuditLog::create([
    'user_id' => auth()->id(),
    'action' => 'server.created',
    'auditable_type' => Server::class,
    'auditable_id' => $server->id,
    'old_values' => $old,
    'new_values' => $new,
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);
```

Implemented via event listener on all domain events.

### 11.4 MFA

Fortify 2FA already configured. Optional for now. TOTP-based.

### 11.5 SSH Key Management

- Upload public keys to profile
- Keys pushed to all managed servers during bootstrap
- Key changes pushed via agent command
- Platform generates deploy key pair per server for git operations

---

## SECTION 12 — BACKGROUND JOBS AND EVENTS

### 12.1 Command Jobs

| Job | Queue | Description |
|-----|-------|-------------|
| `ProvisionServer` | infrastructure | Create server via provider API |
| `BootstrapServer` | infrastructure | SSH into server, install packages, install agent |
| `DrainNode` | infrastructure | Stop routing, wait for connections |
| `DecommissionNode` | infrastructure | Remove from cluster, cleanup |
| `PushSshKeys` | infrastructure | Update authorized_keys on server |
| `PushProxyConfig` | infrastructure | Push Caddy config to web nodes |

### 12.2 Pipeline Jobs

| Job | Queue | Description |
|-----|-------|-------------|
| `ProcessWebhook` | pipeline | Parse webhook, evaluate triggers, create runs |
| `OrchestrateRun` | pipeline | Coordinate stages/jobs for a pipeline run |
| `CheckJobTimeout` | pipeline | Check for jobs exceeding timeout |
| `CleanupOldArtifacts` | maintenance | Delete expired artifacts |

### 12.3 Deployment Jobs

| Job | Queue | Description |
|-----|-------|-------------|
| `ExecuteDeployment` | deployment | Orchestrate rolling deployment |
| `DeployToNode` | deployment | Send deploy command to node agent |
| `RunPostDeployHealthCheck` | deployment | HTTP health check after deploy |
| `ExecuteRollback` | deployment | Coordinate rollback |

### 12.4 Networking Jobs

| Job | Queue | Description |
|-----|-------|-------------|
| `RenewExpiringCertificates` | maintenance | Check for expiring certs |
| `PushProxyConfig` | infrastructure | Generate and push proxy config |
| `VerifyDomain` | default | Check DNS records |

### 12.5 Operations Jobs

| Job | Queue | Description |
|-----|-------|-------------|
| `ExecuteBackup` | maintenance | Run backup via agent (post-MVP) |
| `ApplyRetentionPolicy` | maintenance | Delete expired backups/artifacts |
| `PurgeOldAuditLogs` | maintenance | Clean old audit logs |

### 12.6 Events and Listeners

| Event | Listeners |
|-------|-----------|
| `ServerRegistered` | RecordAuditLog |
| `ServerBootstrapped` | UpdateClusterStatus, PushSshKeys, RecordAuditLog |
| `ServerHealthChanged` | UpdateClusterStatus, RecordAuditLog |
| `ClusterTopologyChanged` | RegenerateProxyConfigs, RecordAuditLog |
| `ApplicationCreated` | CreateDefaultEnvironments, SetupWebhook, RecordAuditLog |
| `EnvironmentConfigChanged` | RecordAuditLog |
| `PipelineRunStarted` | RecordAuditLog |
| `PipelineRunCompleted` | CreateArtifactIfSucceeded, TriggerAutoDeployIfEnabled, RecordAuditLog |
| `PipelineJobCompleted` | WakeOrchestrator, RecordAuditLog |
| `ArtifactCreated` | RecordAuditLog |
| `DeploymentStarted` | RecordAuditLog |
| `DeploymentCompleted` | ActivateRelease, CleanupOldReleases, RecordAuditLog |
| `DeploymentFailed` | TriggerRollback, RecordAuditLog |
| `RollbackCompleted` | RecordAuditLog |
| `DomainAssigned` | VerifyDomain, RecordAuditLog |
| `DomainVerified` | RegenerateProxyConfigs, RecordAuditLog |
| `SecretUpdated` | RecordAuditLog (never logs the value) |

### 12.7 Scheduled Tasks

```php
$schedule->job(new CheckJobTimeout)->everyMinute();
$schedule->job(new RenewExpiringCertificates)->daily();
$schedule->job(new CleanupOldArtifacts)->daily();
$schedule->job(new ApplyRetentionPolicy)->daily();
```

---

## SECTION 13 — API DESIGN

All API routes prefixed `/api/v1`. Auth via session (Inertia) or Bearer token.

### 13.1 Auth

```
POST   /auth/login
POST   /auth/logout
GET    /auth/user

GET    /ssh-keys
POST   /ssh-keys
DELETE /ssh-keys/{key}

GET    /tokens
POST   /tokens
DELETE /tokens/{token}
```

### 13.2 Infrastructure

```
GET    /providers
POST   /providers
PUT    /providers/{provider}
DELETE /providers/{provider}
POST   /providers/{provider}/test

GET    /servers
POST   /servers
GET    /servers/{server}
PUT    /servers/{server}
DELETE /servers/{server}
POST   /servers/{server}/bootstrap
POST   /servers/{server}/drain
POST   /servers/{server}/cordon
POST   /servers/{server}/activate

GET    /clusters
POST   /clusters
GET    /clusters/{cluster}
PUT    /clusters/{cluster}
DELETE /clusters/{cluster}
POST   /clusters/{cluster}/nodes
DELETE /clusters/{cluster}/nodes/{server}
PUT    /clusters/{cluster}/nodes/{server}
```

### 13.3 Application Platform

```
GET    /projects
POST   /projects
GET    /projects/{project}
PUT    /projects/{project}
DELETE /projects/{project}

GET    /git-connections
POST   /git-connections/github/authorize
DELETE /git-connections/{connection}

GET    /projects/{project}/applications
POST   /projects/{project}/applications
GET    /applications/{app}
PUT    /applications/{app}
DELETE /applications/{app}

GET    /applications/{app}/environments
POST   /applications/{app}/environments
GET    /environments/{env}
PUT    /environments/{env}
DELETE /environments/{env}

GET    /environments/{env}/variables
POST   /environments/{env}/variables
PUT    /environments/{env}/variables/{var}
DELETE /environments/{env}/variables/{var}

GET    /environments/{env}/secrets
POST   /environments/{env}/secrets
PUT    /environments/{env}/secrets/{secret}
DELETE /environments/{env}/secrets/{secret}
GET    /environments/{env}/secrets/{secret}/reveal

GET    /environments/{env}/processes
POST   /environments/{env}/processes
PUT    /environments/{env}/processes/{process}
DELETE /environments/{env}/processes/{process}
```

### 13.4 Pipelines

```
GET    /applications/{app}/pipelines
POST   /applications/{app}/pipelines
GET    /pipelines/{pipeline}
PUT    /pipelines/{pipeline}
DELETE /pipelines/{pipeline}

POST   /pipelines/{pipeline}/trigger
GET    /pipelines/{pipeline}/runs
GET    /pipeline-runs/{run}
POST   /pipeline-runs/{run}/cancel
POST   /pipeline-runs/{run}/retry

GET    /pipeline-jobs/{job}
GET    /pipeline-jobs/{job}/log

GET    /runners
POST   /runners
GET    /runners/{runner}
DELETE /runners/{runner}

GET    /applications/{app}/artifacts
GET    /artifacts/{artifact}
```

### 13.5 Deployments

```
GET    /environments/{env}/deployments
POST   /environments/{env}/deploy
GET    /deployments/{deployment}
POST   /deployments/{deployment}/cancel

GET    /environments/{env}/releases
GET    /releases/{release}
POST   /environments/{env}/rollback
```

### 13.6 Networking

```
GET    /environments/{env}/domains
POST   /environments/{env}/domains
DELETE /domains/{domain}
POST   /domains/{domain}/verify
GET    /domains/{domain}/certificate
```

### 13.7 Operations

```
GET    /audit-logs
GET    /activity
```

### 13.8 Agent & Runner Internal APIs

```
# Agent (routes/agent.php)
POST   /api/agent/heartbeat
GET    /api/agent/commands/pending
POST   /api/agent/commands/{id}/result
POST   /api/agent/commands/{id}/log

# Runner (routes/runner.php)
GET    /api/runner/jobs/next
PUT    /api/runner/jobs/{id}/status
POST   /api/runner/jobs/{id}/log
POST   /api/runner/jobs/{id}/artifact
POST   /api/runner/heartbeat
```

### 13.9 Webhooks

```
# (routes/webhooks.php) — no auth, signature-verified
POST   /webhooks/{application}/{provider}
```

---

## SECTION 14 — UI / PANEL INFORMATION ARCHITECTURE

### 14.1 Navigation

```
Sidebar:
├── Dashboard
├── Servers
├── Clusters
├── Projects
│   └── {Project}
│       └── {Application}
│           ├── Environments
│           ├── Pipelines
│           ├── Deployments
│           └── Settings
├── Runners
├── Activity
└── Settings
    ├── Profile
    ├── SSH Keys
    ├── API Tokens
    └── Providers
```

### 14.2 Screens

**Dashboard**
- Server count by status
- Cluster health summary
- Application count
- Recent deployments (last 10): app, env, status, when
- Recent pipeline runs (last 10): app, status, trigger, duration

**Servers List**
- Table: name, IP, cluster, roles, status, last heartbeat
- Actions: register, bootstrap, drain, cordon, decommission

**Server Detail**
- Name, IP, provider, region, OS, specs
- Status badge with state actions
- Cluster membership and roles
- Agent status
- Recent commands

**Clusters List**
- Name, status, node count by role, app count

**Cluster Detail**
- Node list with roles and status
- Add/remove nodes
- Applications deployed here

**Project List**
- Name, description, app count

**Application Detail (tabs)**
- **Overview:** repo, runtime, last deploy per env
- **Environments:** list with cluster, branch, active release, domains
- **Pipelines:** definitions, recent runs
- **Deployments:** history across all envs
- **Settings:** repo connection, runtime, delete

**Environment Detail**
- Active release
- Env vars editor (inline key-value table)
- Secrets editor (key list, reveal button)
- Process definitions
- Domain list with SSL status
- Health check config
- Deploy + rollback buttons

**Pipeline Run Detail**
- Trigger info (sha, branch)
- Stage visualization (sequential boxes with parallel job cards)
- Click job → log viewer (terminal output)
- Retry / cancel buttons
- Artifact info

**Runners List**
- Name, status, current job, last heartbeat
- Register new (shows token once)

**Activity Feed**
- Chronological audit events
- Filter by resource type, action, date

**Settings**
- Profile (name, email, password, 2FA)
- SSH Keys (list, add, remove)
- API Tokens (list, create, revoke)
- Providers (credentials, test connection)

### 14.3 Key User Flows

**First Setup:**
Register → Add Provider → Register Server → Bootstrap → Create Cluster → Add Node → Create Project → Create App → Connect Git → Configure Env → Create Pipeline → Deploy

**Push to Production:**
Git push → Webhook → Pipeline runs → Artifact built → Auto-deploy staging → Manual deploy production → Rolling deploy → Health check → Live

**Rollback:**
Open environment → Rollback button → Select release → Confirm → Rolling rollback → Healthy

---

## SECTION 15 — IMPLEMENTATION ROADMAP

### Phase 0: Foundation (Week 1)

**Deliverables:**
- PostgreSQL configured as default DB
- Redis for cache, queue, session
- Base module directory structure
- `config/helm.php`
- ULID trait for models
- Object storage integration (S3 disk)
- Base test setup with PostgreSQL
- Inertia layout with sidebar skeleton

**Risks:** None. Standard Laravel setup.

### Phase 1: Auth, Projects, Settings (Weeks 2-3)

**Deliverables:**
- Personal access tokens (custom, not Sanctum)
- SSH key management
- Project CRUD
- Settings pages (profile, keys, tokens)
- Audit log model + listener
- Activity feed

**Key Tables:** personal_access_tokens, ssh_keys, projects, audit_logs

**API:** /auth/*, /ssh-keys/*, /tokens/*, /projects/*, /audit-logs/*

### Phase 2: Infrastructure (Weeks 4-6)

**Deliverables:**
- Provider credentials management
- Server registration (manual + DO API)
- Server state machine
- SSH bootstrap service
- Agent token generation
- Cluster CRUD + node assignment
- Cluster state machine
- Agent heartbeat endpoint
- Agent command queue
- Server/cluster UI

**Key Tables:** providers, servers, clusters, cluster_node

**Key Jobs:** ProvisionServer, BootstrapServer, DrainNode, PushSshKeys

**Risks:** SSH fragility. Support Ubuntu 22.04/24.04 only initially.

### Phase 3: Apps, Environments, Secrets (Weeks 7-9)

**Deliverables:**
- Application CRUD with git connection
- GitHub OAuth flow
- Environment management
- Env vars + secrets CRUD
- Process definitions
- Webhook setup
- App/environment UI

**Key Tables:** git_connections, applications, environments, environment_variables, secrets, process_definitions, webhooks

### Phase 4: Pipelines, Runners, Artifacts (Weeks 10-14)

**Deliverables:**
- Pipeline definitions
- Webhook ingestion + trigger evaluation
- PipelineRun orchestration
- PipelineJob lifecycle
- Runner registration + API
- Artifact storage to S3
- Log streaming
- Pipeline UI with stage visualization + log viewer
- Runner management UI

**Key Tables:** pipelines, pipeline_runs, pipeline_jobs, runners, artifacts

**Key Jobs:** ProcessWebhook, OrchestrateRun, CheckJobTimeout, CleanupOldArtifacts

**Risks:** Most complex phase. Runner binary (Go) parallel development needed.

### Phase 5: Deployments (Weeks 15-18)

**Deliverables:**
- Release model + config snapshots
- Rolling deployment orchestration
- Agent deploy commands
- Health checks
- Automatic + manual rollback
- Symlink release management (agent side)
- Process management (systemd units)
- Migration + queue restart handling
- Deploy button + deployment history UI

**Key Tables:** releases, deployments, deployment_steps, health_checks

**Key Jobs:** ExecuteDeployment, DeployToNode, RunPostDeployHealthCheck, ExecuteRollback

**Risks:** Most critical phase. Agent implementation must be solid.

### Phase 6: Networking, Polish (Weeks 19-22)

**Deliverables:**
- Domain assignment
- DNS verification
- Caddy proxy config generation + push
- Let's Encrypt via Caddy
- Certificate tracking
- Dashboard with summaries
- Error handling polish
- Documentation

**Key Tables:** domains, certificates

---

## SECTION 16 — FIRST MIGRATIONS TO WRITE

### Batch 1: Foundation
```
2026_04_01_000001_create_personal_access_tokens_table.php
2026_04_01_000002_create_ssh_keys_table.php
2026_04_01_000003_create_projects_table.php
2026_04_01_000004_create_audit_logs_table.php
```

### Batch 2: Infrastructure
```
2026_04_15_000001_create_providers_table.php
2026_04_15_000002_create_servers_table.php
2026_04_15_000003_create_clusters_table.php
2026_04_15_000004_create_cluster_node_table.php
```

### Batch 3: Applications
```
2026_05_01_000001_create_git_connections_table.php
2026_05_01_000002_create_applications_table.php
2026_05_01_000003_create_environments_table.php
2026_05_01_000004_create_environment_variables_table.php
2026_05_01_000005_create_secrets_table.php
2026_05_01_000006_create_process_definitions_table.php
```

### Batch 4: Pipeline
```
2026_05_15_000001_create_pipelines_table.php
2026_05_15_000002_create_pipeline_runs_table.php
2026_05_15_000003_create_pipeline_jobs_table.php
2026_05_15_000004_create_runners_table.php
2026_05_15_000005_create_artifacts_table.php
2026_05_15_000006_create_webhooks_table.php
```

### Batch 5: Deployment
```
2026_06_01_000001_create_releases_table.php
2026_06_01_000002_create_deployments_table.php
2026_06_01_000003_create_deployment_steps_table.php
2026_06_01_000004_create_health_checks_table.php
2026_06_01_000005_add_active_release_to_environments_table.php
```

### Batch 6: Networking
```
2026_06_15_000001_create_domains_table.php
2026_06_15_000002_create_certificates_table.php
```

---

## SECTION 17 — FIRST LARAVEL CLASSES TO CREATE

### Phase 0: Foundation

**Support:**
- `app/Support/Concerns/HasUlid.php`
- `app/Support/Concerns/HasStateMachine.php`
- `app/Support/Enums/QueueName.php`
- `app/Support/Services/ObjectStorage/ObjectStorageService.php`
- `config/helm.php`

### Phase 1: Auth + Projects

**Models:**
- `app/Modules/AppPlatform/Models/Project.php`
- `app/Models/PersonalAccessToken.php`
- `app/Models/SshKey.php`
- `app/Modules/Operations/Models/AuditLog.php`

**Actions:**
- `app/Modules/AppPlatform/Actions/CreateProject.php`
- `app/Modules/Operations/Actions/RecordAuditLog.php`

**Events:**
- `app/Modules/AppPlatform/Events/ProjectCreated.php`

**Listeners:**
- `app/Modules/Operations/Listeners/RecordAuditLog.php`

**Controllers:**
- `app/Http/Controllers/Api/AppPlatform/ProjectController.php`
- `app/Http/Controllers/Api/SshKeyController.php`
- `app/Http/Controllers/Api/PersonalAccessTokenController.php`
- `app/Http/Controllers/Api/Operations/AuditLogController.php`

**Requests:**
- `app/Http/Requests/AppPlatform/CreateProjectRequest.php`
- `app/Http/Requests/AddSshKeyRequest.php`
- `app/Http/Requests/CreateTokenRequest.php`

### Phase 2: Infrastructure

**Models:**
- `app/Modules/Infrastructure/Models/Provider.php`
- `app/Modules/Infrastructure/Models/Server.php`
- `app/Modules/Infrastructure/Models/Cluster.php`

**Enums:**
- `app/Modules/Infrastructure/Enums/ServerStatus.php`
- `app/Modules/Infrastructure/Enums/ClusterStatus.php`
- `app/Modules/Infrastructure/Enums/NodeRole.php`
- `app/Modules/Infrastructure/Enums/ProviderType.php`

**Actions:**
- `app/Modules/Infrastructure/Actions/RegisterServer.php`
- `app/Modules/Infrastructure/Actions/BootstrapServer.php`
- `app/Modules/Infrastructure/Actions/CreateCluster.php`

**Services:**
- `app/Modules/Infrastructure/Services/SshService.php`
- `app/Modules/Infrastructure/Services/ServerBootstrapper.php`
- `app/Modules/Infrastructure/Services/Providers/ProviderInterface.php`
- `app/Modules/Infrastructure/Services/Providers/DigitalOceanProvider.php`

**StateMachines:**
- `app/Modules/Infrastructure/StateMachines/ServerStateMachine.php`
- `app/Modules/Infrastructure/StateMachines/ClusterStateMachine.php`

**Jobs:**
- `app/Modules/Infrastructure/Jobs/ProvisionServer.php`
- `app/Modules/Infrastructure/Jobs/BootstrapServer.php`

---

## SECTION 18 — CODING STANDARDS AND ARCHITECTURAL RULES

### Business Logic Location

| Pattern | Purpose | Where |
|---------|---------|-------|
| **Action** | Single-purpose business operation. One public `execute()` method. | `app/Modules/{Module}/Actions/` |
| **Service** | Stateless service for infrastructure concerns (SSH, API clients, config generation). | `app/Modules/{Module}/Services/` |
| **State Machine** | Validates and enforces state transitions. | `app/Modules/{Module}/StateMachines/` |

Business logic NEVER lives in controllers, models, jobs, or listeners.

### Controller Rules

Controllers are thin: validate (FormRequest), call Action, return response. Max one Action per method.

```php
// Good
public function store(RegisterServerRequest $request): ServerResource
{
    $server = (new RegisterServer)->execute(
        RegisterServerData::from($request->validated())
    );
    return new ServerResource($server);
}

// Bad
public function store(Request $request)
{
    $server = Server::create($request->all());
    dispatch(new BootstrapServer($server));
    return response()->json($server);
}
```

### Model Rules

Models define: relationships, scopes, casts, accessors, mutators. No business logic. No create/update helpers with business rules.

### Job Rules

Jobs orchestrate — they call Actions. Must be idempotent. Must handle failure (mark resources as failed). Must specify queue. Long-running jobs use `$timeout`.

### Action Pattern

```php
class RegisterServer
{
    public function execute(RegisterServerData $data): Server
    {
        return DB::transaction(function () use ($data) {
            $server = Server::create([...]);
            event(new ServerRegistered($server));
            return $server;
        });
    }
}
```

### Event Rules

- Named in past tense: `ServerBootstrapped`, not `BootstrapServer`
- Carry minimal data (model instance)
- Dispatched inside Actions after transaction
- Listeners handle cross-cutting: audit, notifications

### Transaction Handling

- `DB::transaction()` for multi-model mutations
- `afterCommit` on queued listeners
- No external API calls inside transactions
- Keep transactions short

### Idempotency

- Jobs check current state before acting
- Agent commands deduplicate by command ID
- Webhook processing deduplicates by commit SHA
- Pipeline orchestration checks status before advancing

### Testing

- Every Action gets a unit test
- Every API endpoint gets a feature test (happy + error)
- State machines tested for all transitions
- No external service calls in tests (mock at service boundary)
- Factories for every model

---

## SECTION 19 — TESTING STRATEGY

### Unit Tests
**Target:** Actions, StateMachines, DTOs, Enums, Services (mocked deps)
**Location:** `tests/Unit/Modules/{Module}/`

Use PostgreSQL for all tests (not SQLite). PostgreSQL features (jsonb, inet) won't work in SQLite.

### Feature Tests
**Target:** API endpoints, web routes, middleware, auth
**Location:** `tests/Feature/Api/{Module}/`, `tests/Feature/Web/`

Test each endpoint with valid input, validation errors, not-found. Use `actingAs()`.

### Integration Tests
**Target:** Multi-step workflows spanning modules
**Location:** `tests/Integration/`

- Deployment workflow: app → env → pipeline → webhook → run → artifact → deploy → healthy
- Pipeline workflow: trigger → jobs → completion
- Rollback workflow: deploy v1 → deploy v2 → fail → rollback to v1

### Contract Tests
**Target:** Agent and runner API contracts
**Location:** `tests/Contract/`

Verify request/response shapes. These serve as spec for the Go binaries.

### Test Infrastructure
- `phpunit.xml` with PostgreSQL test DB
- Parallel execution (`--parallel`)
- Factories for all models
- Test helpers for common setup

---

## SECTION 20 — RISKS / OPEN QUESTIONS

### High-Priority Risks

1. **Agent is a separate Go project.** Needs parallel development. Mitigation: define protocol contract first, build contract tests, stub with shell script initially.

2. **SSH bootstrap fragility.** Different OS configs, firewalls, SSH key formats. Mitigation: support Ubuntu 22.04/24.04 only. Test on clean DO/Hetzner images.

3. **CI runner complexity.** Docker management, log streaming, timeouts, artifacts. Mitigation: simplest possible runner first (single job, no caching, no services).

4. **Zero-downtime migration constraint.** Rolling deploys require backward-compatible migrations. Mitigation: document clearly, enforce as team standard.

5. **Secret exposure surface.** Secrets decrypted on control plane, sent to agents/runners. Mitigation: TLS everywhere, log scrubbing, audit logging.

### Medium-Priority Risks

6. **Log streaming latency.** S3 polling works but isn't instant. Start with polling, add WebSocket/SSE later.

7. **Object storage dependency.** S3/MinIO required. Self-hosted = run MinIO. Document setup clearly.

8. **Multi-runtime support.** PHP-first but architecture should handle others. Use `runtime` enum, keep language specifics in strategy classes.

### Open Questions

| # | Question | Recommendation |
|---|----------|---------------|
| 1 | Caddy vs Nginx? | **Caddy** — auto HTTPS, simpler config |
| 2 | systemd vs Supervisor? | **systemd** — already present on Ubuntu |
| 3 | Agent distribution? | Download from control plane during bootstrap |
| 4 | Pipeline definition: YAML in repo vs UI JSON? | **UI JSON for MVP**, YAML later |
| 5 | Agent protocol: REST vs WebSocket? | **REST polling for MVP** |

---

## FINAL SUMMARY

### MVP Architecture

Laravel 13 modular monolith, PostgreSQL, Redis, S3-compatible storage. React/TS via Inertia v3. Single-user, no multi-tenancy overhead.

Core flow: Register servers → bootstrap via SSH → create clusters → define apps → CI pipelines build artifacts → rolling deployments via node agents → Caddy with auto-HTTPS.

Two Go binaries: node agent + CI runner. Both communicate via authenticated REST polling.

### Database Build Order

```
1. personal_access_tokens, ssh_keys     (auth)
2. projects                              (app grouping)
3. audit_logs                            (observability from day 1)
4. providers                             (cloud creds)
5. servers                               (server inventory)
6. clusters, cluster_node                (topology)
7. git_connections                       (repo access)
8. applications                          (app definitions)
9. environments                          (deploy targets)
10. environment_variables, secrets        (config)
11. process_definitions                   (processes)
12. pipelines                             (CI definitions)
13. pipeline_runs, pipeline_jobs          (CI execution)
14. runners                               (CI workers)
15. artifacts, webhooks                   (CI outputs)
16. releases                              (deploy units)
17. deployments, deployment_steps         (deploy execution)
18. health_checks                         (verification)
19. domains, certificates                 (networking)
```

### Codebase Folder Tree

```
helm/
├── app/
│   ├── Modules/
│   │   ├── Infrastructure/    (servers, clusters, agents, providers)
│   │   ├── AppPlatform/       (projects, apps, environments, secrets)
│   │   ├── Pipeline/          (CI/CD pipelines, jobs, runners, artifacts)
│   │   ├── Deployment/        (releases, deployments, health checks)
│   │   ├── Networking/        (domains, certificates, proxy config)
│   │   ├── ServiceManagement/ (databases, caches, processes — post-MVP)
│   │   ├── Observability/     (metrics, alerts — post-MVP)
│   │   └── Operations/        (backups, audit logs)
│   ├── Http/
│   │   ├── Controllers/Api/{Module}/
│   │   ├── Controllers/Web/
│   │   ├── Middleware/
│   │   ├── Requests/{Module}/
│   │   └── Resources/{Module}/
│   ├── Console/Commands/
│   ├── Providers/
│   ├── Support/
│   └── Models/User.php
├── config/helm.php
├── database/migrations/
├── routes/ (web, api, agent, runner, webhooks)
├── resources/js/pages/ (dashboard, servers, clusters, apps, pipelines, deployments, settings)
└── tests/ (Unit/Modules, Feature/Api, Integration, Contract)
```

### Top 10 First Tickets

| # | Title | Phase |
|---|-------|-------|
| 1 | Configure PostgreSQL, Redis, S3 filesystem disks | 0 |
| 2 | Create module structure, ULID trait, config/helm.php | 0 |
| 3 | Personal access tokens: model, migration, CRUD, API auth guard | 1 |
| 4 | SSH keys: model, migration, CRUD, UI | 1 |
| 5 | Projects: model, migration, CRUD action, API, UI | 1 |
| 6 | Audit log: model, migration, event listener, activity feed | 1 |
| 7 | Sidebar navigation + dashboard layout | 1 |
| 8 | Servers: model, migration, state machine, registration, list/detail UI | 2 |
| 9 | Clusters: model, migration, node assignment, CRUD, list/detail UI | 2 |
| 10 | SSH service + server bootstrap: SshService, bootstrap script, BootstrapServer job | 2 |
