import { Head, Link, router } from '@inertiajs/react';
import { Copy, Play, Plus } from 'lucide-react';
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
import type { PaginatedData, Runner } from '@/types';

type Props = {
    runners: PaginatedData<Runner>;
};

type RunnerForm = {
    name: string;
    platform: string;
};

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'online':
            return 'default';
        case 'busy':
            return 'secondary';
        case 'offline':
            return 'outline';
        case 'draining':
            return 'destructive';
        default:
            return 'outline';
    }
}

async function createRunner(payload: RunnerForm) {
    const response = await fetch('/api/v1/runners', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            name: payload.name,
            platform: payload.platform || null,
        }),
    });

    const json = response.status === 204 ? null : await response.json();

    if (!response.ok) {
        throw json;
    }

    return json as { data: Runner; plain_text_token?: string };
}

export default function RunnersIndex({ runners }: Props) {
    const [showCreate, setShowCreate] = useState(false);
    const [form, setForm] = useState<RunnerForm>({ name: '', platform: '' });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);
    const [plainTextToken, setPlainTextToken] = useState<string | null>(null);

    const emptyState = useMemo(
        () => runners.data.length === 0,
        [runners.data.length],
    );

    async function handleCreate(event: React.FormEvent) {
        event.preventDefault();
        setProcessing(true);
        setErrors({});

        try {
            const payload = await createRunner(form);
            setPlainTextToken(payload.plain_text_token ?? null);
            setForm({ name: '', platform: '' });
            setShowCreate(false);
            router.reload({ only: ['runners'] });
        } catch (error) {
            const payload = error as { errors?: Record<string, string[]> };

            setErrors(
                Object.fromEntries(
                    Object.entries(payload.errors ?? {}).map(([key, value]) => [
                        key,
                        value[0] ?? 'Invalid value.',
                    ]),
                ),
            );
        } finally {
            setProcessing(false);
        }
    }

    async function copyToken() {
        if (!plainTextToken) {
            return;
        }

        await navigator.clipboard.writeText(plainTextToken);
    }

    return (
        <>
            <Head title="Runners" />

            <div className="space-y-6 px-4 py-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <Heading
                        title="Runners"
                        description="Register build workers, watch heartbeat health, and spot the job currently in flight."
                    />

                    <Button onClick={() => setShowCreate(true)}>
                        <Plus className="mr-2 h-4 w-4" />
                        Register Runner
                    </Button>
                </div>

                {plainTextToken && (
                    <Card className="border-primary/30">
                        <CardHeader>
                            <CardTitle>Runner token</CardTitle>
                            <CardDescription>
                                This secret is only shown once. Store it in the
                                runner host before closing this message.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="rounded-lg border bg-muted/40 p-3 font-mono text-sm break-all">
                                {plainTextToken}
                            </div>
                            <div className="flex gap-2">
                                <Button variant="outline" onClick={copyToken}>
                                    <Copy className="mr-2 h-4 w-4" />
                                    Copy token
                                </Button>
                                <Button
                                    variant="ghost"
                                    onClick={() => setPlainTextToken(null)}
                                >
                                    Dismiss
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {emptyState ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>No runners registered</CardTitle>
                            <CardDescription>
                                Helm runners connect to `/api/runner/*`, poll
                                for jobs, stream logs, and upload artifacts.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button onClick={() => setShowCreate(true)}>
                                <Play className="mr-2 h-4 w-4" />
                                Create first runner
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50">
                                            <th className="px-4 py-3 text-left font-medium">
                                                Runner
                                            </th>
                                            <th className="px-4 py-3 text-left font-medium">
                                                Status
                                            </th>
                                            <th className="px-4 py-3 text-left font-medium">
                                                Platform
                                            </th>
                                            <th className="px-4 py-3 text-left font-medium">
                                                Current Job
                                            </th>
                                            <th className="px-4 py-3 text-left font-medium">
                                                Last Heartbeat
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {runners.data.map((runner) => (
                                            <tr
                                                key={runner.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="px-4 py-3 font-medium">
                                                    {runner.name}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge
                                                        variant={statusVariant(
                                                            runner.status,
                                                        )}
                                                    >
                                                        {runner.status}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {runner.platform ?? '-'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {runner.current_job ? (
                                                        <div className="space-y-1">
                                                            <div className="font-medium">
                                                                {
                                                                    runner
                                                                        .current_job
                                                                        .stage
                                                                }{' '}
                                                                /{' '}
                                                                {
                                                                    runner
                                                                        .current_job
                                                                        .name
                                                                }
                                                            </div>
                                                            <div className="text-xs text-muted-foreground">
                                                                {runner
                                                                    .current_job
                                                                    .pipeline
                                                                    ?.name ??
                                                                    'Pipeline'}
                                                            </div>
                                                            <Link
                                                                href={`/pipeline-runs/${runner.current_job.pipeline_run_id}`}
                                                                className="text-xs text-primary hover:underline"
                                                            >
                                                                View run
                                                            </Link>
                                                        </div>
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            Idle
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {runner.last_heartbeat_at
                                                        ? new Date(
                                                              runner.last_heartbeat_at,
                                                          ).toLocaleString()
                                                        : 'Never'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>

            <Dialog open={showCreate} onOpenChange={setShowCreate}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Register Runner</DialogTitle>
                        <DialogDescription>
                            Create a polling worker for pipeline jobs. The
                            generated token is shown only once.
                        </DialogDescription>
                    </DialogHeader>

                    <form className="space-y-4" onSubmit={handleCreate}>
                        <div className="grid gap-2">
                            <Label htmlFor="runner-name">Name</Label>
                            <Input
                                id="runner-name"
                                value={form.name}
                                onChange={(event) =>
                                    setForm((current) => ({
                                        ...current,
                                        name: event.target.value,
                                    }))
                                }
                                placeholder="build-linux-01"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="runner-platform">Platform</Label>
                            <Input
                                id="runner-platform"
                                value={form.platform}
                                onChange={(event) =>
                                    setForm((current) => ({
                                        ...current,
                                        platform: event.target.value,
                                    }))
                                }
                                placeholder="linux-amd64"
                            />
                            <InputError message={errors.platform} />
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => setShowCreate(false)}
                            >
                                Cancel
                            </Button>
                            <Button disabled={processing}>
                                Register Runner
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

RunnersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Runners',
            href: '/runners',
        },
    ],
};
