import { Head, router, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Cluster, GitConnection, Project } from '@/types';

type Props = {
    project: Project;
    gitConnections: GitConnection[];
    clusters: Cluster[];
};

const runtimes = [
    { value: 'php', label: 'PHP' },
    { value: 'node', label: 'Node.js' },
    { value: 'python', label: 'Python' },
    { value: 'go', label: 'Go' },
] as const;

export default function ApplicationCreate({
    project,
    gitConnections,
    clusters,
}: Props) {
    const form = useForm({
        name: '',
        runtime: 'php',
        repository_url: '',
        repository_branch: 'main',
        git_connection_id: '',
        default_cluster_id: '',
    });

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        form.post(`/projects/${project.slug}/applications`);
    }

    function connectGithub() {
        router.post('/git-connections/github/authorize', {
            redirect_to: `/projects/${project.slug}/applications/create`,
        });
    }

    return (
        <>
            <Head title={`New Application - ${project.name}`} />

            <div className="mx-auto max-w-3xl space-y-6 px-4 py-6">
                <Heading
                    title="Create Application"
                    description={`Add a deployable application to ${project.name}`}
                />

                <Card>
                    <CardHeader>
                        <CardTitle>Repository Connection</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {gitConnections.length === 0 ? (
                            <Alert>
                                <AlertDescription className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <span>
                                        No git connections yet. Connect GitHub
                                        first so you can link a repository.
                                    </span>
                                    <Button
                                        type="button"
                                        onClick={connectGithub}
                                    >
                                        Connect GitHub
                                    </Button>
                                </AlertDescription>
                            </Alert>
                        ) : (
                            <div className="flex items-center justify-between rounded-lg border p-4">
                                <div>
                                    <p className="font-medium">
                                        {gitConnections.length} connection(s)
                                        available
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Choose an existing connection below or
                                        add another GitHub account.
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={connectGithub}
                                >
                                    Add GitHub Connection
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Application Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Application Name</Label>
                                <Input
                                    id="name"
                                    value={form.data.name}
                                    onChange={(event) =>
                                        form.setData('name', event.target.value)
                                    }
                                    placeholder="e.g. marketing-site"
                                    required
                                />
                                <InputError message={form.errors.name} />
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="runtime">Runtime</Label>
                                    <Select
                                        value={form.data.runtime}
                                        onValueChange={(value) =>
                                            form.setData(
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
                                            <SelectValue placeholder="Select runtime" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {runtimes.map((runtime) => (
                                                <SelectItem
                                                    key={runtime.value}
                                                    value={runtime.value}
                                                >
                                                    {runtime.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={form.errors.runtime} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="git_connection_id">
                                        Git Connection
                                    </Label>
                                    <Select
                                        value={form.data.git_connection_id}
                                        onValueChange={(value) =>
                                            form.setData(
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
                                        message={form.errors.git_connection_id}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="repository_url">
                                    Repository URL
                                </Label>
                                <Input
                                    id="repository_url"
                                    value={form.data.repository_url}
                                    onChange={(event) =>
                                        form.setData(
                                            'repository_url',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="https://github.com/owner/repository"
                                />
                                <InputError
                                    message={form.errors.repository_url}
                                />
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="repository_branch">
                                        Default Branch
                                    </Label>
                                    <Input
                                        id="repository_branch"
                                        value={form.data.repository_branch}
                                        onChange={(event) =>
                                            form.setData(
                                                'repository_branch',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="main"
                                    />
                                    <InputError
                                        message={form.errors.repository_branch}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="default_cluster_id">
                                        Default Cluster
                                    </Label>
                                    <Select
                                        value={form.data.default_cluster_id}
                                        onValueChange={(value) =>
                                            form.setData(
                                                'default_cluster_id',
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="Optional default environment cluster" />
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
                                        message={form.errors.default_cluster_id}
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-3">
                                <Button disabled={form.processing}>
                                    Create Application
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => history.back()}
                                >
                                    Cancel
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

ApplicationCreate.layout = {
    breadcrumbs: [
        {
            title: 'Projects',
            href: '/projects',
        },
        {
            title: 'New Application',
            href: '/projects',
        },
    ],
};
