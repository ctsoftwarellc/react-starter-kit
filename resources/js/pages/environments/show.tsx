import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArchiveRestore,
    Database,
    Eye,
    FolderArchive,
    Pencil,
    RefreshCw,
    Rocket,
    RotateCcw,
    ShieldCheck,
    TerminalSquare,
    Trash2,
    Unplug,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { DomainList } from '@/components/networking/domain-list';
import type {
    Application,
    Artifact,
    Backup,
    CacheInstance,
    Cluster,
    Domain,
    DatabaseInstance,
    Deployment,
    Environment,
    EnvironmentVariable,
    HealthCheck,
    ProcessDefinition,
    RemoteCommand,
    Release,
    RuntimeProfile,
    Secret,
    Server,
    ServerRoleProfile,
    ServiceBinding,
    StorageBucket,
} from '@/types';

type Props = {
    environment: Environment & {
        variables?: EnvironmentVariable[];
        secrets?: Secret[];
        process_definitions?: ProcessDefinition[];
        cluster?: Cluster;
    };
    application: Application;
    domains: Domain[];
    backupServers: Array<Server & { backups?: Backup[] }>;
    clusters: Cluster[];
    databaseInstances: DatabaseInstance[];
    cacheInstances: CacheInstance[];
    storageBuckets: StorageBucket[];
    serviceBindings: ServiceBinding[];
    activeRelease: Release | null;
    releases: Release[];
    deployments: Deployment[];
    healthCheck: HealthCheck | null;
    deployableArtifacts: Artifact[];
    runtimeProfiles: RuntimeProfile[];
    serverRoleProfiles: ServerRoleProfile[];
    remoteCommands: RemoteCommand[];
    environmentServers: Server[];
};

function deploymentStatusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'active':
        case 'succeeded':
            return 'default';
        case 'pending':
        case 'preparing':
        case 'deploying':
        case 'verifying':
            return 'secondary';
        case 'failed':
        case 'cancelled':
        case 'rolled_back':
            return 'destructive';
        default:
            return 'outline';
    }
}

async function revealSecret(
    environmentId: string,
    secretId: string,
): Promise<string> {
    const response = await fetch(
        `/environments/${environmentId}/secrets/${secretId}/reveal`,
        {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        },
    );

    if (!response.ok) {
        throw new Error('Unable to reveal secret');
    }

    const payload = (await response.json()) as { value: string };

    return payload.value;
}

function bindingServiceName(binding: ServiceBinding): string {
    if (binding.service_type === 'database') {
        return binding.database_instance?.name ?? 'Unknown database';
    }

    if (binding.service_type === 'cache') {
        return binding.cache_instance?.name ?? 'Unknown cache';
    }

    return binding.storage_bucket?.name ?? 'Unknown bucket';
}

