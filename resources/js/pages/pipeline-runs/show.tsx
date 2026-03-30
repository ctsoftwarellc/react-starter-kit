import { Head, Link, router } from '@inertiajs/react';
import { RefreshCw, SquareTerminal, StopCircle } from 'lucide-react';
import { useMemo, useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type {
    Application,
    Artifact,
    Pipeline,
    PipelineJob,
    PipelineRun,
} from '@/types';

type StageGroup = {
    name: string;
    jobs: PipelineJob[];
};

type Props = {
    pipelineRun: PipelineRun;
    pipeline: Pipeline;
    application: Application;
    stages: StageGroup[];
    artifact: Artifact | null;
};

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'succeeded':
        case 'ready':
        case 'online':
            return 'default';
        case 'running':
        case 'queued':
        case 'assigned':
        case 'pending':
        case 'building':
            return 'secondary';
        case 'failed':
        case 'cancelled':
        case 'timed_out':
            return 'destructive';
        default:
            return 'outline';
    }
}

async function postAction(url: string) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw await response.json().catch(() => null);
    }

    return (await response.json()) as { data: PipelineRun };
}

async function fetchLog(jobId: string) {
    const response = await fetch(`/api/v1/pipeline-jobs/${jobId}/log`, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Unable to load log output.');
    }

    return (await response.json()) as { content: string };
}

function formatDuration(run: PipelineRun) {
    if (run.duration_seconds == null) {
        return 'Not started';
    }

    if (run.duration_seconds < 60) {
        return `${run.duration_seconds}s`;
    }

    const minutes = Math.floor(run.duration_seconds / 60);
    const seconds = run.duration_seconds % 60;

    return `${minutes}m ${seconds}s`;
}

