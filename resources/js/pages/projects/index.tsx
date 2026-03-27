import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import ProjectWebController from '@/actions/App/Http/Controllers/Web/ProjectWebController';
import type { PaginatedData, Project } from '@/types';

type Props = {
    projects: PaginatedData<Project>;
};

export default function ProjectIndex({ projects }: Props) {
    return (
        <>
            <Head title="Projects" />

            <div className="px-4 py-6">
                <div className="mb-8 flex items-center justify-between">
                    <Heading
                        title="Projects"
                        description="Manage your application projects"
                    />
                    <Button asChild>
                        <Link href={ProjectWebController.create()}>
                            <Plus className="mr-2 h-4 w-4" />
                            New Project
                        </Link>
                    </Button>
                </div>

                {projects.data.length > 0 ? (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {projects.data.map((project) => (
                            <Link
                                key={project.id}
                                href={ProjectWebController.show(project)}
                                className="block"
                            >
                                <Card className="transition-colors hover:bg-muted/50">
                                    <CardHeader>
                                        <CardTitle>{project.name}</CardTitle>
                                        {project.description && (
                                            <CardDescription>
                                                {project.description}
                                            </CardDescription>
                                        )}
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-xs text-muted-foreground">
                                            Created{' '}
                                            {new Date(
                                                project.created_at,
                                            ).toLocaleDateString()}
                                        </p>
                                    </CardContent>
                                </Card>
                            </Link>
                        ))}
                    </div>
                ) : (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <p className="mb-4 text-muted-foreground">
                                No projects yet. Create your first project to
                                get started.
                            </p>
                            <Button asChild>
                                <Link href={ProjectWebController.create()}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    New Project
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                )}

                {projects.meta.last_page > 1 && (
                    <div className="mt-6 flex justify-center gap-2">
                        {projects.links.prev && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={projects.links.prev}>Previous</Link>
                            </Button>
                        )}
                        <span className="flex items-center px-3 text-sm text-muted-foreground">
                            Page {projects.meta.current_page} of{' '}
                            {projects.meta.last_page}
                        </span>
                        {projects.links.next && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={projects.links.next}>Next</Link>
                            </Button>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

ProjectIndex.layout = {
    breadcrumbs: [
        {
            title: 'Projects',
            href: '/projects',
        },
    ],
};