export default function EnvironmentShow({
    environment,
    application,
    domains,
    backupServers,
    clusters,
    databaseInstances,
    cacheInstances,
    storageBuckets,
    serviceBindings,
    activeRelease,
    releases,
    deployments,
    healthCheck,
    deployableArtifacts,
    runtimeProfiles,
    serverRoleProfiles,
    remoteCommands,
    environmentServers,
}: Props) {
    const [editingVariableId, setEditingVariableId] = useState<string | null>(
        null,
    );
    const [editingSecretId, setEditingSecretId] = useState<string | null>(null);
    const [editingProcessId, setEditingProcessId] = useState<string | null>(
        null,
    );
    const [revealedValues, setRevealedValues] = useState<
        Record<string, string>
    >({});
    const [revealingId, setRevealingId] = useState<string | null>(null);

    const settingsForm = useForm({
        cluster_id: environment.cluster_id,
        name: environment.name,
        type: environment.type,
        branch: environment.branch ?? '',
        is_auto_deploy: environment.is_auto_deploy,
    });

    const variableForm = useForm({
        key: '',
        value: '',
        is_build_arg: false,
    });

    const secretForm = useForm({
        key: '',
        value: '',
    });

    const processForm = useForm({
        type: 'web',
        command: '',
        instances: '1',
    });

    const deployForm = useForm({
        artifact_id: '',
        strategy: 'rolling',
    });

    const rollbackForm = useForm({
        release_id: '',
    });

    const healthCheckForm = useForm({
        id: healthCheck?.id ?? '',
        type: healthCheck?.type ?? 'http',
        target: healthCheck?.target ?? '',
        interval_seconds: healthCheck?.interval_seconds ?? 30,
        timeout_seconds: healthCheck?.timeout_seconds ?? 5,
        healthy_threshold: healthCheck?.healthy_threshold ?? 3,
        unhealthy_threshold: healthCheck?.unhealthy_threshold ?? 2,
        is_active: healthCheck?.is_active ?? true,
    });

    const runtimeProfileForm = useForm({
        name: '',
        stack: 'php-fpm',
        config: '{"web_server":"caddy","php_version":"8.3"}',
    });

    const applyRuntimeProfileForm = useForm({
        runtime_profile_id: environment.runtime_profile_id ?? '',
    });

    const serverRoleProfileForm = useForm({
        role: 'web',
        name: 'Web Nodes',
        config: '{"packages":["caddy","php8.3-fpm"],"services":["caddy","php8.3-fpm"]}',
    });

    const remoteCommandForm = useForm({
        server_id: '',
        type: 'run_migrations',
        command: '',
        script: 'app()->environment();',
        allow_arbitrary: false,
    });

    const databaseForm = useForm({
        cluster_id: environment.cluster_id,
        name: '',
        engine: 'postgres',
        version: '',
    });

    const cacheForm = useForm({
        cluster_id: environment.cluster_id,
        name: '',
        engine: 'redis',
        version: '',
    });

    const storageForm = useForm({
        name: '',
        provider: 's3',
        region: 'us-east-1',
        bucket_name: '',
    });

    const bindDatabaseForm = useForm({
        service_type: 'database',
        binding_name: 'DB',
        database_instance_id: '',
    });

    const bindCacheForm = useForm({
        service_type: 'cache',
        binding_name: 'CACHE',
        cache_instance_id: '',
    });

    const bindStorageForm = useForm({
        service_type: 'storage',
        binding_name: 'STORAGE',
        storage_bucket_id: '',
    });

    const backupForm = useForm({
        server_id: backupServers[0]?.id ?? '',
        type: 'database',
        retention_days: 30,
    });

    const variables = useMemo(
        () => environment.variables ?? [],
        [environment.variables],
    );
    const secrets = useMemo(
        () => environment.secrets ?? [],
        [environment.secrets],
    );
    const processes = useMemo(
        () => environment.process_definitions ?? [],
        [environment.process_definitions],
    );
    const deploymentHistory = useMemo(() => deployments ?? [], [deployments]);
    const releaseHistory = useMemo(() => releases ?? [], [releases]);
    const runtimeProfileHistory = useMemo(
        () => runtimeProfiles ?? [],
        [runtimeProfiles],
    );
    const roleProfileHistory = useMemo(
        () => serverRoleProfiles ?? [],
        [serverRoleProfiles],
    );
    const commandHistory = useMemo(
        () => remoteCommands ?? [],
        [remoteCommands],
    );
    const environmentDomains = useMemo(() => domains ?? [], [domains]);

    function saveSettings(event: React.FormEvent) {
        event.preventDefault();
        settingsForm.put(`/environments/${environment.id}`);
    }

    function submitVariable(event: React.FormEvent) {
        event.preventDefault();

        const url = editingVariableId
            ? `/environments/${environment.id}/variables/${editingVariableId}`
            : `/environments/${environment.id}/variables`;

        const options = {
            onSuccess: () => {
                setEditingVariableId(null);
                variableForm.reset();
            },
        };

        if (editingVariableId) {
            variableForm.put(url, options);
        } else {
            variableForm.post(url, options);
        }
    }

    function startEditVariable(variable: EnvironmentVariable) {
        setEditingVariableId(variable.id);
        variableForm.setData({
            key: variable.key,
            value: variable.value,
            is_build_arg: variable.is_build_arg,
        });
    }

    function deleteVariable(variable: EnvironmentVariable) {
        router.delete(
            `/environments/${environment.id}/variables/${variable.id}`,
        );
    }

    function submitSecret(event: React.FormEvent) {
        event.preventDefault();

        const url = editingSecretId
            ? `/environments/${environment.id}/secrets/${editingSecretId}`
            : `/environments/${environment.id}/secrets`;

        const options = {
            onSuccess: () => {
                setEditingSecretId(null);
                secretForm.reset();
            },
        };

        if (editingSecretId) {
            secretForm.put(url, options);
        } else {
            secretForm.post(url, options);
        }
    }

    function startEditSecret(secret: Secret) {
        setEditingSecretId(secret.id);
        secretForm.setData({ key: secret.key, value: '' });
    }

    function deleteSecret(secret: Secret) {
        router.delete(`/environments/${environment.id}/secrets/${secret.id}`);
    }

    async function handleRevealSecret(secret: Secret) {
        setRevealingId(secret.id);

        try {
            const value = await revealSecret(environment.id, secret.id);
            setRevealedValues((current) => ({
                ...current,
                [secret.id]: value,
            }));
        } finally {
            setRevealingId(null);
        }
    }

    function submitProcess(event: React.FormEvent) {
        event.preventDefault();

        const url = editingProcessId
            ? `/environments/${environment.id}/processes/${editingProcessId}`
            : `/environments/${environment.id}/processes`;

        const options = {
            onSuccess: () => {
                setEditingProcessId(null);
                processForm.reset();
                processForm.setData('type', 'web');
                processForm.setData('instances', '1');
            },
        };

        if (editingProcessId) {
            processForm.put(url, options);
        } else {
            processForm.post(url, options);
        }
    }

    function startEditProcess(processDefinition: ProcessDefinition) {
        setEditingProcessId(processDefinition.id);
        processForm.setData({
            type: processDefinition.type,
            command: processDefinition.command,
            instances: String(processDefinition.instances),
        });
    }

    function deleteProcess(processDefinition: ProcessDefinition) {
        router.delete(
            `/environments/${environment.id}/processes/${processDefinition.id}`,
        );
    }

    function deleteEnvironment() {
        router.delete(`/environments/${environment.id}`);
    }

    function submitBackup(event: React.FormEvent) {
        event.preventDefault();

        backupForm.post(`/servers/${backupForm.data.server_id}/backups`, {
            preserveScroll: true,
            onSuccess: () => backupForm.reset('type', 'retention_days'),
        });
    }

    function restoreBackup(backup: Backup) {
        router.post(
            `/backups/${backup.id}/restore`,
            { server_id: backup.server_id },
            { preserveScroll: true },
        );
    }

    function submitDeploy(event: React.FormEvent) {
        event.preventDefault();
        deployForm.post(`/environments/${environment.id}/deploy`);
    }

    function submitRollback(event: React.FormEvent) {
        event.preventDefault();
        rollbackForm.post(`/environments/${environment.id}/rollback`);
    }

    function saveHealthCheck(event: React.FormEvent) {
        event.preventDefault();
        healthCheckForm.put(`/environments/${environment.id}/health-check`);
    }

    function submitRuntimeProfile(event: React.FormEvent) {
        event.preventDefault();
        runtimeProfileForm.post(
            `/environments/${environment.id}/runtime-profiles`,
            {
                onSuccess: () => runtimeProfileForm.reset('name'),
            },
        );
    }

    function applyRuntimeProfile(event: React.FormEvent) {
        event.preventDefault();
        applyRuntimeProfileForm.post(
            `/environments/${environment.id}/runtime-profile/apply`,
        );
    }

    function submitServerRoleProfile(event: React.FormEvent) {
        event.preventDefault();
        serverRoleProfileForm.post(
            `/environments/${environment.id}/server-role-profiles`,
        );
    }

    function submitRemoteCommand(event: React.FormEvent) {
        event.preventDefault();
        remoteCommandForm.post(
            `/environments/${environment.id}/remote-commands`,
        );
    }

    function submitDatabase(event: React.FormEvent) {
        event.preventDefault();
        databaseForm.post('/service-management/databases', {
            onSuccess: () => databaseForm.reset('name', 'version'),
        });
    }

    function submitCache(event: React.FormEvent) {
        event.preventDefault();
        cacheForm.post('/service-management/caches', {
            onSuccess: () => cacheForm.reset('name', 'version'),
        });
    }

    function submitStorage(event: React.FormEvent) {
        event.preventDefault();
        storageForm.post('/service-management/storage-buckets', {
            onSuccess: () => storageForm.reset('name', 'bucket_name'),
        });
    }

    function bindDatabase(event: React.FormEvent) {
        event.preventDefault();
        bindDatabaseForm.post(
            `/environments/${environment.id}/service-bindings`,
            {
                onSuccess: () => bindDatabaseForm.reset('database_instance_id'),
            },
        );
    }

    function bindCache(event: React.FormEvent) {
        event.preventDefault();
        bindCacheForm.post(`/environments/${environment.id}/service-bindings`, {
            onSuccess: () => bindCacheForm.reset('cache_instance_id'),
        });
    }

    function bindStorage(event: React.FormEvent) {
        event.preventDefault();
        bindStorageForm.post(
            `/environments/${environment.id}/service-bindings`,
            {
                onSuccess: () => bindStorageForm.reset('storage_bucket_id'),
            },
        );
    }

    function unbindService(binding: ServiceBinding) {
        router.delete(
            `/environments/${environment.id}/service-bindings/${binding.id}`,
        );
    }

    function rotateDatabase(instance: DatabaseInstance) {
        router.post(
            `/service-management/databases/${instance.id}/rotate-credentials`,
        );
    }

    function rotateCache(instance: CacheInstance) {
        router.post(
            `/service-management/caches/${instance.id}/rotate-credentials`,
        );
    }

    function rotateStorage(bucket: StorageBucket) {
        router.post(
            `/service-management/storage-buckets/${bucket.id}/rotate-credentials`,
        );
    }

    function deleteDatabase(instance: DatabaseInstance) {
        router.delete(`/service-management/databases/${instance.id}`);
    }

    function deleteCache(instance: CacheInstance) {
        router.delete(`/service-management/caches/${instance.id}`);
    }

    function deleteStorage(bucket: StorageBucket) {
        router.delete(`/service-management/storage-buckets/${bucket.id}`);
    }

    return (
        <>
            <Head title={`${application.name} - ${environment.name}`} />

            <div className="space-y-6 px-4 py-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div className="mb-3 flex items-center gap-3">
                            <Heading
                                title={environment.name}
                                description={`${application.name} environment`}
                            />
                            <Badge variant="outline">{environment.type}</Badge>
                        </div>
                        <div className="flex flex-wrap gap-2 text-sm text-muted-foreground">
                            <span>
                                Cluster:{' '}
                                {environment.cluster?.name ?? 'Unassigned'}
                            </span>
                            <span>-</span>
                            <span>Branch: {environment.branch ?? '-'}</span>
                            <span>-</span>
                            <span>
                                Auto Deploy:{' '}
                                {environment.is_auto_deploy
                                    ? 'Enabled'
                                    : 'Disabled'}
                            </span>
                        </div>
                    </div>

                    <Button variant="destructive" onClick={deleteEnvironment}>
                        <Trash2 className="mr-2 h-4 w-4" />
                        Delete Environment
                    </Button>
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.15fr,0.85fr]">
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Active Release</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4 text-sm">
                                {activeRelease ? (
                                    <>
                                        <div className="flex items-center justify-between gap-4">
                                            <div>
                                                <p className="font-medium">
                                                    Release v
                                                    {activeRelease.version}
                                                </p>
                                                <p className="text-muted-foreground">
                                                    {activeRelease.artifact
                                                        ?.pipeline_run?.pipeline
                                                        ?.name ??
                                                        'Deployment artifact'}
                                                </p>
                                            </div>
                                            <Badge
                                                variant={deploymentStatusVariant(
                                                    activeRelease.status,
                                                )}
                                            >
                                                {activeRelease.status}
                                            </Badge>
                                        </div>
                                        <div className="grid gap-3 md:grid-cols-2">
                                            <div className="rounded-lg border p-3">
                                                <p className="text-muted-foreground">
                                                    Artifact
                                                </p>
                                                <p className="mt-1 font-mono text-xs">
                                                    {activeRelease.artifact_id}
                                                </p>
                                            </div>
                                            <div className="rounded-lg border p-3">
                                                <p className="text-muted-foreground">
                                                    Activated
                                                </p>
                                                <p className="mt-1 font-medium">
                                                    {new Date(
                                                        activeRelease.created_at,
                                                    ).toLocaleString()}
                                                </p>
                                            </div>
                                        </div>
                                    </>
                                ) : (
                                    <p className="text-muted-foreground">
                                        No release is active yet. Deploy a ready
                                        artifact to start serving traffic.
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        <DomainList
                            domains={environmentDomains}
                            environmentId={environment.id}
                        />

                        <Card>
                            <CardHeader>
                                <CardTitle>Recent Deployments</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="rounded-lg border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Deployment
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Status
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Progress
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Started
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {deploymentHistory.length > 0 ? (
                                                deploymentHistory.map(
                                                    (deployment) => (
                                                        <tr
                                                            key={deployment.id}
                                                            className="border-b last:border-0"
                                                        >
                                                            <td className="px-4 py-2">
                                                                <Link
                                                                    href={`/deployments/${deployment.id}`}
                                                                    className="font-medium text-primary hover:underline"
                                                                >
                                                                    {deployment.release
                                                                        ? `Release v${deployment.release.version}`
                                                                        : deployment.id}
                                                                </Link>
                                                            </td>
                                                            <td className="px-4 py-2">
                                                                <Badge
                                                                    variant={deploymentStatusVariant(
                                                                        deployment.status,
                                                                    )}
                                                                >
                                                                    {
                                                                        deployment.status
                                                                    }
                                                                </Badge>
                                                            </td>
                                                            <td className="px-4 py-2 text-muted-foreground">
                                                                {
                                                                    deployment.completed_nodes
                                                                }
                                                                /
                                                                {
                                                                    deployment.total_nodes
                                                                }{' '}
                                                                complete
                                                            </td>
                                                            <td className="px-4 py-2 text-muted-foreground">
                                                                {deployment.started_at
                                                                    ? new Date(
                                                                          deployment.started_at,
                                                                      ).toLocaleString()
                                                                    : '-'}
                                                            </td>
                                                        </tr>
                                                    ),
                                                )
                                            ) : (
                                                <tr>
                                                    <td
                                                        className="px-4 py-6 text-muted-foreground"
                                                        colSpan={4}
                                                    >
                                                        No deployments have been
                                                        started yet.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Recent Releases</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="rounded-lg border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Version
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Status
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Artifact
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {releaseHistory.length > 0 ? (
                                                releaseHistory.map(
                                                    (release) => (
                                                        <tr
                                                            key={release.id}
                                                            className="border-b last:border-0"
                                                        >
                                                            <td className="px-4 py-2 font-medium">
                                                                v
                                                                {
                                                                    release.version
                                                                }
                                                            </td>
                                                            <td className="px-4 py-2">
                                                                <Badge
                                                                    variant={deploymentStatusVariant(
                                                                        release.status,
                                                                    )}
                                                                >
                                                                    {
                                                                        release.status
                                                                    }
                                                                </Badge>
                                                            </td>
                                                            <td className="px-4 py-2 font-mono text-xs text-muted-foreground">
                                                                {
                                                                    release.artifact_id
                                                                }
                                                            </td>
                                                        </tr>
                                                    ),
                                                )
                                            ) : (
                                                <tr>
                                                    <td
                                                        className="px-4 py-6 text-muted-foreground"
                                                        colSpan={3}
                                                    >
                                                        No releases created yet.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Deploy</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form
                                    onSubmit={submitDeploy}
                                    className="space-y-4"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="deploy-artifact">
                                            Ready Artifact
                                        </Label>
                                        <Select
                                            value={deployForm.data.artifact_id}
                                            onValueChange={(value) =>
                                                deployForm.setData(
                                                    'artifact_id',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select artifact" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {deployableArtifacts.map(
                                                    (artifact) => (
                                                        <SelectItem
                                                            key={artifact.id}
                                                            value={artifact.id}
                                                        >
                                                            {artifact
                                                                .pipeline_run
                                                                ?.trigger_ref ??
                                                                'artifact'}{' '}
                                                            -{' '}
                                                            {artifact.id.slice(
                                                                0,
                                                                8,
                                                            )}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={
                                                deployForm.errors.artifact_id
                                            }
                                        />
                                    </div>

                                    <div className="rounded-lg border border-dashed p-3 text-sm text-muted-foreground">
                                        Rolling strategy is enabled for MVP and
                                        will deploy nodes sequentially.
                                    </div>

                                    <Button disabled={deployForm.processing}>
                                        <Rocket className="mr-2 h-4 w-4" />
                                        Start Deployment
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Rollback</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form
                                    onSubmit={submitRollback}
                                    className="space-y-4"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="rollback-release">
                                            Target Release
                                        </Label>
                                        <Select
                                            value={rollbackForm.data.release_id}
                                            onValueChange={(value) =>
                                                rollbackForm.setData(
                                                    'release_id',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select release" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {releaseHistory
                                                    .filter(
                                                        (release) =>
                                                            release.id !==
                                                            activeRelease?.id,
                                                    )
                                                    .map((release) => (
                                                        <SelectItem
                                                            key={release.id}
                                                            value={release.id}
                                                        >
                                                            v{release.version} -{' '}
                                                            {release.status}
                                                        </SelectItem>
                                                    ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={
                                                rollbackForm.errors.release_id
                                            }
                                        />
                                    </div>

                                    <Button
                                        variant="outline"
                                        disabled={rollbackForm.processing}
                                    >
                                        <RotateCcw className="mr-2 h-4 w-4" />
                                        Start Rollback
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Backups</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {backupServers.length > 0 ? (
                                    <form
                                        onSubmit={submitBackup}
                                        className="grid gap-4 rounded-lg border p-4 md:grid-cols-4"
                                    >
                                        <div className="grid gap-2 md:col-span-2">
                                            <Label htmlFor="backup-server">
                                                Target Server
                                            </Label>
                                            <Select
                                                value={
                                                    backupForm.data.server_id
                                                }
                                                onValueChange={(value) =>
                                                    backupForm.setData(
                                                        'server_id',
                                                        value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Select server" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {backupServers.map(
                                                        (server) => (
                                                            <SelectItem
                                                                key={server.id}
                                                                value={
                                                                    server.id
                                                                }
                                                            >
                                                                {server.name} (
                                                                {
                                                                    server.public_ip
                                                                }
                                                                )
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    backupForm.errors.server_id
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="backup-type">
                                                Type
                                            </Label>
                                            <Select
                                                value={backupForm.data.type}
                                                onValueChange={(value) =>
                                                    backupForm.setData(
                                                        'type',
                                                        value as Backup['type'],
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Select type" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="database">
                                                        Database
                                                    </SelectItem>
                                                    <SelectItem value="files">
                                                        Files
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="backup-retention">
                                                Retention Days
                                            </Label>
                                            <Input
                                                id="backup-retention"
                                                type="number"
                                                min={1}
                                                max={365}
                                                value={
                                                    backupForm.data
                                                        .retention_days
                                                }
                                                onChange={(event) =>
                                                    backupForm.setData(
                                                        'retention_days',
                                                        Number(
                                                            event.target.value,
                                                        ),
                                                    )
                                                }
                                            />
                                        </div>
                                        <div className="md:col-span-4">
                                            <Button
                                                disabled={
                                                    backupForm.processing ||
                                                    !backupForm.data.server_id
                                                }
                                            >
                                                <FolderArchive className="mr-2 h-4 w-4" />
                                                Trigger Backup
                                            </Button>
                                        </div>
                                    </form>
                                ) : (
                                    <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                                        Backups become available once this
                                        environment has active cluster nodes.
                                    </div>
                                )}

                                <div className="space-y-3">
                                    {backupServers.map((server) => (
                                        <div
                                            key={server.id}
                                            className="rounded-lg border p-4"
                                        >
                                            <div className="mb-3 flex items-center justify-between gap-3">
                                                <div>
                                                    <p className="font-medium">
                                                        {server.name}
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {server.public_ip}
                                                    </p>
                                                </div>
                                                <Badge variant="outline">
                                                    {server.backups?.length ??
                                                        0}{' '}
                                                    backups
                                                </Badge>
                                            </div>
                                            {server.backups &&
                                            server.backups.length > 0 ? (
                                                <div className="space-y-2">
                                                    {server.backups.map(
                                                        (backup) => (
                                                            <div
                                                                key={backup.id}
                                                                className="flex flex-col gap-3 rounded-lg border px-3 py-3 text-sm md:flex-row md:items-center md:justify-between"
                                                            >
                                                                <div className="space-y-1">
                                                                    <div className="flex flex-wrap items-center gap-2">
                                                                        <span className="font-medium capitalize">
                                                                            {backup.type ===
                                                                            'database' ? (
                                                                                <Database className="mr-1 inline h-4 w-4" />
                                                                            ) : (
                                                                                <FolderArchive className="mr-1 inline h-4 w-4" />
                                                                            )}
                                                                            {
                                                                                backup.type
                                                                            }
                                                                        </span>
                                                                        <Badge
                                                                            variant={deploymentStatusVariant(
                                                                                backup.status,
                                                                            )}
                                                                        >
                                                                            {
                                                                                backup.status
                                                                            }
                                                                        </Badge>
                                                                    </div>
                                                                    <p className="text-xs text-muted-foreground">
                                                                        Started{' '}
                                                                        {backup.started_at
                                                                            ? new Date(
                                                                                  backup.started_at,
                                                                              ).toLocaleString()
                                                                            : 'pending'}
                                                                        {' · '}
                                                                        Retention{' '}
                                                                        {
                                                                            backup.retention_days
                                                                        }{' '}
                                                                        days
                                                                    </p>
                                                                </div>
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        restoreBackup(
                                                                            backup,
                                                                        )
                                                                    }
                                                                    disabled={
                                                                        backup.status !==
                                                                        'completed'
                                                                    }
                                                                >
                                                                    <ArchiveRestore className="mr-2 h-4 w-4" />
                                                                    Restore
                                                                </Button>
                                                            </div>
                                                        ),
                                                    )}
                                                </div>
                                            ) : (
                                                <p className="text-sm text-muted-foreground">
                                                    No backups recorded for this
                                                    server yet.
                                                </p>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Health Check</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form
                                    onSubmit={saveHealthCheck}
                                    className="space-y-4"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="health-check-type">
                                            Type
                                        </Label>
                                        <Select
                                            value={healthCheckForm.data.type}
                                            onValueChange={(value) =>
                                                healthCheckForm.setData(
                                                    'type',
                                                    value as 'http',
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="http">
                                                    HTTP
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="health-check-target">
                                            Target
                                        </Label>
                                        <Input
                                            id="health-check-target"
                                            value={healthCheckForm.data.target}
                                            onChange={(event) =>
                                                healthCheckForm.setData(
                                                    'target',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="https://app.example.com/health"
                                        />
                                        <InputError
                                            message={
                                                healthCheckForm.errors.target
                                            }
                                        />
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="health-check-interval">
                                                Interval Seconds
                                            </Label>
                                            <Input
                                                id="health-check-interval"
                                                type="number"
                                                min={5}
                                                value={
                                                    healthCheckForm.data
                                                        .interval_seconds
                                                }
                                                onChange={(event) =>
                                                    healthCheckForm.setData(
                                                        'interval_seconds',
                                                        Number(
                                                            event.target.value,
                                                        ),
                                                    )
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="health-check-timeout">
                                                Timeout Seconds
                                            </Label>
                                            <Input
                                                id="health-check-timeout"
                                                type="number"
                                                min={1}
                                                value={
                                                    healthCheckForm.data
                                                        .timeout_seconds
                                                }
                                                onChange={(event) =>
                                                    healthCheckForm.setData(
                                                        'timeout_seconds',
                                                        Number(
                                                            event.target.value,
                                                        ),
                                                    )
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="health-check-healthy-threshold">
                                                Healthy Threshold
                                            </Label>
                                            <Input
                                                id="health-check-healthy-threshold"
                                                type="number"
                                                min={1}
                                                value={
                                                    healthCheckForm.data
                                                        .healthy_threshold
                                                }
                                                onChange={(event) =>
                                                    healthCheckForm.setData(
                                                        'healthy_threshold',
                                                        Number(
                                                            event.target.value,
                                                        ),
                                                    )
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="health-check-unhealthy-threshold">
                                                Unhealthy Threshold
                                            </Label>
                                            <Input
                                                id="health-check-unhealthy-threshold"
                                                type="number"
                                                min={1}
                                                value={
                                                    healthCheckForm.data
                                                        .unhealthy_threshold
                                                }
                                                onChange={(event) =>
                                                    healthCheckForm.setData(
                                                        'unhealthy_threshold',
                                                        Number(
                                                            event.target.value,
                                                        ),
                                                    )
                                                }
                                            />
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-3 rounded-lg border p-3">
                                        <Checkbox
                                            id="health-check-active"
                                            checked={
                                                healthCheckForm.data.is_active
                                            }
                                            onCheckedChange={(value) =>
                                                healthCheckForm.setData(
                                                    'is_active',
                                                    value === true,
                                                )
                                            }
                                        />
                                        <Label htmlFor="health-check-active">
                                            Enable post-deploy verification
                                        </Label>
                                    </div>

                                    <Button
                                        disabled={healthCheckForm.processing}
                                    >
                                        <ShieldCheck className="mr-2 h-4 w-4" />
                                        Save Health Check
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Runtime Profiles</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <form
                                    onSubmit={applyRuntimeProfile}
                                    className="space-y-4 rounded-lg border p-4"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="runtime-profile-apply">
                                            Assigned Profile
                                        </Label>
                                        <Select
                                            value={
                                                applyRuntimeProfileForm.data
                                                    .runtime_profile_id
                                            }
                                            onValueChange={(value) =>
                                                applyRuntimeProfileForm.setData(
                                                    'runtime_profile_id',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select runtime profile" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {runtimeProfileHistory.map(
                                                    (profile) => (
                                                        <SelectItem
                                                            key={profile.id}
                                                            value={profile.id}
                                                        >
                                                            {profile.name} -{' '}
                                                            {profile.stack}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={
                                                applyRuntimeProfileForm.errors
                                                    .runtime_profile_id
                                            }
                                        />
                                    </div>
                                    <Button
                                        disabled={
                                            applyRuntimeProfileForm.processing
                                        }
                                    >
                                        Apply Runtime Profile
                                    </Button>
                                </form>

                                <form
                                    onSubmit={submitRuntimeProfile}
                                    className="grid gap-4 rounded-lg border p-4"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="runtime-profile-name">
                                            New Profile Name
                                        </Label>
                                        <Input
                                            id="runtime-profile-name"
                                            value={runtimeProfileForm.data.name}
                                            onChange={(event) =>
                                                runtimeProfileForm.setData(
                                                    'name',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="runtime-profile-stack">
                                            Stack
                                        </Label>
                                        <Select
                                            value={
                                                runtimeProfileForm.data.stack
                                            }
                                            onValueChange={(value) =>
                                                runtimeProfileForm.setData(
                                                    'stack',
                                                    value as RuntimeProfile['stack'],
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select stack" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="php-fpm">
                                                    PHP-FPM
                                                </SelectItem>
                                                <SelectItem value="nginx">
                                                    Nginx
                                                </SelectItem>
                                                <SelectItem value="caddy">
                                                    Caddy
                                                </SelectItem>
                                                <SelectItem value="node">
                                                    Node
                                                </SelectItem>
                                                <SelectItem value="supervisor">
                                                    Supervisor
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="runtime-profile-config">
                                            Config JSON
                                        </Label>
                                        <textarea
                                            id="runtime-profile-config"
                                            className="min-h-28 rounded-md border bg-background px-3 py-2 text-sm"
                                            value={
                                                runtimeProfileForm.data.config
                                            }
                                            onChange={(event) =>
                                                runtimeProfileForm.setData(
                                                    'config',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <Button
                                        disabled={runtimeProfileForm.processing}
                                    >
                                        Create Runtime Profile
                                    </Button>
                                </form>

                                <div className="space-y-2">
                                    {runtimeProfileHistory.map((profile) => (
                                        <div
                                            key={profile.id}
                                            className="flex items-center justify-between rounded-lg border px-3 py-2 text-sm"
                                        >
                                            <div>
                                                <p className="font-medium">
                                                    {profile.name}
                                                </p>
                                                <p className="text-muted-foreground">
                                                    {profile.stack}
                                                </p>
                                            </div>
                                            <Badge
                                                variant={
                                                    environment.runtime_profile_id ===
                                                    profile.id
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                            >
                                                {environment.runtime_profile_id ===
                                                profile.id
                                                    ? 'Assigned'
                                                    : 'Available'}
                                            </Badge>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Server Role Profiles</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <form
                                    onSubmit={submitServerRoleProfile}
                                    className="grid gap-4 rounded-lg border p-4"
                                >
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="role-profile-role">
                                                Role
                                            </Label>
                                            <Select
                                                value={
                                                    serverRoleProfileForm.data
                                                        .role
                                                }
                                                onValueChange={(value) =>
                                                    serverRoleProfileForm.setData(
                                                        'role',
                                                        value as ServerRoleProfile['role'],
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Select role" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="web">
                                                        Web
                                                    </SelectItem>
                                                    <SelectItem value="worker">
                                                        Worker
                                                    </SelectItem>
                                                    <SelectItem value="db">
                                                        DB
                                                    </SelectItem>
                                                    <SelectItem value="cache">
                                                        Cache
                                                    </SelectItem>
                                                    <SelectItem value="queue">
                                                        Queue
                                                    </SelectItem>
                                                    <SelectItem value="bastion">
                                                        Bastion
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="role-profile-name">
                                                Name
                                            </Label>
                                            <Input
                                                id="role-profile-name"
                                                value={
                                                    serverRoleProfileForm.data
                                                        .name
                                                }
                                                onChange={(event) =>
                                                    serverRoleProfileForm.setData(
                                                        'name',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </div>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="role-profile-config">
                                            Config JSON
                                        </Label>
                                        <textarea
                                            id="role-profile-config"
                                            className="min-h-28 rounded-md border bg-background px-3 py-2 text-sm"
                                            value={
                                                serverRoleProfileForm.data
                                                    .config
                                            }
                                            onChange={(event) =>
                                                serverRoleProfileForm.setData(
                                                    'config',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <Button
                                        disabled={
                                            serverRoleProfileForm.processing
                                        }
                                    >
                                        Save Role Profile
                                    </Button>
                                </form>

                                <div className="space-y-2">
                                    {roleProfileHistory.map((profile) => (
                                        <div
                                            key={profile.id}
                                            className="flex items-center justify-between rounded-lg border px-3 py-2 text-sm"
                                        >
                                            <div>
                                                <p className="font-medium">
                                                    {profile.name}
                                                </p>
                                                <p className="text-muted-foreground">
                                                    {profile.role}
                                                </p>
                                            </div>
                                            <Badge variant="outline">
                                                Queued to apply
                                            </Badge>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Commands</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <form
                                    onSubmit={submitRemoteCommand}
                                    className="grid gap-4 rounded-lg border p-4"
                                >
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="remote-command-type">
                                                Template
                                            </Label>
                                            <Select
                                                value={
                                                    remoteCommandForm.data.type
                                                }
                                                onValueChange={(value) =>
                                                    remoteCommandForm.setData(
                                                        'type',
                                                        value as RemoteCommand['type'],
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Select command" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="run_migrations">
                                                        Run migrations
                                                    </SelectItem>
                                                    <SelectItem value="clear_cache">
                                                        Clear cache
                                                    </SelectItem>
                                                    <SelectItem value="restart_workers">
                                                        Restart workers
                                                    </SelectItem>
                                                    <SelectItem value="artisan_tinker">
                                                        Artisan tinker snippet
                                                    </SelectItem>
                                                    <SelectItem value="custom">
                                                        Custom shell command
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="remote-command-server">
                                                Target Server
                                            </Label>
                                            <Select
                                                value={
                                                    remoteCommandForm.data
                                                        .server_id
                                                }
                                                onValueChange={(value) =>
                                                    remoteCommandForm.setData(
                                                        'server_id',
                                                        value === 'auto'
                                                            ? ''
                                                            : value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Auto-select app server" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="auto">
                                                        Auto-select app server
                                                    </SelectItem>
                                                    {environmentServers.map(
                                                        (server) => (
                                                            <SelectItem
                                                                key={server.id}
                                                                value={
                                                                    server.id
                                                                }
                                                            >
                                                                {server.name}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>

                                    {remoteCommandForm.data.type ===
                                    'artisan_tinker' ? (
                                        <div className="grid gap-2">
                                            <Label htmlFor="remote-command-script">
                                                Tinker Snippet
                                            </Label>
                                            <Input
                                                id="remote-command-script"
                                                value={
                                                    remoteCommandForm.data
                                                        .script
                                                }
                                                onChange={(event) =>
                                                    remoteCommandForm.setData(
                                                        'script',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </div>
                                    ) : null}

                                    {remoteCommandForm.data.type ===
                                    'custom' ? (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="remote-command-custom">
                                                    Custom Command
                                                </Label>
                                                <Input
                                                    id="remote-command-custom"
                                                    value={
                                                        remoteCommandForm.data
                                                            .command
                                                    }
                                                    onChange={(event) =>
                                                        remoteCommandForm.setData(
                                                            'command',
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                            </div>
                                            <div className="flex items-center gap-3 rounded-lg border p-3 text-sm">
                                                <Checkbox
                                                    id="remote-command-allow-arbitrary"
                                                    checked={
                                                        remoteCommandForm.data
                                                            .allow_arbitrary
                                                    }
                                                    onCheckedChange={(value) =>
                                                        remoteCommandForm.setData(
                                                            'allow_arbitrary',
                                                            value === true,
                                                        )
                                                    }
                                                />
                                                <Label htmlFor="remote-command-allow-arbitrary">
                                                    I understand this bypasses
                                                    the safe templates.
                                                </Label>
                                            </div>
                                        </>
                                    ) : null}

                                    <InputError
                                        message={
                                            remoteCommandForm.errors.command ??
                                            remoteCommandForm.errors.script
                                        }
                                    />

                                    <Button
                                        disabled={remoteCommandForm.processing}
                                    >
                                        <TerminalSquare className="mr-2 h-4 w-4" />
                                        Run Command
                                    </Button>
                                </form>

                                <div className="space-y-2">
                                    {commandHistory.length > 0 ? (
                                        commandHistory.map((command) => (
                                            <div
                                                key={command.id}
                                                className="rounded-lg border p-3 text-sm"
                                            >
                                                <div className="flex items-center justify-between gap-3">
                                                    <div>
                                                        <p className="font-medium">
                                                            {command.type.replaceAll(
                                                                '_',
                                                                ' ',
                                                            )}
                                                        </p>
                                                        <p className="font-mono text-xs text-muted-foreground">
                                                            {command.command}
                                                        </p>
                                                    </div>
                                                    <Badge
                                                        variant={deploymentStatusVariant(
                                                            command.status,
                                                        )}
                                                    >
                                                        {command.status}
                                                    </Badge>
                                                </div>
                                                <p className="mt-2 text-xs text-muted-foreground">
                                                    {command.server?.name ??
                                                        'Auto-selected server'}
                                                </p>
                                                {command.output ? (
                                                    <div className="mt-3 rounded-md bg-muted p-2 font-mono text-xs">
                                                        {command.output}
                                                    </div>
                                                ) : null}
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-sm text-muted-foreground">
                                            No remote commands yet. Start with a
                                            safe template to keep changes
                                            auditable.
                                        </p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <div className="grid gap-6 xl:grid-cols-[0.95fr,1.05fr]">
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Environment Settings</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form
                                    onSubmit={saveSettings}
                                    className="space-y-4"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="environment-settings-name">
                                            Name
                                        </Label>
                                        <Input
                                            id="environment-settings-name"
                                            value={settingsForm.data.name}
                                            onChange={(event) =>
                                                settingsForm.setData(
                                                    'name',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={settingsForm.errors.name}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="environment-settings-cluster">
                                            Cluster
                                        </Label>
                                        <Select
                                            value={settingsForm.data.cluster_id}
                                            onValueChange={(value) =>
                                                settingsForm.setData(
                                                    'cluster_id',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select cluster" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {clusters.map((cluster) => (
                                                    <SelectItem
                                                        key={cluster.id}
                                                        value={cluster.id}
                                                    >
                                                        {cluster.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={
                                                settingsForm.errors.cluster_id
                                            }
                                        />
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="environment-settings-type">
                                                Type
                                            </Label>
                                            <Select
                                                value={settingsForm.data.type}
                                                onValueChange={(value) =>
                                                    settingsForm.setData(
                                                        'type',
                                                        value as
                                                            | 'production'
                                                            | 'staging'
                                                            | 'preview',
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Select type" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="production">
                                                        Production
                                                    </SelectItem>
                                                    <SelectItem value="staging">
                                                        Staging
                                                    </SelectItem>
                                                    <SelectItem value="preview">
                                                        Preview
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    settingsForm.errors.type
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="environment-settings-branch">
                                                Branch
                                            </Label>
                                            <Input
                                                id="environment-settings-branch"
                                                value={settingsForm.data.branch}
                                                onChange={(event) =>
                                                    settingsForm.setData(
                                                        'branch',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    settingsForm.errors.branch
                                                }
                                            />
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-3">
                                        <Button
                                            disabled={settingsForm.processing}
                                        >
                                            Save Settings
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Bound Services</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="rounded-lg border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Binding
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Type
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Service
                                                </th>
                                                <th className="px-4 py-2 text-right font-medium">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {serviceBindings.length > 0 ? (
                                                serviceBindings.map(
                                                    (binding) => (
                                                        <tr
                                                            key={binding.id}
                                                            className="border-b last:border-0"
                                                        >
                                                            <td className="px-4 py-2 font-mono text-xs">
                                                                {
                                                                    binding.binding_name
                                                                }
                                                            </td>
                                                            <td className="px-4 py-2">
                                                                <Badge variant="outline">
                                                                    {
                                                                        binding.service_type
                                                                    }
                                                                </Badge>
                                                            </td>
                                                            <td className="px-4 py-2">
                                                                {bindingServiceName(
                                                                    binding,
                                                                )}
                                                            </td>
                                                            <td className="px-4 py-2 text-right">
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        unbindService(
                                                                            binding,
                                                                        )
                                                                    }
                                                                >
                                                                    <Unplug className="h-4 w-4" />
                                                                </Button>
                                                            </td>
                                                        </tr>
                                                    ),
                                                )
                                            ) : (
                                                <tr>
                                                    <td
                                                        className="px-4 py-6 text-muted-foreground"
                                                        colSpan={4}
                                                    >
                                                        No services bound yet.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Environment Variables</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <form
                                    onSubmit={submitVariable}
                                    className="grid gap-4 md:grid-cols-[1fr,1fr,auto]"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="variable-key">
                                            Key
                                        </Label>
                                        <Input
                                            id="variable-key"
                                            value={variableForm.data.key}
                                            onChange={(event) =>
                                                variableForm.setData(
                                                    'key',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={variableForm.errors.key}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="variable-value">
                                            Value
                                        </Label>
                                        <Input
                                            id="variable-value"
                                            value={variableForm.data.value}
                                            onChange={(event) =>
                                                variableForm.setData(
                                                    'value',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={variableForm.errors.value}
                                        />
                                    </div>
                                    <div className="flex items-end gap-2">
                                        <Button
                                            disabled={variableForm.processing}
                                        >
                                            {editingVariableId
                                                ? 'Update'
                                                : 'Add'}
                                        </Button>
                                        {editingVariableId && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() => {
                                                    setEditingVariableId(null);
                                                    variableForm.reset();
                                                }}
                                            >
                                                Cancel
                                            </Button>
                                        )}
                                    </div>
                                </form>

                                <div className="rounded-lg border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Key
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Value
                                                </th>
                                                <th className="px-4 py-2 text-right font-medium">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {variables.length > 0 ? (
                                                variables.map((variable) => (
                                                    <tr
                                                        key={variable.id}
                                                        className="border-b last:border-0"
                                                    >
                                                        <td className="px-4 py-2 font-mono text-xs">
                                                            {variable.key}
                                                        </td>
                                                        <td className="px-4 py-2">
                                                            {variable.value}
                                                        </td>
                                                        <td className="px-4 py-2 text-right">
                                                            <div className="flex justify-end gap-1">
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        startEditVariable(
                                                                            variable,
                                                                        )
                                                                    }
                                                                >
                                                                    <Pencil className="h-4 w-4" />
                                                                </Button>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        deleteVariable(
                                                                            variable,
                                                                        )
                                                                    }
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))
                                            ) : (
                                                <tr>
                                                    <td
                                                        className="px-4 py-6 text-muted-foreground"
                                                        colSpan={3}
                                                    >
                                                        No environment variables
                                                        yet.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Secrets</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <form
                                    onSubmit={submitSecret}
                                    className="grid gap-4 md:grid-cols-[1fr,1fr,auto]"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="secret-key">Key</Label>
                                        <Input
                                            id="secret-key"
                                            value={secretForm.data.key}
                                            onChange={(event) =>
                                                secretForm.setData(
                                                    'key',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={secretForm.errors.key}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="secret-value">
                                            Value
                                        </Label>
                                        <Input
                                            id="secret-value"
                                            value={secretForm.data.value}
                                            onChange={(event) =>
                                                secretForm.setData(
                                                    'value',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder={
                                                editingSecretId
                                                    ? 'Enter a new secret value'
                                                    : ''
                                            }
                                        />
                                        <InputError
                                            message={secretForm.errors.value}
                                        />
                                    </div>
                                    <div className="flex items-end gap-2">
                                        <Button
                                            disabled={secretForm.processing}
                                        >
                                            {editingSecretId ? 'Update' : 'Add'}
                                        </Button>
                                        {editingSecretId && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() => {
                                                    setEditingSecretId(null);
                                                    secretForm.reset();
                                                }}
                                            >
                                                Cancel
                                            </Button>
                                        )}
                                    </div>
                                </form>

                                <div className="rounded-lg border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Key
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Version
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Reveal
                                                </th>
                                                <th className="px-4 py-2 text-right font-medium">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {secrets.length > 0 ? (
                                                secrets.map((secret) => (
                                                    <tr
                                                        key={secret.id}
                                                        className="border-b last:border-0"
                                                    >
                                                        <td className="px-4 py-2 font-mono text-xs">
                                                            {secret.key}
                                                        </td>
                                                        <td className="px-4 py-2">
                                                            v{secret.version}
                                                        </td>
                                                        <td className="px-4 py-2 font-mono text-xs text-muted-foreground">
                                                            {revealedValues[
                                                                secret.id
                                                            ] ?? '-'}
                                                        </td>
                                                        <td className="px-4 py-2 text-right">
                                                            <div className="flex justify-end gap-1">
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    disabled={
                                                                        revealingId ===
                                                                        secret.id
                                                                    }
                                                                    onClick={() =>
                                                                        void handleRevealSecret(
                                                                            secret,
                                                                        )
                                                                    }
                                                                >
                                                                    <Eye className="h-4 w-4" />
                                                                </Button>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        startEditSecret(
                                                                            secret,
                                                                        )
                                                                    }
                                                                >
                                                                    <Pencil className="h-4 w-4" />
                                                                </Button>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        deleteSecret(
                                                                            secret,
                                                                        )
                                                                    }
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))
                                            ) : (
                                                <tr>
                                                    <td
                                                        className="px-4 py-6 text-muted-foreground"
                                                        colSpan={4}
                                                    >
                                                        No secrets yet.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Process Definitions</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <form
                                    onSubmit={submitProcess}
                                    className="grid gap-4 md:grid-cols-[180px,1fr,120px,auto]"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="process-type">
                                            Type
                                        </Label>
                                        <Select
                                            value={processForm.data.type}
                                            onValueChange={(value) =>
                                                processForm.setData(
                                                    'type',
                                                    value as
                                                        | 'web'
                                                        | 'worker'
                                                        | 'scheduler'
                                                        | 'custom',
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="web">
                                                    Web
                                                </SelectItem>
                                                <SelectItem value="worker">
                                                    Worker
                                                </SelectItem>
                                                <SelectItem value="scheduler">
                                                    Scheduler
                                                </SelectItem>
                                                <SelectItem value="custom">
                                                    Custom
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={processForm.errors.type}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="process-command">
                                            Command
                                        </Label>
                                        <Input
                                            id="process-command"
                                            value={processForm.data.command}
                                            onChange={(event) =>
                                                processForm.setData(
                                                    'command',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={processForm.errors.command}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="process-instances">
                                            Instances
                                        </Label>
                                        <Input
                                            id="process-instances"
                                            type="number"
                                            min={1}
                                            value={processForm.data.instances}
                                            onChange={(event) =>
                                                processForm.setData(
                                                    'instances',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                processForm.errors.instances
                                            }
                                        />
                                    </div>
                                    <div className="flex items-end gap-2">
                                        <Button
                                            disabled={processForm.processing}
                                        >
                                            {editingProcessId
                                                ? 'Update'
                                                : 'Add'}
                                        </Button>
                                        {editingProcessId && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() => {
                                                    setEditingProcessId(null);
                                                    processForm.reset();
                                                    processForm.setData(
                                                        'type',
                                                        'web',
                                                    );
                                                    processForm.setData(
                                                        'instances',
                                                        '1',
                                                    );
                                                }}
                                            >
                                                Cancel
                                            </Button>
                                        )}
                                    </div>
                                </form>

                                <div className="rounded-lg border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Type
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Command
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Instances
                                                </th>
                                                <th className="px-4 py-2 text-right font-medium">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {processes.length > 0 ? (
                                                processes.map(
                                                    (processDefinition) => (
                                                        <tr
                                                            key={
                                                                processDefinition.id
                                                            }
                                                            className="border-b last:border-0"
                                                        >
                                                            <td className="px-4 py-2">
                                                                {
                                                                    processDefinition.type
                                                                }
                                                            </td>
                                                            <td className="px-4 py-2 font-mono text-xs">
                                                                {
                                                                    processDefinition.command
                                                                }
                                                            </td>
                                                            <td className="px-4 py-2">
                                                                {
                                                                    processDefinition.instances
                                                                }
                                                            </td>
                                                            <td className="px-4 py-2 text-right">
                                                                <div className="flex justify-end gap-1">
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() =>
                                                                            startEditProcess(
                                                                                processDefinition,
                                                                            )
                                                                        }
                                                                    >
                                                                        <Pencil className="h-4 w-4" />
                                                                    </Button>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() =>
                                                                            deleteProcess(
                                                                                processDefinition,
                                                                            )
                                                                        }
                                                                    >
                                                                        <Trash2 className="h-4 w-4" />
                                                                    </Button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    ),
                                                )
                                            ) : (
                                                <tr>
                                                    <td
                                                        className="px-4 py-6 text-muted-foreground"
                                                        colSpan={4}
                                                    >
                                                        No process definitions
                                                        yet.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Managed Databases</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <form
                                    onSubmit={submitDatabase}
                                    className="grid gap-4 md:grid-cols-2"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="database-name">
                                            Name
                                        </Label>
                                        <Input
                                            id="database-name"
                                            value={databaseForm.data.name}
                                            onChange={(event) =>
                                                databaseForm.setData(
                                                    'name',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={databaseForm.errors.name}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="database-cluster">
                                            Cluster
                                        </Label>
                                        <Select
                                            value={databaseForm.data.cluster_id}
                                            onValueChange={(value) =>
                                                databaseForm.setData(
                                                    'cluster_id',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select cluster" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {clusters.map((cluster) => (
                                                    <SelectItem
                                                        key={cluster.id}
                                                        value={cluster.id}
                                                    >
                                                        {cluster.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={
                                                databaseForm.errors.cluster_id
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="database-engine">
                                            Engine
                                        </Label>
                                        <Select
                                            value={databaseForm.data.engine}
                                            onValueChange={(value) =>
                                                databaseForm.setData(
                                                    'engine',
                                                    value as
                                                        | 'postgres'
                                                        | 'mysql',
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Engine" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="postgres">
                                                    Postgres
                                                </SelectItem>
                                                <SelectItem value="mysql">
                                                    MySQL
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="database-version">
                                            Version
                                        </Label>
                                        <Input
                                            id="database-version"
                                            value={databaseForm.data.version}
                                            onChange={(event) =>
                                                databaseForm.setData(
                                                    'version',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="16"
                                        />
                                    </div>
                                    <div className="flex items-center gap-3 md:col-span-2">
                                        <Button
                                            disabled={databaseForm.processing}
                                        >
                                            Provision Database
                                        </Button>
                                    </div>
                                </form>

                                <form
                                    onSubmit={bindDatabase}
                                    className="grid gap-4 rounded-lg border p-4"
                                >
                                    <p className="font-medium">
                                        Bind Existing Database
                                    </p>
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="bind-database">
                                                Database
                                            </Label>
                                            <Select
                                                value={
                                                    bindDatabaseForm.data
                                                        .database_instance_id
                                                }
                                                onValueChange={(value) =>
                                                    bindDatabaseForm.setData(
                                                        'database_instance_id',
                                                        value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Select database" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {databaseInstances.map(
                                                        (instance) => (
                                                            <SelectItem
                                                                key={
                                                                    instance.id
                                                                }
                                                                value={
                                                                    instance.id
                                                                }
                                                            >
                                                                {instance.name}{' '}
                                                                (
                                                                {
                                                                    instance.engine
                                                                }
                                                                )
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    bindDatabaseForm.errors
                                                        .database_instance_id
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="database-binding-name">
                                                Binding Name
                                            </Label>
                                            <Input
                                                id="database-binding-name"
                                                value={
                                                    bindDatabaseForm.data
                                                        .binding_name
                                                }
                                                onChange={(event) =>
                                                    bindDatabaseForm.setData(
                                                        'binding_name',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    bindDatabaseForm.errors
                                                        .binding_name
                                                }
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <Button
                                            disabled={
                                                bindDatabaseForm.processing
                                            }
                                        >
                                            Bind Database
                                        </Button>
                                    </div>
                                </form>

                                <div className="rounded-lg border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Name
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Engine
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Cluster
                                                </th>
                                                <th className="px-4 py-2 text-right font-medium">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {databaseInstances.length > 0 ? (
                                                databaseInstances.map(
                                                    (instance) => (
                                                        <tr
                                                            key={instance.id}
                                                            className="border-b last:border-0"
                                                        >
                                                            <td className="px-4 py-2">
                                                                {instance.name}
                                                            </td>
                                                            <td className="px-4 py-2">
                                                                {
                                                                    instance.engine
                                                                }
                                                            </td>
                                                            <td className="px-4 py-2">
                                                                {instance
                                                                    .cluster
                                                                    ?.name ??
                                                                    '-'}
                                                            </td>
                                                            <td className="px-4 py-2 text-right">
                                                                <div className="flex justify-end gap-1">
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() =>
                                                                            rotateDatabase(
                                                                                instance,
                                                                            )
                                                                        }
                                                                    >
                                                                        <RefreshCw className="h-4 w-4" />
                                                                    </Button>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() =>
                                                                            deleteDatabase(
                                                                                instance,
                                                                            )
                                                                        }
                                                                    >
                                                                        <Trash2 className="h-4 w-4" />
                                                                    </Button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    ),
                                                )
                                            ) : (
                                                <tr>
                                                    <td
                                                        className="px-4 py-6 text-muted-foreground"
                                                        colSpan={4}
                                                    >
                                                        No databases
                                                        provisioned.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Managed Caches</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <form
                                    onSubmit={submitCache}
                                    className="grid gap-4 md:grid-cols-2"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="cache-name">Name</Label>
                                        <Input
                                            id="cache-name"
                                            value={cacheForm.data.name}
                                            onChange={(event) =>
                                                cacheForm.setData(
                                                    'name',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={cacheForm.errors.name}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="cache-cluster">
                                            Cluster
                                        </Label>
                                        <Select
                                            value={cacheForm.data.cluster_id}
                                            onValueChange={(value) =>
                                                cacheForm.setData(
                                                    'cluster_id',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select cluster" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {clusters.map((cluster) => (
                                                    <SelectItem
                                                        key={cluster.id}
                                                        value={cluster.id}
                                                    >
                                                        {cluster.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={
                                                cacheForm.errors.cluster_id
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="cache-engine">
                                            Engine
                                        </Label>
                                        <Select
                                            value={cacheForm.data.engine}
                                            onValueChange={(value) =>
                                                cacheForm.setData(
                                                    'engine',
                                                    value as 'redis' | 'valkey',
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Engine" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="redis">
                                                    Redis
                                                </SelectItem>
                                                <SelectItem value="valkey">
                                                    Valkey
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="cache-version">
                                            Version
                                        </Label>
                                        <Input
                                            id="cache-version"
                                            value={cacheForm.data.version}
                                            onChange={(event) =>
                                                cacheForm.setData(
                                                    'version',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="7"
                                        />
                                    </div>
                                    <div className="flex items-center gap-3 md:col-span-2">
                                        <Button disabled={cacheForm.processing}>
                                            Provision Cache
                                        </Button>
                                    </div>
                                </form>

                                <form
                                    onSubmit={bindCache}
                                    className="grid gap-4 rounded-lg border p-4"
                                >
                                    <p className="font-medium">
                                        Bind Existing Cache
                                    </p>
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="bind-cache">
                                                Cache
                                            </Label>
                                            <Select
                                                value={
                                                    bindCacheForm.data
                                                        .cache_instance_id
                                                }
                                                onValueChange={(value) =>
                                                    bindCacheForm.setData(
                                                        'cache_instance_id',
                                                        value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Select cache" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {cacheInstances.map(
                                                        (instance) => (
                                                            <SelectItem
                                                                key={
                                                                    instance.id
                                                                }
                                                                value={
                                                                    instance.id
                                                                }
                                                            >
                                                                {instance.name}{' '}
                                                                (
                                                                {
                                                                    instance.engine
                                                                }
                                                                )
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    bindCacheForm.errors
                                                        .cache_instance_id
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="cache-binding-name">
                                                Binding Name
                                            </Label>
                                            <Input
                                                id="cache-binding-name"
                                                value={
                                                    bindCacheForm.data
                                                        .binding_name
                                                }
                                                onChange={(event) =>
                                                    bindCacheForm.setData(
                                                        'binding_name',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    bindCacheForm.errors
                                                        .binding_name
                                                }
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <Button
                                            disabled={bindCacheForm.processing}
                                        >
                                            Bind Cache
                                        </Button>
                                    </div>
                                </form>

                                <div className="rounded-lg border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Name
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Engine
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Cluster
                                                </th>
                                                <th className="px-4 py-2 text-right font-medium">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {cacheInstances.length > 0 ? (
                                                cacheInstances.map(
                                                    (instance) => (
                                                        <tr
                                                            key={instance.id}
                                                            className="border-b last:border-0"
                                                        >
                                                            <td className="px-4 py-2">
                                                                {instance.name}
                                                            </td>
                                                            <td className="px-4 py-2">
                                                                {
                                                                    instance.engine
                                                                }
                                                            </td>
                                                            <td className="px-4 py-2">
                                                                {instance
                                                                    .cluster
                                                                    ?.name ??
                                                                    '-'}
                                                            </td>
                                                            <td className="px-4 py-2 text-right">
                                                                <div className="flex justify-end gap-1">
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() =>
                                                                            rotateCache(
                                                                                instance,
                                                                            )
                                                                        }
                                                                    >
                                                                        <RefreshCw className="h-4 w-4" />
                                                                    </Button>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() =>
                                                                            deleteCache(
                                                                                instance,
                                                                            )
                                                                        }
                                                                    >
                                                                        <Trash2 className="h-4 w-4" />
                                                                    </Button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    ),
                                                )
                                            ) : (
                                                <tr>
                                                    <td
                                                        className="px-4 py-6 text-muted-foreground"
                                                        colSpan={4}
                                                    >
                                                        No caches provisioned.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Storage Buckets</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <form
                                    onSubmit={submitStorage}
                                    className="grid gap-4 md:grid-cols-2"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="bucket-name">
                                            Name
                                        </Label>
                                        <Input
                                            id="bucket-name"
                                            value={storageForm.data.name}
                                            onChange={(event) =>
                                                storageForm.setData(
                                                    'name',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={storageForm.errors.name}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="bucket-provider">
                                            Provider
                                        </Label>
                                        <Input
                                            id="bucket-provider"
                                            value={storageForm.data.provider}
                                            onChange={(event) =>
                                                storageForm.setData(
                                                    'provider',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                storageForm.errors.provider
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="bucket-region">
                                            Region
                                        </Label>
                                        <Input
                                            id="bucket-region"
                                            value={storageForm.data.region}
                                            onChange={(event) =>
                                                storageForm.setData(
                                                    'region',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={storageForm.errors.region}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="bucket-bucket-name">
                                            Bucket Name
                                        </Label>
                                        <Input
                                            id="bucket-bucket-name"
                                            value={storageForm.data.bucket_name}
                                            onChange={(event) =>
                                                storageForm.setData(
                                                    'bucket_name',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                storageForm.errors.bucket_name
                                            }
                                        />
                                    </div>
                                    <div className="flex items-center gap-3 md:col-span-2">
                                        <Button
                                            disabled={storageForm.processing}
                                        >
                                            Provision Bucket
                                        </Button>
                                    </div>
                                </form>

                                <form
                                    onSubmit={bindStorage}
                                    className="grid gap-4 rounded-lg border p-4"
                                >
                                    <p className="font-medium">
                                        Bind Existing Bucket
                                    </p>
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="bind-storage">
                                                Bucket
                                            </Label>
                                            <Select
                                                value={
                                                    bindStorageForm.data
                                                        .storage_bucket_id
                                                }
                                                onValueChange={(value) =>
                                                    bindStorageForm.setData(
                                                        'storage_bucket_id',
                                                        value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Select bucket" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {storageBuckets.map(
                                                        (bucket) => (
                                                            <SelectItem
                                                                key={bucket.id}
                                                                value={
                                                                    bucket.id
                                                                }
                                                            >
                                                                {bucket.name} (
                                                                {
                                                                    bucket.provider
                                                                }
                                                                )
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    bindStorageForm.errors
                                                        .storage_bucket_id
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="storage-binding-name">
                                                Binding Name
                                            </Label>
                                            <Input
                                                id="storage-binding-name"
                                                value={
                                                    bindStorageForm.data
                                                        .binding_name
                                                }
                                                onChange={(event) =>
                                                    bindStorageForm.setData(
                                                        'binding_name',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    bindStorageForm.errors
                                                        .binding_name
                                                }
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <Button
                                            disabled={
                                                bindStorageForm.processing
                                            }
                                        >
                                            Bind Bucket
                                        </Button>
                                    </div>
                                </form>

                                <div className="rounded-lg border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Name
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Provider
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Region
                                                </th>
                                                <th className="px-4 py-2 text-right font-medium">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {storageBuckets.length > 0 ? (
                                                storageBuckets.map((bucket) => (
                                                    <tr
                                                        key={bucket.id}
                                                        className="border-b last:border-0"
                                                    >
                                                        <td className="px-4 py-2">
                                                            {bucket.name}
                                                        </td>
                                                        <td className="px-4 py-2">
                                                            {bucket.provider}
                                                        </td>
                                                        <td className="px-4 py-2">
                                                            {bucket.region}
                                                        </td>
                                                        <td className="px-4 py-2 text-right">
                                                            <div className="flex justify-end gap-1">
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        rotateStorage(
                                                                            bucket,
                                                                        )
                                                                    }
                                                                >
                                                                    <RefreshCw className="h-4 w-4" />
                                                                </Button>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        deleteStorage(
                                                                            bucket,
                                                                        )
                                                                    }
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))
                                            ) : (
                                                <tr>
                                                    <td
                                                        className="px-4 py-6 text-muted-foreground"
                                                        colSpan={4}
                                                    >
                                                        No buckets provisioned.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

EnvironmentShow.layout = {
    breadcrumbs: [
        {
            title: 'Projects',
            href: '/projects',
        },
        {
            title: 'Environment',
            href: '/projects',
        },
    ],
};
