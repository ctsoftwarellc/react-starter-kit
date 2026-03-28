# Phase 2: Infrastructure — Implementation Plan

## Overview

Phase 2 delivers the Infrastructure module: providers, servers, clusters, SSH bootstrap, and agent API.

**Cross-module dependencies:**
- Operations module (already built) — RecordAuditLog listener extended with new events
- Auth models (SshKey) — PushSshKeys job needs user SSH keys
- AppServiceProvider — register new event-to-listener bindings

**Testing concern:** Using `string(45)` instead of `inet` for IP columns ensures SQLite test compatibility.

---

## Migrations (5 files)

### 1. `create_providers_table`
- id: ulid PK
- name: string(255) NOT NULL
- type: string(20) NOT NULL
- credentials: text NOT NULL (encrypted cast)
- is_active: boolean DEFAULT true
- timestamps

### 2. `create_servers_table`
- id: ulid PK
- provider_id: ulid NULLABLE FK → providers(id) ON DELETE SET NULL
- name: string(255) NOT NULL
- hostname: string(255) NOT NULL
- public_ip: string(45) NOT NULL
- private_ip: string(45) NULLABLE
- ssh_port: integer DEFAULT 22
- ssh_user: string(50) DEFAULT 'root'
- os: string(100) NULLABLE
- cpu_cores: integer NULLABLE
- memory_mb: integer NULLABLE
- disk_gb: integer NULLABLE
- region: string(100) NULLABLE
- status: string(30) NOT NULL DEFAULT 'pending'
- agent_token: text NULLABLE (encrypted cast)
- agent_token_hash: string(64) NULLABLE, indexed (for agent auth lookup)
- last_heartbeat_at: timestamp NULLABLE
- metadata: json DEFAULT '{}'
- timestamps, softDeletes
- Indexes: status, public_ip

### 3. `create_clusters_table`
- id: ulid PK
- name: string(255) NOT NULL
- slug: string(255) NOT NULL UNIQUE
- status: string(30) NOT NULL DEFAULT 'pending'
- settings: json DEFAULT '{}'
- timestamps

### 4. `create_cluster_node_table`
- id: ulid PK
- cluster_id: ulid FK → clusters(id) CASCADE
- server_id: ulid FK → servers(id) CASCADE
- role: string(20) NOT NULL
- is_active: boolean DEFAULT true
- sort_order: integer DEFAULT 0
- timestamps
- UNIQUE(cluster_id, server_id)

### 5. `create_agent_commands_table`
- id: ulid PK
- server_id: ulid FK → servers(id) CASCADE
- type: string(50) NOT NULL
- payload: json DEFAULT '{}'
- status: string(20) NOT NULL DEFAULT 'pending'
- result: json NULLABLE
- expires_at: timestamp NOT NULL
- created_at: timestamp (no updated_at)
- completed_at: timestamp NULLABLE
- Indexes: (server_id, status), status

---

## Enums (6 files) — `app/Modules/Infrastructure/Enums/`

- **ProviderType**: digitalocean, hetzner, vultr, aws, manual
- **ServerStatus**: pending, provisioning, bootstrapping, active, draining, cordoned, maintenance, decommissioning, decommissioned, failed
- **NodeRole**: web, worker, db, cache, queue, bastion
- **ClusterStatus**: pending, provisioning, active, updating, scaling, degraded, maintenance, decommissioning, decommissioned
- **AgentCommandStatus**: pending, running, completed, failed, expired
- **AgentCommandType**: bootstrap, deploy, update_proxy_config, push_ssh_keys, restart_process, custom

---

## Models (4 files) — `app/Modules/Infrastructure/Models/`

### Provider
- Traits: HasFactory, HasUlid
- Casts: type→ProviderType, credentials→encrypted:json, is_active→boolean
- Relations: hasMany(Server)

### Server
- Traits: HasFactory, HasUlid, HasStateMachine, SoftDeletes
- Casts: status→ServerStatus, agent_token→encrypted, metadata→json, last_heartbeat_at→datetime, ssh_port→integer
- Relations: belongsTo(Provider), belongsToMany(Cluster via cluster_node), hasMany(AgentCommand)
- State machine: Full 19-transition map from architecture Section 7.1

