import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import ProjectWebController from '@/actions/App/Http/Controllers/Web/ProjectWebController';
import type { Application, Project } from '@/types';

type Props = {
    project: Project;
    applications: Application[];
};

function runtimeVariant(
    runtime: Application['runtime'],
): 'default' | 'secondary' | 'outline' {
    switch (runtime) {
        case 'php':
            return 'default';
        case 'node':
        case 'python':
            return 'secondary';
        default:
            return 'outline';
    }
}

export default function ProjectShow({ project, applications }: Props) {
    const [showDelete, setShowDelete] = useState(false);

    function handleDelete() {
        router.delete(ProjectWebController.destroy(project).url, {
            onSuccess: () => setShowDelete(false),
        });
    }

    return (
        <>
            <Head title={project.name} />

            <div className="px-4 py-6">
                <div className="mb-8 flex items-center justify-between gap-4">
                    <Heading
                        title={project.name}
                        description={project.description ?? undefined}
                    />
                    <div className="flex gap-2">
                        <Button asChild>
                            <Link
                                href={`/projects/${project.slug}/applications/create`}
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                New Application
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={ProjectWebController.edit(project)}>
                                <Pencil className="mr-2 h-4 w-4" />
                                Edit
                            </Link>
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => setShowDelete(true)}
                        >
                            <Trash2 className="mr-2 h-4 w-4" />
                            Delete
                        </Button>
                    </div>
                </div>

                {applications.length > 0 ? (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {applications.map((application) => (
                            <Link
                                key={application.id}
                                href={`/applications/${application.id}`}
                            >
                                <Card className="h-full transition-colors hover:bg-muted/40">
                                    <CardHeader>
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <CardTitle>
                                                    {application.name}
                                                </CardTitle>
                                                <CardDescription>
                                                    {application.repository_url ??
                                                        'No repository connected yet'}
                                                </CardDescription>
                                            </div>
                                            <Badge
                                                variant={runtimeVariant(
                                                    application.runtime,
                                                )}
                                            >
                                                {application.runtime}
                                            </Badge>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="space-y-3 text-sm">
                                        <div className="flex items-center justify-between text-muted-foreground">
                                            <span>Branch</span>
                                            <span className="font-medium text-foreground">
                                                {application.repository_branch}
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between text-muted-foreground">
                                            <span>Environments</span>
                                            <span className="font-medium text-foreground">
                                                {application.environments_count ??
                                                    application.environments
                                                        ?.length ??
                                                    0}
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between text-muted-foreground">
                                            <span>Git Connection</span>
                                            <span className="font-medium text-foreground">
                                                {application.git_connection
                                                    ?.account_name ??
                                                    'Not connected'}
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-2 pt-2 text-sm font-medium text-primary">
                                            Open application
                                            <ArrowRight className="h-4 w-4" />
                                        </div>
                                    </CardContent>
                                </Card>
                            </Link>
                        ))}
                    </div>
                ) : (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center gap-4 py-12 text-center">
                            <p className="max-w-md text-muted-foreground">
                                No applications yet. Create your first
                                application to connect a repository, define
                                environments, and prepare for pipelines.
                            </p>
                            <Button asChild>
                                <Link
                                    href={`/projects/${project.slug}/applications/create`}
                                >
                                    <Plus className="mr-2 h-4 w-4" />
                                    Create Application
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                )}
            </div>

            <Dialog
                open={showDelete}
                onOpenChange={(open) => !open && setShowDelete(false)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Project</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete &quot;{project.name}
                            &quot;? This action cannot be undone.
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

ProjectShow.layout = {
    breadcrumbs: [
        {
            title: 'Projects',
            href: '/projects',
        },
        {
            title: 'Project',
            href: '/projects',
        },
    ],
};
