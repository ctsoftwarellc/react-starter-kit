import { Head, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ProjectWebController from '@/actions/App/Http/Controllers/Web/ProjectWebController';
import type { Project } from '@/types';

type Props = {
    project: Project;
};

export default function ProjectEdit({ project }: Props) {
    const form = useForm({
        name: project.name,
        description: project.description ?? '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.put(ProjectWebController.update(project).url);
    }

    return (
        <>
            <Head title={`Edit ${project.name}`} />

            <div className="mx-auto max-w-xl px-4 py-6">
                <Heading
                    title="Edit Project"
                    description={`Update settings for ${project.name}`}
                />

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Project Name</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            required
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">
                            Description{' '}
                            <span className="text-muted-foreground">
                                (optional)
                            </span>
                        </Label>
                        <textarea
                            id="description"
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                            className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[100px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    <div className="flex items-center gap-4">
                        <Button disabled={form.processing}>
                            Update Project
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

ProjectEdit.layout = {
    breadcrumbs: [
        {
            title: 'Projects',
            href: '/projects',
        },
        {
            title: 'Edit',
            href: '/projects',
        },
    ],
};
