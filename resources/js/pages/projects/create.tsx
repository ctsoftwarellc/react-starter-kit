import { Head, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function ProjectCreate() {
    const form = useForm({
        name: '',
        description: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.post('/projects');
    }

    return (
        <>
            <Head title="Create Project" />

            <div className="mx-auto max-w-xl px-4 py-6">
                <Heading
                    title="Create Project"
                    description="Create a new project to organize your applications"
                />

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Project Name</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            placeholder="e.g. My Web App"
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
                            placeholder="A brief description of this project"
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    <div className="flex items-center gap-4">
                        <Button disabled={form.processing}>
                            Create Project
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

ProjectCreate.layout = {
    breadcrumbs: [
        {
            title: 'Projects',
            href: '/projects',
        },
        {
            title: 'Create',
            href: '/projects/create',
        },
    ],
};
