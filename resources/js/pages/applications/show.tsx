import { Head, Link, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
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
    Environment,
    GitConnection,
    Project,
} from '@/types';

type Props = {
    application: Application & {
        environments?: Array<Environment & { cluster?: Cluster }>;
    };
    project: Project;
    gitConnections: GitConnection[];
    clusters: Cluster[];
};

export default function ApplicationShow({
    application,
    project,
    gitConnections,
    clusters,
}: Props) {
    const [showDelete, setShowDelete] = useState(false);

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
                            <CardHeader>
                                <CardTitle>Later Phases</CardTitle>
                                <CardDescription>
                                    Pipeline, deployment, and release views land
                                    in upcoming phases.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-4 md:grid-cols-2">
                                <div className="rounded-lg border p-4">
                                    <p className="font-medium">Pipelines</p>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Pipeline definitions and recent runs
                                        arrive in Phase 4.
                                    </p>
                                </div>
                                <div className="rounded-lg border p-4">
                                    <p className="font-medium">Deployments</p>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Releases, rollouts, and rollback history
                                        arrive in Phase 5.
                                    </p>
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
