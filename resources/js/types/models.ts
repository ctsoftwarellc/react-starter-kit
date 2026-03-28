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