export default function PipelineRunShow({
    pipelineRun,
    pipeline,
    application,
    stages,
    artifact,
}: Props) {
    const [selectedJobId, setSelectedJobId] = useState<string | null>(null);
    const [logs, setLogs] = useState<Record<string, string>>({});
    const [loadingJobId, setLoadingJobId] = useState<string | null>(null);
    const [actionLoading, setActionLoading] = useState<
        'cancel' | 'retry' | null
    >(null);

    const canCancel = useMemo(
        () => ['pending', 'running'].includes(pipelineRun.status),
        [pipelineRun.status],
    );
    const canRetry = useMemo(
        () =>
            ['failed', 'cancelled', 'timed_out', 'succeeded'].includes(
                pipelineRun.status,
            ),
        [pipelineRun.status],
    );

    async function handleCancel() {
        setActionLoading('cancel');

        try {
            await postAction(`/api/v1/pipeline-runs/${pipelineRun.id}/cancel`);
            router.reload();
        } finally {
            setActionLoading(null);
        }
    }

    async function handleRetry() {
        setActionLoading('retry');

        try {
            const payload = await postAction(
                `/api/v1/pipeline-runs/${pipelineRun.id}/retry`,
            );
            router.visit(`/pipeline-runs/${payload.data.id}`);
        } finally {
            setActionLoading(null);
        }
    }

    async function openLog(jobId: string) {
        setSelectedJobId(jobId);

        if (logs[jobId]) {
            return;
        }

        setLoadingJobId(jobId);

        try {
            const payload = await fetchLog(jobId);
            setLogs((current) => ({ ...current, [jobId]: payload.content }));
        } finally {
            setLoadingJobId(null);
        }
    }

    return (
        <>
            <Head title={`Run ${pipelineRun.id}`} />

            <div className="space-y-6 px-4 py-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-3">
                        <div className="flex items-center gap-3">
                            <Heading
                                title={pipeline.name}
                                description={`Run ${pipelineRun.id}`}
                            />
                            <Badge variant={statusVariant(pipelineRun.status)}>
                                {pipelineRun.status}
                            </Badge>
                        </div>
                        <div className="flex flex-wrap gap-2 text-sm text-muted-foreground">
                            <span>Application: {application.name}</span>
                            <span>-</span>
                            <span>Trigger: {pipelineRun.trigger_type}</span>
                            <span>-</span>
                            <span>Duration: {formatDuration(pipelineRun)}</span>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={`/applications/${application.id}`}>
                                Back to application
                            </Link>
                        </Button>
                        {canCancel && (
                            <Button
                                variant="outline"
                                onClick={handleCancel}
                                disabled={actionLoading !== null}
                            >
                                <StopCircle className="mr-2 h-4 w-4" />
                                Cancel run
                            </Button>
                        )}
                        {canRetry && (
                            <Button
                                onClick={handleRetry}
                                disabled={actionLoading !== null}
                            >
                                <RefreshCw className="mr-2 h-4 w-4" />
                                Retry run
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.4fr,0.9fr]">
                    <div className="space-y-6">
                        {stages.map((stage, index) => (
                            <Card key={stage.name}>
                                <CardHeader>
                                    <CardTitle>
                                        Stage {index + 1}: {stage.name}
                                    </CardTitle>
                                    <CardDescription>
                                        Sequential stage with{' '}
                                        {stage.jobs.length} job
                                        {stage.jobs.length === 1 ? '' : 's'}.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="grid gap-4">
                                    {stage.jobs.map((job) => (
                                        <div
                                            key={job.id}
                                            className="rounded-lg border p-4"
                                        >
                                            <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <p className="font-medium">
                                                            {job.name}
                                                        </p>
                                                        <Badge
                                                            variant={statusVariant(
                                                                job.status,
                                                            )}
                                                        >
                                                            {job.status}
                                                        </Badge>
                                                    </div>
                                                    <p className="mt-1 text-sm text-muted-foreground">
                                                        {job.commands.join(
                                                            ' && ',
                                                        )}
                                                    </p>
                                                    {job.runner && (
                                                        <p className="mt-2 text-xs text-muted-foreground">
                                                            Runner:{' '}
                                                            {job.runner.name}
                                                        </p>
                                                    )}
                                                </div>

                                                <Button
                                                    variant="outline"
                                                    onClick={() =>
                                                        openLog(job.id)
                                                    }
                                                >
                                                    <SquareTerminal className="mr-2 h-4 w-4" />
                                                    View log
                                                </Button>
                                            </div>

                                            {selectedJobId === job.id && (
                                                <div className="mt-4 rounded-lg border bg-zinc-950 p-4 text-xs text-zinc-100">
                                                    <pre className="font-mono break-words whitespace-pre-wrap">
                                                        {loadingJobId === job.id
                                                            ? 'Loading log output...'
                                                            : logs[job.id] ||
                                                              'No log output yet.'}
                                                    </pre>
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        ))}
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Trigger metadata</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <div className="flex justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Ref
                                    </span>
                                    <span className="font-medium">
                                        {pipelineRun.trigger_ref ?? '-'}
                                    </span>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        SHA
                                    </span>
                                    <span className="font-mono text-xs">
                                        {pipelineRun.trigger_sha ?? '-'}
                                    </span>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Actor
                                    </span>
                                    <span className="font-medium">
                                        {pipelineRun.trigger_actor ?? '-'}
                                    </span>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Started
                                    </span>
                                    <span className="font-medium">
                                        {pipelineRun.started_at
                                            ? new Date(
                                                  pipelineRun.started_at,
                                              ).toLocaleString()
                                            : '-'}
                                    </span>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Finished
                                    </span>
                                    <span className="font-medium">
                                        {pipelineRun.finished_at
                                            ? new Date(
                                                  pipelineRun.finished_at,
                                              ).toLocaleString()
                                            : '-'}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Artifact</CardTitle>
                                <CardDescription>
                                    Immutable build output produced by this run.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                {artifact ? (
                                    <>
                                        <div className="flex justify-between gap-4">
                                            <span className="text-muted-foreground">
                                                Status
                                            </span>
                                            <Badge
                                                variant={statusVariant(
                                                    artifact.status,
                                                )}
                                            >
                                                {artifact.status}
                                            </Badge>
                                        </div>
                                        <div className="flex justify-between gap-4">
                                            <span className="text-muted-foreground">
                                                Hash
                                            </span>
                                            <span className="font-mono text-xs">
                                                {artifact.content_hash ?? '-'}
                                            </span>
                                        </div>
                                        <div className="flex justify-between gap-4">
                                            <span className="text-muted-foreground">
                                                Size
                                            </span>
                                            <span className="font-medium">
                                                {artifact.size_bytes != null
                                                    ? `${Math.round(artifact.size_bytes / 1024)} KB`
                                                    : '-'}
                                            </span>
                                        </div>
                                        <div className="flex justify-between gap-4">
                                            <span className="text-muted-foreground">
                                                Storage path
                                            </span>
                                            <span className="font-mono text-xs">
                                                {artifact.storage_path ?? '-'}
                                            </span>
                                        </div>
                                    </>
                                ) : (
                                    <p className="text-muted-foreground">
                                        No artifact has been uploaded for this
                                        run yet.
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

PipelineRunShow.layout = {
    breadcrumbs: [
        {
            title: 'Projects',
            href: '/projects',
        },
        {
            title: 'Pipeline Run',
            href: '/pipeline-runs',
        },
    ],
};
