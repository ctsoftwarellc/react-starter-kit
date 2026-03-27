import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import ProjectWebController from '@/actions/App/Http/Controllers/Web/ProjectWebController';
import type { Project } from '@/types';

type Props = {
    project: Project;
};

export default function ProjectShow({ project }: Props) {
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
                <div className="mb-8 flex items-center justify-between">
                    <Heading
                        title={project.name}
                        description={project.description ?? undefined}
                    />
                    <div className="flex gap-2">
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

                <Card>
                    <CardContent className="flex flex-col items-center justify-center py-12">
                        <p className="text-muted-foreground">
                            Applications will appear here once you set up git
                            connections and create applications in Phase 3.
                        </p>
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={showDelete}
                onOpenChange={(open) => !open && setShowDelete(false)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Project</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete "{project.name}"?
                            This action cannot be undone.
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
