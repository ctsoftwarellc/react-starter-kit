import { Head, Link, router, useForm } from '@inertiajs/react';
import { Pencil, Play, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    Application,
    Cluster,
    Deployment,
    Environment,
    GitConnection,
    Pipeline,
    PipelineRun,
    Project,
} from '@/types';

type Props = {
    application: Application & {
        environments?: Array<Environment & { cluster?: Cluster }>;
        pipelines?: Array<Pipeline & { runs?: PipelineRun[] }>;
    };
    project: Project;
    gitConnections: GitConnection[];
    clusters: Cluster[];
    deployments: Deployment[];
};

type PipelineFormState = {
    name: string;
    definition: string;
    is_active: boolean;
    trigger_branches: string;
    trigger_events: string;
};

function defaultPipelineDefinition(branch: string): string {
    return JSON.stringify(
        {
            artifact: true,
            stages: [
                {
                    name: 'build',
                    jobs: [
                        {
                            name: 'bundle',
                            commands: [
                                'git fetch --all',
                                `git checkout ${branch}`,
                                'npm ci',
                                'npm run build',
                            ],
                            environment: {},
                            allow_failure: false,
                            timeout: 15,
                        },
                    ],
                },
            ],
        },
        null,
        2,
    );
}

function emptyPipelineForm(branch: string): PipelineFormState {
    return {
        name: '',
        definition: defaultPipelineDefinition(branch),
        is_active: true,
        trigger_branches: branch,
        trigger_events: 'push, manual',
    };
}

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'succeeded':
        case 'ready':
        case 'active':
            return 'default';
        case 'running':
        case 'queued':
        case 'assigned':
        case 'pending':
        case 'building':
        case 'preparing':
        case 'deploying':
        case 'verifying':
            return 'secondary';
        case 'failed':
        case 'cancelled':
        case 'timed_out':
        case 'rolled_back':
            return 'destructive';
        default:
            return 'outline';
    }
}

