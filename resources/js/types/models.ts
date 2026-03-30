export type PersonalAccessToken = {
    id: string;
    name: string;
    abilities: string[];
    last_used_at: string | null;
    expires_at: string | null;
    created_at: string;
};

export type SshKey = {
    id: string;
    name: string;
    public_key: string;
    fingerprint: string;
    created_at: string;
};

export type Project = {
    id: string;
    name: string;
    slug: string;
    description: string | null;
    created_at: string;
    updated_at: string;
};

export type GitConnection = {
    id: string;
    provider: 'github' | 'gitlab' | 'bitbucket';
    account_name: string;
    token_expires_at: string | null;
    created_at: string;
    updated_at: string;
};

export type Application = {
    id: string;
    project_id: string;
    name: string;
    slug: string;
    runtime: 'php' | 'node' | 'python' | 'go';
    repository_url: string | null;
    repository_branch: string;
    git_connection_id: string | null;
    settings: Record<string, unknown>;
    project?: Project;
    git_connection?: GitConnection | null;
    environments?: Environment[];
    pipelines?: Pipeline[];
    environments_count?: number;
    webhooks?: Webhook[];
    created_at: string;
    updated_at: string;
};

export type PipelineDefinitionJob = {
    name: string;
    commands: string[];
    environment: Record<string, unknown>;
    allow_failure: boolean;
    timeout: number | null;
};

export type PipelineDefinitionStage = {
    name: string;
    jobs: PipelineDefinitionJob[];
};

export type PipelineDefinition = {
    artifact: boolean;
    stages: PipelineDefinitionStage[];
};

export type Pipeline = {
    id: string;
    application_id: string;
    name: string;
    definition: PipelineDefinition;
    is_active: boolean;
    trigger_branches: string[];
    trigger_events: string[];
    application?: Application;
    runs?: PipelineRun[];
    created_at: string;
    updated_at: string;
};

export type PipelineRun = {
    id: string;
    pipeline_id: string;
    environment_id: string | null;
    status: string;
    trigger_type: string;
    trigger_ref: string | null;
    trigger_sha: string | null;
    trigger_actor: string | null;
    definition_snapshot: PipelineDefinition;
    duration_seconds?: number | null;
    pipeline?: Pipeline;
    environment?: Environment | null;
    jobs?: PipelineJob[];
    artifact?: Artifact | null;
    started_at: string | null;
    finished_at: string | null;
    created_at: string;
    updated_at: string;
};

export type PipelineJob = {
    id: string;
    pipeline_run_id: string;
    stage: string;
    name: string;
    status: string;
    runner_id: string | null;
    commands: string[];
    environment: Record<string, unknown>;
    log_path: string | null;
    exit_code: number | null;
    pipeline_run?: PipelineRun;
    runner?: Runner | null;
    started_at: string | null;
    finished_at: string | null;
    created_at: string;
    updated_at: string;
};

export type Artifact = {
    id: string;
    pipeline_run_id: string;
    application_id: string;
    status: string;
    storage_path: string | null;
    content_hash: string | null;
    size_bytes: number | null;
    metadata: Record<string, unknown>;
    pipeline_run?: PipelineRun;
    application?: Application;
    created_at: string;
    updated_at: string;
};

export type RunnerCurrentJob = {
    id: string;
    pipeline_run_id: string;
    stage: string;
    name: string;
    status: string;
    pipeline?: {
        id: string | null;
        name: string | null;
    } | null;
};

export type Runner = {
    id: string;
    name: string;
    status: string;
    platform: string | null;
    metadata: Record<string, unknown>;
    last_heartbeat_at: string | null;
    current_job?: RunnerCurrentJob | null;
    created_at: string;
    updated_at: string;
};

export type EnvironmentVariable = {
    id: string;
    environment_id: string;
    key: string;
    value: string;
    is_build_arg: boolean;
    created_at: string;
    updated_at: string;
};

export type Secret = {
    id: string;
    environment_id: string;
    key: string;
    version: number;
    created_at: string;
    updated_at: string;
};

export type ProcessDefinition = {
    id: string;
    environment_id: string;
    type: 'web' | 'worker' | 'scheduler' | 'custom';
    command: string;
    instances: number;
    created_at: string;
    updated_at: string;
};

export type Webhook = {
    id: string;
    application_id: string;
    provider: 'github' | 'gitlab' | 'bitbucket';
    is_active: boolean;
    created_at: string;
    updated_at: string;
};

export type AuditLog = {
    id: string;
    user_id: string;
    action: string;
    auditable_type: string;
    auditable_id: string;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
};

export type Provider = {
    id: string;
    name: string;
    type: 'digitalocean' | 'hetzner' | 'vultr' | 'aws' | 'manual';
    is_active: boolean;
    created_at: string;
    updated_at: string;
};

export type Server = {
    id: string;
    provider_id: string | null;
    name: string;
    hostname: string;
    public_ip: string;
    private_ip: string | null;
    ssh_port: number;
    ssh_user: string;
    os: string | null;
    cpu_cores: number | null;
    memory_mb: number | null;
    disk_gb: number | null;
    region: string | null;
    status: string;
    last_heartbeat_at: string | null;
    metadata: Record<string, unknown>;
    provider?: Provider;
    clusters?: Cluster[];
    created_at: string;
    updated_at: string;
};

export type Cluster = {
    id: string;
    name: string;
    slug: string;
    status: string;
    settings: Record<string, unknown>;
    servers?: Server[];
    node_counts?: Record<string, number>;
    created_at: string;
    updated_at: string;
};

export type Environment = {
    id: string;
    application_id: string;
    cluster_id: string;
    name: string;
    type: 'production' | 'staging' | 'preview';
    is_auto_deploy: boolean;
    branch: string | null;
    application?: Application;
    cluster?: Cluster;
    variables?: EnvironmentVariable[];
    secrets?: Secret[];
    process_definitions?: ProcessDefinition[];
    service_bindings?: ServiceBinding[];
    created_at: string;
    updated_at: string;
};

export type DatabaseInstance = {
    id: string;
    cluster_id: string;
    name: string;
    engine: 'postgres' | 'mysql';
    version: string | null;
    host: string;
    port: number;
    database_name: string;
    username: string;
    cluster?: Cluster;
    created_at: string;
    updated_at: string;
};

export type CacheInstance = {
    id: string;
    cluster_id: string;
    name: string;
    engine: 'redis' | 'valkey';
    version: string | null;
    host: string;
    port: number;
    cluster?: Cluster;
    created_at: string;
    updated_at: string;
};

export type StorageBucket = {
    id: string;
    name: string;
    provider: string;
    region: string;
    bucket_name: string;
    created_at: string;
    updated_at: string;
};

export type ServiceBinding = {
    id: string;
    environment_id: string;
    service_type: 'database' | 'cache' | 'storage';
    binding_name: string;
    config: Record<string, unknown>;
    database_instance?: DatabaseInstance | null;
    cache_instance?: CacheInstance | null;
    storage_bucket?: StorageBucket | null;
    created_at: string;
    updated_at: string;
};

export type ClusterNode = {
    id: string;
    server_id: string;
    cluster_id: string;
    role: 'web' | 'worker' | 'db' | 'cache' | 'queue' | 'bastion';
    is_active: boolean;
    sort_order: number;
    server?: Server;
};

export type PaginatedData<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
};
