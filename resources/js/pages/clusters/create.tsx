import { Head, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function ClusterCreate() {
    const form = useForm({
        name: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.post('/clusters');
    }

    return (
        <>
            <Head title="Create Cluster" />

            <div className="mx-auto max-w-xl px-4 py-6">
                <Heading
                    title="Create Cluster"
                    description="Create a new cluster to group your servers"
                />

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Cluster Name</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            placeholder="e.g. production"
                            required
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="flex items-center gap-4">
                        <Button disabled={form.processing}>Create Cluster</Button>
                    </div>
                </form>
            </div>
        </>
    );
}

ClusterCreate.layout = {
    breadcrumbs: [
        {
            title: 'Clusters',
            href: '/clusters',
        },
        {
            title: 'Create',
            href: '/clusters/create',
        },
    ],
};