export default function ApplicationShow({
    application,
    project,
    gitConnections,
    clusters,
    deployments,
}: Props) {
    const [showDelete, setShowDelete] = useState(false);
    const [showPipelineDialog, setShowPipelineDialog] = useState(false);
    const [editingPipeline, setEditingPipeline] = useState<Pipeline | null>(
        null,
    );
    const [pipelineForm, setPipelineForm] = useState<PipelineFormState>(
        emptyPipelineForm(application.repository_branch),
    );
    const [pipelineErrors, setPipelineErrors] = useState<
        Record<string, string>
    >({});
    const [pipelineProcessing, setPipelineProcessing] = useState(false);
    const [triggerPipelineId, setTriggerPipelineId] = useState<string | null>(
        null,
    );
    const [triggerEnvironmentId, setTriggerEnvironmentId] = useState('');
    const [triggerProcessing, setTriggerProcessing] = useState(false);

    const settingsForm = useForm({
        name: application.name,
        runtime: application.runtime,
        repository_url: application.repository_url ?? '',
        repository_branch: application.repository_branch,
        git_connection_id: application.git_connection_id ?? '',
    });

    const environmentForm = useForm({
        name: '',
        type: 'production',
        cluster_id: '',
        branch: application.repository_branch,
        is_auto_deploy: false,
    });

    const recentRuns = useMemo(
        () =>
            (application.pipelines ?? [])
                .flatMap((pipeline) =>
                    (pipeline.runs ?? []).map((run) => ({
                        ...run,
                        pipeline,
                    })),
                )
                .sort(
                    (left, right) =>
                        new Date(right.created_at).getTime() -
                        new Date(left.created_at).getTime(),
                )
                .slice(0, 8),
        [application.pipelines],
    );

    function saveSettings(event: React.FormEvent) {
        event.preventDefault();
        settingsForm.put(`/applications/${application.id}`);
    }

    function createEnvironment(event: React.FormEvent) {
        event.preventDefault();
        environmentForm.post(`/applications/${application.id}/environments`, {
            onSuccess: () => environmentForm.reset('name', 'cluster_id'),
        });
    }

    function handleDelete() {
        router.delete(`/applications/${application.id}`, {
            onSuccess: () => setShowDelete(false),
        });
    }

    function openCreatePipeline() {
        setEditingPipeline(null);
        setPipelineErrors({});
        setPipelineForm(emptyPipelineForm(application.repository_branch));
        setShowPipelineDialog(true);
    }

    function openEditPipeline(pipeline: Pipeline) {
        setEditingPipeline(pipeline);
        setPipelineErrors({});
        setPipelineForm({
            name: pipeline.name,
            definition: JSON.stringify(pipeline.definition, null, 2),
            is_active: pipeline.is_active,
            trigger_branches: pipeline.trigger_branches.join(', '),
            trigger_events: pipeline.trigger_events.join(', '),
        });
        setShowPipelineDialog(true);
    }

    async function savePipeline(event: React.FormEvent) {
        event.preventDefault();
        setPipelineProcessing(true);
        setPipelineErrors({});

        const url = editingPipeline
            ? `/api/v1/pipelines/${editingPipeline.id}`
            : `/api/v1/applications/${application.id}/pipelines`;

        const response = await fetch(url, {
            method: editingPipeline ? 'PUT' : 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                name: pipelineForm.name,
                definition: pipelineForm.definition,
                is_active: pipelineForm.is_active,
                trigger_branches: pipelineForm.trigger_branches
                    .split(',')
                    .map((value) => value.trim())
                    .filter(Boolean),
                trigger_events: pipelineForm.trigger_events
                    .split(',')
                    .map((value) => value.trim())
                    .filter(Boolean),
            }),
        });

        const payload = response.status === 204 ? null : await response.json();

        if (!response.ok) {
            setPipelineErrors(
                Object.fromEntries(
                    Object.entries(
                        (payload as { errors?: Record<string, string[]> })
                            ?.errors ?? {},
                    ).map(([key, value]) => [
                        key,
                        value[0] ?? 'Invalid value.',
                    ]),
                ),
            );
            setPipelineProcessing(false);

            return;
        }

        setPipelineProcessing(false);
        setShowPipelineDialog(false);
        router.reload({ only: ['application'] });
    }

    async function deletePipeline(pipeline: Pipeline) {
        if (!window.confirm(`Delete pipeline ${pipeline.name}?`)) {
            return;
        }

        await fetch(`/api/v1/pipelines/${pipeline.id}`, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        router.reload({ only: ['application'] });
    }

    async function triggerPipeline() {
        if (!triggerPipelineId) {
            return;
        }

        setTriggerProcessing(true);

        const response = await fetch(
            `/api/v1/pipelines/${triggerPipelineId}/trigger`,
            {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    trigger_type: 'manual',
                    environment_id: triggerEnvironmentId || null,
                    trigger_ref: application.repository_branch,
                }),
            },
        );

        const payload = (await response.json()) as { data: PipelineRun };
        setTriggerProcessing(false);
        setTriggerPipelineId(null);
        setTriggerEnvironmentId('');

        if (response.ok) {
            router.visit(`/pipeline-runs/${payload.data.id}`);
        }
    }

    return (
        <>
            <Head title={application.name} />

            <div className="space-y-6 px-4 py-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-3">
                        <div className="flex items-center gap-3">
                            <Heading
                                title={application.name}
                                description={
                                    application.repository_url ??
                                    'No repository connected yet'
                                }
                            />
                            <Badge>{application.runtime}</Badge>
                        </div>
                        <div className="flex flex-wrap gap-2 text-sm text-muted-foreground">
                            <span>Project: {project.name}</span>
                            <span>-</span>
                            <span>Branch: {application.repository_branch}</span>
                            <span>-</span>
                            <span>
                                Git:{' '}
                                {application.git_connection?.account_name ??
                                    'Not connected'}
                            </span>
                        </div>
                    </div>

                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            onClick={() => setShowDelete(true)}
                        >
                            <Trash2 className="mr-2 h-4 w-4" />
                            Delete
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.3fr,0.9fr]">
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Overview</CardTitle>
                                <CardDescription>
                                    Repository, runtime, and webhook readiness
                                    for this application.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <div className="flex justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Repository
                                    </span>
                                    <span className="text-right font-medium">
                                        {application.repository_url ??
                                            'Not configured'}
                                    </span>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Runtime
                                    </span>
                                    <span className="font-medium">
                                        {application.runtime}
                                    </span>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Default Branch
                                    </span>
                                    <span className="font-medium">
                                        {application.repository_branch}
                                    </span>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Webhook
                                    </span>
                                    <span className="font-medium">
                                        {application.webhooks?.length
                                            ? 'Configured'
                                            : 'Pending'}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between gap-4">
                                <div>
                                    <CardTitle>Environments</CardTitle>
                                    <CardDescription>
                                        Deployment targets for this application.
                                    </CardDescription>
                                </div>
                                <Badge variant="secondary">
                                    {application.environments?.length ?? 0}
                                </Badge>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {application.environments?.length ? (
                                    <div className="grid gap-4 md:grid-cols-2">
                                        {application.environments.map(
                                            (environment) => (
                                                <Link
                                                    key={environment.id}
                                                    href={`/environments/${environment.id}`}
                                                    className="rounded-lg border p-4 transition-colors hover:bg-muted/40"
                                                >
                                                    <div className="mb-3 flex items-center justify-between gap-3">
                                                        <div>
                                                            <p className="font-medium">
                                                                {
                                                                    environment.name
                                                                }
                                                            </p>
                                                            <p className="text-sm text-muted-foreground">
                                                                {environment
                                                                    .cluster
                                                                    ?.name ??
                                                                    'Unassigned cluster'}
                                                            </p>
                                                        </div>
                                                        <Badge variant="outline">
                                                            {environment.type}
                                                        </Badge>
                                                    </div>
                                                    <div className="space-y-2 text-sm text-muted-foreground">
                                                        <div className="flex justify-between gap-4">
                                                            <span>Branch</span>
                                                            <span className="font-medium text-foreground">
                                                                {environment.branch ??
                                                                    '-'}
                                                            </span>
                                                        </div>
                                                        <div className="flex justify-between gap-4">
                                                            <span>
                                                                Auto Deploy
                                                            </span>
                                                            <span className="font-medium text-foreground">
                                                                {environment.is_auto_deploy
                                                                    ? 'Enabled'
                                                                    : 'Disabled'}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </Link>
                                            ),
                                        )}
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No environments yet. Create one below to
                                        start defining configuration.
                                    </p>
                                )}

                                <form
                                    onSubmit={createEnvironment}
                                    className="rounded-lg border p-4"
                                >
                                    <div className="mb-4 flex items-center gap-2">
                                        <Plus className="h-4 w-4 text-muted-foreground" />
                                        <p className="font-medium">
                                            Create Environment
                                        </p>
                                    </div>
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="environment-name">
                                                Name
                                            </Label>
                                            <Input
                                                id="environment-name"
                                                value={
                                                    environmentForm.data.name
                                                }
                                                onChange={(event) =>
                                                    environmentForm.setData(
                                                        'name',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="production"
                                            />
                                            <InputError
                                                message={
                                                    environmentForm.errors.name
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="environment-type">
                                                Type
                                            </Label>
                                            <Select
                                                value={
                                                    environmentForm.data.type
                                                }
                                                onValueChange={(value) =>
                                                    environmentForm.setData(
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
                                                    environmentForm.errors.type
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="environment-cluster">
                                                Cluster
                                            </Label>
                                            <Select
                                                value={
                                                    environmentForm.data
                                                        .cluster_id
                                                }
                                                onValueChange={(value) =>
                                                    environmentForm.setData(
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
                                                    environmentForm.errors
                                                        .cluster_id
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="environment-branch">
                                                Branch
                                            </Label>
                                            <Input
                                                id="environment-branch"
                                                value={
                                                    environmentForm.data.branch
                                                }
                                                onChange={(event) =>
                                                    environmentForm.setData(
                                                        'branch',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="main"
                                            />
                                            <InputError
                                                message={
                                                    environmentForm.errors
                                                        .branch
                                                }
                                            />
                                        </div>
                                    </div>

                                    <div className="mt-4 flex items-center gap-3">
                                        <Button
                                            disabled={
                                                environmentForm.processing
                                            }
                                        >
                                            Add Environment
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between gap-4">
                                <div>
                                    <CardTitle>Deployments</CardTitle>
                                    <CardDescription>
                                        Recent release activity across every
                                        environment.
                                    </CardDescription>
                                </div>
                                <Badge variant="secondary">
                                    {deployments.length}
                                </Badge>
                            </CardHeader>
                            <CardContent>
                                {deployments.length ? (
                                    <div className="rounded-md border">
                                        <table className="w-full text-sm">
                                            <thead>
                                                <tr className="border-b bg-muted/50">
                                                    <th className="px-4 py-2 text-left font-medium">
                                                        Environment
                                                    </th>
                                                    <th className="px-4 py-2 text-left font-medium">
                                                        Release
                                                    </th>
                                                    <th className="px-4 py-2 text-left font-medium">
                                                        Status
                                                    </th>
                                                    <th className="px-4 py-2 text-left font-medium">
                                                        Progress
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {deployments.map(
                                                    (deployment) => (
                                                        <tr
                                                            key={deployment.id}
                                                            className="border-b last:border-0"
                                                        >
                                                            <td className="px-4 py-2">
                                                                {deployment
                                                                    .environment
                                                                    ?.name ??
                                                                    '-'}
                                                            </td>
                                                            <td className="px-4 py-2">
                                                                <Link
                                                                    href={`/deployments/${deployment.id}`}
                                                                    className="font-medium text-primary hover:underline"
                                                                >
                                                                    {deployment.release
                                                                        ? `v${deployment.release.version}`
                                                                        : deployment.id}
                                                                </Link>
                                                            </td>
                                                            <td className="px-4 py-2">
                                                                <Badge
                                                                    variant={statusVariant(
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
                                                        </tr>
                                                    ),
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                ) : (
                                    <div className="rounded-lg border border-dashed p-6 text-sm text-muted-foreground">
                                        No deployments yet. Ship a ready
                                        artifact from one of this application's
                                        environments to start tracking release
                                        history here.
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between gap-4">
                                <div>
                                    <CardTitle>Pipelines</CardTitle>
                                    <CardDescription>
                                        Define build stages, trigger manual
                                        runs, and inspect recent activity from
                                        one place.
                                    </CardDescription>
                                </div>
                                <div className="flex gap-2">
                                    <Button variant="outline" asChild>
                                        <Link href="/runners">
                                            View Runners
                                        </Link>
                                    </Button>
                                    <Button onClick={openCreatePipeline}>
                                        <Plus className="mr-2 h-4 w-4" />
                                        New Pipeline
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {application.pipelines?.length ? (
                                    <div className="grid gap-4">
                                        {application.pipelines.map(
                                            (pipeline) => (
                                                <div
                                                    key={pipeline.id}
                                                    className="rounded-lg border p-4"
                                                >
                                                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                                        <div className="space-y-3">
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                <p className="font-medium">
                                                                    {
                                                                        pipeline.name
                                                                    }
                                                                </p>
                                                                <Badge
                                                                    variant={
                                                                        pipeline.is_active
                                                                            ? 'default'
                                                                            : 'outline'
                                                                    }
                                                                >
                                                                    {pipeline.is_active
                                                                        ? 'active'
                                                                        : 'inactive'}
                                                                </Badge>
                                                            </div>
                                                            <div className="flex flex-wrap gap-2 text-xs text-muted-foreground">
                                                                <span>
                                                                    Branches:{' '}
                                                                    {pipeline.trigger_branches.join(
                                                                        ', ',
                                                                    )}
                                                                </span>
                                                                <span>-</span>
                                                                <span>
                                                                    Events:{' '}
                                                                    {pipeline.trigger_events.join(
                                                                        ', ',
                                                                    )}
                                                                </span>
                                                            </div>
                                                            <div className="rounded-md bg-muted/40 p-3 text-xs text-muted-foreground">
                                                                {
                                                                    pipeline
                                                                        .definition
                                                                        .stages
                                                                        .length
                                                                }{' '}
                                                                stage
                                                                {pipeline
                                                                    .definition
                                                                    .stages
                                                                    .length ===
                                                                1
                                                                    ? ''
                                                                    : 's'}
                                                                , artifact{' '}
                                                                {pipeline
                                                                    .definition
                                                                    .artifact
                                                                    ? 'enabled'
                                                                    : 'disabled'}
                                                            </div>
                                                        </div>

                                                        <div className="flex flex-wrap gap-2">
                                                            <Button
                                                                variant="outline"
                                                                onClick={() =>
                                                                    openEditPipeline(
                                                                        pipeline,
                                                                    )
                                                                }
                                                            >
                                                                <Pencil className="mr-2 h-4 w-4" />
                                                                Edit
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                onClick={() =>
                                                                    setTriggerPipelineId(
                                                                        pipeline.id,
                                                                    )
                                                                }
                                                            >
                                                                <Play className="mr-2 h-4 w-4" />
                                                                Trigger
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                onClick={() =>
                                                                    deletePipeline(
                                                                        pipeline,
                                                                    )
                                                                }
                                                            >
                                                                <Trash2 className="mr-2 h-4 w-4" />
                                                                Delete
                                                            </Button>
                                                        </div>
                                                    </div>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                ) : (
                                    <div className="rounded-lg border border-dashed p-6 text-sm text-muted-foreground">
                                        No pipeline definition yet. Create one
                                        with a raw JSON stage plan and start
                                        shipping builds.
                                    </div>
                                )}

                                <div className="space-y-3">
                                    <div className="flex items-center justify-between gap-3">
                                        <div>
                                            <p className="font-medium">
                                                Recent runs
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                Latest webhook and manual
                                                executions across every
                                                pipeline.
                                            </p>
                                        </div>
                                    </div>

                                    {recentRuns.length ? (
                                        <div className="rounded-md border">
                                            <table className="w-full text-sm">
                                                <thead>
                                                    <tr className="border-b bg-muted/50">
                                                        <th className="px-4 py-2 text-left font-medium">
                                                            Pipeline
                                                        </th>
                                                        <th className="px-4 py-2 text-left font-medium">
                                                            Status
                                                        </th>
                                                        <th className="px-4 py-2 text-left font-medium">
                                                            Trigger
                                                        </th>
                                                        <th className="px-4 py-2 text-left font-medium">
                                                            Ref
                                                        </th>
                                                        <th className="px-4 py-2 text-left font-medium">
                                                            Environment
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {recentRuns.map((run) => (
                                                        <tr
                                                            key={run.id}
                                                            className="border-b last:border-0"
                                                        >
                                                            <td className="px-4 py-2">
                                                                <Link
                                                                    href={`/pipeline-runs/${run.id}`}
                                                                    className="font-medium text-primary hover:underline"
                                                                >
                                                                    {
                                                                        run
                                                                            .pipeline
                                                                            ?.name
                                                                    }
                                                                </Link>
                                                            </td>
                                                            <td className="px-4 py-2">
                                                                <Badge
                                                                    variant={statusVariant(
                                                                        run.status,
                                                                    )}
                                                                >
                                                                    {run.status}
                                                                </Badge>
                                                            </td>
                                                            <td className="px-4 py-2 text-muted-foreground">
                                                                {
                                                                    run.trigger_type
                                                                }
                                                            </td>
                                                            <td className="px-4 py-2 font-mono text-xs text-muted-foreground">
                                                                {run.trigger_ref ??
                                                                    '-'}
                                                            </td>
                                                            <td className="px-4 py-2 text-muted-foreground">
                                                                {run.environment
                                                                    ?.name ??
                                                                    '-'}
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">
                                            No runs yet. Trigger a manual build
                                            after saving a pipeline.
                                        </p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Settings</CardTitle>
                            <CardDescription>
                                Update repository details, runtime, and git
                                connection.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={saveSettings} className="space-y-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="app-name">Name</Label>
                                    <Input
                                        id="app-name"
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
                                    <Label htmlFor="app-runtime">Runtime</Label>
                                    <Select
                                        value={settingsForm.data.runtime}
                                        onValueChange={(value) =>
                                            settingsForm.setData(
                                                'runtime',
                                                value as
                                                    | 'php'
                                                    | 'node'
                                                    | 'python'
                                                    | 'go',
                                            )
                                        }
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="Runtime" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="php">
                                                PHP
                                            </SelectItem>
                                            <SelectItem value="node">
                                                Node.js
                                            </SelectItem>
                                            <SelectItem value="python">
                                                Python
                                            </SelectItem>
                                            <SelectItem value="go">
                                                Go
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={settingsForm.errors.runtime}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="app-repository-url">
                                        Repository URL
                                    </Label>
                                    <Input
                                        id="app-repository-url"
                                        value={settingsForm.data.repository_url}
                                        onChange={(event) =>
                                            settingsForm.setData(
                                                'repository_url',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={
                                            settingsForm.errors.repository_url
                                        }
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="app-repository-branch">
                                        Default Branch
                                    </Label>
                                    <Input
                                        id="app-repository-branch"
                                        value={
                                            settingsForm.data.repository_branch
                                        }
                                        onChange={(event) =>
                                            settingsForm.setData(
                                                'repository_branch',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={
                                            settingsForm.errors
                                                .repository_branch
                                        }
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="app-git-connection">
                                        Git Connection
                                    </Label>
                                    <Select
                                        value={
                                            settingsForm.data.git_connection_id
                                        }
                                        onValueChange={(value) =>
                                            settingsForm.setData(
                                                'git_connection_id',
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="Optional connection" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {gitConnections.map(
                                                (connection) => (
                                                    <SelectItem
                                                        key={connection.id}
                                                        value={connection.id}
                                                    >
                                                        {
                                                            connection.account_name
                                                        }{' '}
                                                        ({connection.provider})
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={
                                            settingsForm.errors
                                                .git_connection_id
                                        }
                                    />
                                </div>

                                <div className="flex items-center gap-3 pt-2">
                                    <Button disabled={settingsForm.processing}>
                                        <Pencil className="mr-2 h-4 w-4" />
                                        Save Changes
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <Dialog open={showDelete} onOpenChange={setShowDelete}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Application</DialogTitle>
                        <DialogDescription>
                            Delete {application.name}? This removes the
                            application record and hides it from the project.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="ghost"
                            onClick={() => setShowDelete(false)}
                        >
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={handleDelete}>
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={showPipelineDialog}
                onOpenChange={setShowPipelineDialog}
            >
                <DialogContent className="sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editingPipeline
                                ? 'Edit Pipeline'
                                : 'Create Pipeline'}
                        </DialogTitle>
                        <DialogDescription>
                            Use a raw JSON definition for the MVP pipeline
                            editor.
                        </DialogDescription>
                    </DialogHeader>

                    <form className="space-y-4" onSubmit={savePipeline}>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="pipeline-name">Name</Label>
                                <Input
                                    id="pipeline-name"
                                    value={pipelineForm.name}
                                    onChange={(event) =>
                                        setPipelineForm((current) => ({
                                            ...current,
                                            name: event.target.value,
                                        }))
                                    }
                                    placeholder="Production build"
                                />
                                <InputError message={pipelineErrors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="pipeline-branches">
                                    Trigger branches
                                </Label>
                                <Input
                                    id="pipeline-branches"
                                    value={pipelineForm.trigger_branches}
                                    onChange={(event) =>
                                        setPipelineForm((current) => ({
                                            ...current,
                                            trigger_branches:
                                                event.target.value,
                                        }))
                                    }
                                    placeholder="main, release/*"
                                />
                                <InputError
                                    message={pipelineErrors.trigger_branches}
                                />
                            </div>

                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="pipeline-events">
                                    Trigger events
                                </Label>
                                <Input
                                    id="pipeline-events"
                                    value={pipelineForm.trigger_events}
                                    onChange={(event) =>
                                        setPipelineForm((current) => ({
                                            ...current,
                                            trigger_events: event.target.value,
                                        }))
                                    }
                                    placeholder="push, manual"
                                />
                                <InputError
                                    message={pipelineErrors.trigger_events}
                                />
                            </div>

                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="pipeline-definition">
                                    Definition JSON
                                </Label>
                                <textarea
                                    id="pipeline-definition"
                                    value={pipelineForm.definition}
                                    onChange={(event) =>
                                        setPipelineForm((current) => ({
                                            ...current,
                                            definition: event.target.value,
                                        }))
                                    }
                                    className="min-h-80 rounded-md border bg-background px-3 py-2 font-mono text-sm"
                                />
                                <InputError
                                    message={pipelineErrors.definition}
                                />
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <input
                                id="pipeline-active"
                                type="checkbox"
                                checked={pipelineForm.is_active}
                                onChange={(event) =>
                                    setPipelineForm((current) => ({
                                        ...current,
                                        is_active: event.target.checked,
                                    }))
                                }
                            />
                            <Label htmlFor="pipeline-active">
                                Pipeline is active
                            </Label>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => setShowPipelineDialog(false)}
                            >
                                Cancel
                            </Button>
                            <Button disabled={pipelineProcessing}>
                                {editingPipeline
                                    ? 'Save Pipeline'
                                    : 'Create Pipeline'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={triggerPipelineId !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setTriggerPipelineId(null);
                        setTriggerEnvironmentId('');
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Trigger Pipeline</DialogTitle>
                        <DialogDescription>
                            Queue a manual run and optionally target one
                            environment.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="trigger-environment">Environment</Label>
                        <Select
                            value={triggerEnvironmentId || '__none'}
                            onValueChange={(value) =>
                                setTriggerEnvironmentId(
                                    value === '__none' ? '' : value,
                                )
                            }
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Optional environment" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__none">
                                    No environment
                                </SelectItem>
                                {application.environments?.map(
                                    (environment) => (
                                        <SelectItem
                                            key={environment.id}
                                            value={environment.id}
                                        >
                                            {environment.name}
                                        </SelectItem>
                                    ),
                                )}
                            </SelectContent>
                        </Select>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => setTriggerPipelineId(null)}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={triggerPipeline}
                            disabled={triggerProcessing}
                        >
                            Start Run
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

ApplicationShow.layout = {
    breadcrumbs: [
        {
            title: 'Projects',
            href: '/projects',
        },
        {
            title: 'Application',
            href: '/projects',
        },
    ],
};
