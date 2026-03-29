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
    environments_count?: number;
    webhooks?: Webhook[];
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