### Cluster
- Traits: HasFactory, HasUlid, HasStateMachine
- Casts: status→ClusterStatus, settings→json
- Relations: belongsToMany(Server via cluster_node)
- Route key: slug
- State machine: Full transition map from architecture Section 7.2

### AgentCommand
- Traits: HasUlid
- UPDATED_AT = null
- Casts: type→AgentCommandType, status→AgentCommandStatus, payload→json, result→json, expires_at→datetime, completed_at→datetime
- Relations: belongsTo(Server)
- Scopes: scopePending

---

## DTOs (5 files) — `app/Modules/Infrastructure/DTOs/`

- CreateProviderData: name, type, credentials
- UpdateProviderData: name?, credentials?, is_active?
- RegisterServerData: name, hostname, public_ip, private_ip?, ssh_port, ssh_user, provider_id?, os?, region?
- UpdateServerData: name?, hostname?, ssh_port?, ssh_user?, metadata?
- CreateClusterData: name, settings?

---

## Events (7 files) — `app/Modules/Infrastructure/Events/`

- ServerRegistered(Server)
- ServerBootstrapped(Server)
- ServerHealthChanged(Server, previousStatus, newStatus)
- ClusterTopologyChanged(Cluster, changeType)
- ProviderCreated(Provider)
- ProviderUpdated(Provider)
- ProviderDeleted(Provider)

---

## Actions (17 files) — `app/Modules/Infrastructure/Actions/`

### Provider: CreateProvider, UpdateProvider, DeleteProvider, TestProviderConnection
### Server: RegisterServer, UpdateServer, DeleteServer, BootstrapServer, DrainNode, CordonNode, ActivateNode, DecommissionNode
### Cluster: CreateCluster, UpdateCluster, DeleteCluster, AddNodeToCluster, RemoveNodeFromCluster, UpdateNodeRole

---

## Services — `app/Modules/Infrastructure/Services/`

- Providers/ProviderInterface.php
- Providers/DigitalOceanProvider.php
- Providers/ManualProvider.php
- SshService.php (uses phpseclib3)
- ServerBootstrapper.php

---

## Jobs (5 files) — `app/Modules/Infrastructure/Jobs/`

- BootstrapServer (queue: infrastructure, timeout: 300)
- ProvisionServer (queue: infrastructure, timeout: 120)
- PushSshKeys (queue: infrastructure, timeout: 60)
- DrainNode (queue: infrastructure, timeout: 120)
- DecommissionNode (queue: infrastructure, timeout: 120)

---

## Middleware

- AuthenticateAgent — lookup by agent_token_hash, set server on request

---

## HTTP Layer

### FormRequests (8) — `app/Http/Requests/Infrastructure/`
- CreateProviderRequest, UpdateProviderRequest
- RegisterServerRequest, UpdateServerRequest
- CreateClusterRequest, UpdateClusterRequest
- AddNodeRequest, UpdateNodeRequest

### Resources (5) — `app/Http/Resources/Infrastructure/`
- ProviderResource (NEVER expose credentials)
- ServerResource (NEVER expose agent_token)
- ClusterResource
- ClusterNodeResource
- AgentCommandResource

### API Controllers (4) — `app/Http/Controllers/Api/`
- Infrastructure/ProviderController
- Infrastructure/ServerController
- Infrastructure/ClusterController
- Agent/AgentController

### Web Controllers (3)
- Web/ServerWebController
- Web/ClusterWebController
- Settings/ProviderSettingsController

---

## Routes

- routes/api.php: providers, servers, clusters (CRUD + actions)
- routes/agent.php: heartbeat, commands/pending, commands/{id}/result
- routes/web.php: servers, clusters (Inertia pages)
- routes/settings.php: provider settings

---

## Frontend

### TypeScript types: Provider, Server, Cluster, ClusterNode in models.ts
### Pages (8): servers/{index,create,show}, clusters/{index,create,show}, settings/providers
### Layout: Add Providers to settings nav

---

## Decisions

1. Agent token lookup via `agent_token_hash` column (SHA-256, indexed)
2. Provider events added for audit log consistency
3. SSH via phpseclib/phpseclib v3
4. IPs as string(45) for SQLite compatibility
5. Bootstrap action designed for mocking (agent binary doesn't exist yet)
