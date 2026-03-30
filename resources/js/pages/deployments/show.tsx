import { Head, Link, router, useForm } from '@inertiajs/react';
import { RotateCcw, ShieldCheck, StopCircle } from 'lucide-react';
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
    Deployment,
    DeploymentStep,
    Environment,
    HealthCheck,
    Release,
} from '@/types';

type Props = {
    deployment: Deployment;
    release: Release;
    environment: Environment;
    application: Application;
    steps: DeploymentStep[];
    healthCheck: HealthCheck | null;
    availableRollbackReleases: Release[];
};

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'active':
        case 'succeeded':
        case 'ready':
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
        throw new Error('Request failed.');
    }

    return response.json();
}

export default function DeploymentShow({
    deployment,
    release,
    environment,
    application,
    steps,
    healthCheck,
    availableRollbackReleases,
}: Props) {
    const [actionLoading, setActionLoading] = useState(false);
    const rollbackForm = useForm({
        release_id: availableRollbackReleases[0]?.id ?? '',
    });

    const canCancel = useMemo(
        () => deployment.status === 'pending',
        [deployment.status],
    );
    const canRollback = useMemo(
        () => availableRollbackReleases.length > 0,
        [availableRollbackReleases.length],
    );

    async function handleCancel() {
        setActionLoading(true);

        try {
            await postAction(`/api/v1/deployments/${deployment.id}/cancel`);
            router.reload();
        } finally {
            setActionLoading(false);
        }
    }

    function handleRollback(event: React.FormEvent) {
        event.preventDefault();
        rollbackForm.post(`/environments/${environment.id}/rollback`);
    }

    return (
        <>
            <Head title={`Deployment ${deployment.id}`} />

            <div className="space-y-6 px-4 py-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-3">
                        <div className="flex items-center gap-3">
                            <Heading
                                title={`Release v${release.version}`}
                                description={`${application.name} - ${environment.name}`}
                            />
                            <Badge variant={statusVariant(deployment.status)}>
                                {deployment.status}
                            </Badge>
                        </div>
                        <div className="flex flex-wrap gap-2 text-sm text-muted-foreground">
                            <span>Strategy: {deployment.strategy}</span>
                            <span>-</span>
                            <span>
                                Progress: {deployment.completed_nodes}/
                                {deployment.total_nodes}
                            </span>
                            <span>-</span>
                            <span>Failed: {deployment.failed_nodes}</span>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={`/environments/${environment.id}`}>
                                Back to environment
                            </Link>
                        </Button>
                        {canCancel && (
                            <Button
                                variant="outline"
                                onClick={handleCancel}
                                disabled={actionLoading}
                            >
                                <StopCircle className="mr-2 h-4 w-4" />
                                Cancel deployment
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.2fr,0.8fr]">
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Node Progress</CardTitle>
                                <CardDescription>
                                    Per-server rollout state and captured
                                    output.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {steps.length ? (
                                    steps.map((step) => (
                                        <div
                                            key={step.id}
                                            className="rounded-lg border p-4"
                                        >
                                            <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <p className="font-medium">
                                                            {step.server
                                                                ?.name ??
                                                                step.server_id}
                                                        </p>
                                                        <Badge
                                                            variant={statusVariant(
                                                                step.status,
                                                            )}
                                                        >
                                                            {step.status}
                                                        </Badge>
                                                    </div>
                                                    <p className="mt-1 text-sm text-muted-foreground">
                                                        {step.server
                                                            ?.public_ip ??
                                                            'No server address'}
                                                    </p>
                                                </div>

                                                <div className="text-sm text-muted-foreground">
                                                    {step.started_at
                                                        ? new Date(
                                                              step.started_at,
                                                          ).toLocaleString()
                                                        : 'Not started'}
                                                </div>
                                            </div>

                                            {step.output && (
                                                <div className="mt-4 rounded-lg border bg-zinc-950 p-4 text-xs text-zinc-100">
                                                    <pre className="font-mono break-words whitespace-pre-wrap">
                                                        {step.output}
                                                    </pre>
                                                </div>
                                            )}
                                        </div>
                                    ))
                                ) : (
                                    <div className="rounded-lg border border-dashed p-6 text-sm text-muted-foreground">
                                        Deployment steps will appear once node
                                        orchestration begins.
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Deployment Summary</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <div className="flex justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Environment
                                    </span>
                                    <span className="font-medium">
                                        {environment.name}
                                    </span>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Application
                                    </span>
                                    <span className="font-medium">
                                        {application.name}
                                    </span>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Release
                                    </span>
                                    <span className="font-medium">
                                        v{release.version}
                                    </span>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Artifact
                                    </span>
                                    <span className="font-mono text-xs">
                                        {release.artifact_id}
                                    </span>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Started
                                    </span>
                                    <span className="font-medium">
                                        {deployment.started_at
                                            ? new Date(
                                                  deployment.started_at,
                                              ).toLocaleString()
                                            : '-'}
                                    </span>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Finished
                                    </span>
                                    <span className="font-medium">
                                        {deployment.finished_at
                                            ? new Date(
                                                  deployment.finished_at,
                                              ).toLocaleString()
                                            : '-'}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Health Check</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                {healthCheck ? (
                                    <>
                                        <div className="flex justify-between gap-4">
                                            <span className="text-muted-foreground">
                                                Type
                                            </span>
                                            <span className="font-medium">
                                                {healthCheck.type}
                                            </span>
                                        </div>
                                        <div className="flex justify-between gap-4">
                                            <span className="text-muted-foreground">
                                                Target
                                            </span>
                                            <span className="font-medium">
                                                {healthCheck.target}
                                            </span>
                                        </div>
                                        <div className="flex justify-between gap-4">
                                            <span className="text-muted-foreground">
                                                Policy
                                            </span>
                                            <span className="font-medium">
                                                every{' '}
                                                {healthCheck.interval_seconds}s,
                                                timeout{' '}
                                                {healthCheck.timeout_seconds}s
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-2 text-muted-foreground">
                                            <ShieldCheck className="h-4 w-4" />
                                            {healthCheck.is_active
                                                ? 'Verification enabled'
                                                : 'Verification disabled'}
                                        </div>
                                    </>
                                ) : (
                                    <p className="text-muted-foreground">
                                        No health check configured for this
                                        environment.
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Manual Rollback</CardTitle>
                                <CardDescription>
                                    Target a previous release and create a new
                                    rollback deployment.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {canRollback ? (
                                    <form
                                        onSubmit={handleRollback}
                                        className="space-y-4"
                                    >
                                        <div className="grid gap-2">
                                            <Label htmlFor="rollback-release">
                                                Release
                                            </Label>
                                            <Select
                                                value={
                                                    rollbackForm.data.release_id
                                                }
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
                                                    {availableRollbackReleases.map(
                                                        (rollbackRelease) => (
                                                            <SelectItem
                                                                key={
                                                                    rollbackRelease.id
                                                                }
                                                                value={
                                                                    rollbackRelease.id
                                                                }
                                                            >
                                                                v
                                                                {
                                                                    rollbackRelease.version
                                                                }{' '}
                                                                -{' '}
                                                                {
                                                                    rollbackRelease.status
                                                                }
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    rollbackForm.errors
                                                        .release_id
                                                }
                                            />
                                        </div>

                                        <Button
                                            variant="outline"
                                            disabled={rollbackForm.processing}
                                        >
                                            <RotateCcw className="mr-2 h-4 w-4" />
                                            Create Rollback Deployment
                                        </Button>
                                    </form>
                                ) : (
                                    <p className="text-muted-foreground">
                                        No alternate release is available yet.
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Config Snapshot</CardTitle>
                                <CardDescription>
                                    Immutable release settings captured at build
                                    time.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="rounded-lg border bg-zinc-950 p-4 text-xs text-zinc-100">
                                    <pre className="font-mono break-words whitespace-pre-wrap">
                                        {JSON.stringify(
                                            release.config_snapshot,
                                            null,
                                            2,
                                        )}
                                    </pre>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

DeploymentShow.layout = {
    breadcrumbs: [
        {
            title: 'Projects',
            href: '/projects',
        },
        {
            title: 'Deployment',
            href: '/projects',
        },
    ],
};
