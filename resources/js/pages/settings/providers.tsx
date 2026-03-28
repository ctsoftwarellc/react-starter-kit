import { Transition } from '@headlessui/react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Trash2, Zap } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import type { Provider } from '@/types';

type Props = {
    providers: Provider[];
};

const providerTypes = [
    { value: 'digitalocean', label: 'DigitalOcean' },
    { value: 'hetzner', label: 'Hetzner' },
    { value: 'vultr', label: 'Vultr' },
    { value: 'aws', label: 'AWS' },
    { value: 'manual', label: 'Manual' },
] as const;

export default function Providers({ providers }: Props) {
    const { flash } = usePage().props as unknown as {
        flash: { success?: string; error?: string };
    };

    const form = useForm({
        name: '',
        type: '',
        credentials: '{}',
    });

    const [deleteTarget, setDeleteTarget] = useState<Provider | null>(null);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        let credentials: Record<string, string> = {};
        try {
            credentials = JSON.parse(form.data.credentials);
        } catch {
            return;
        }

        router.post('/settings/providers', {
            name: form.data.name,
            type: form.data.type,
            credentials,
        }, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    function handleDelete() {
        if (!deleteTarget) return;
        router.delete(`/settings/providers/${deleteTarget.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeleteTarget(null),
        });
    }

    function handleTest(provider: Provider) {
        router.post(`/settings/providers/${provider.id}/test`, {}, {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Providers" />

            <h1 className="sr-only">Providers</h1>

            {flash?.success && (
                <div className="rounded-md border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950">
                    <p className="text-sm text-green-800 dark:text-green-200">
                        {flash.success}
                    </p>
                </div>
            )}

            {flash?.error && (
                <div className="rounded-md border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950">
                    <p className="text-sm text-red-800 dark:text-red-200">
                        {flash.error}
                    </p>
                </div>
            )}

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Providers"
                    description="Manage cloud provider connections"
                />

                {providers.length > 0 && (
                    <div className="rounded-md border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-4 py-2 text-left font-medium">Name</th>
                                    <th className="px-4 py-2 text-left font-medium">Type</th>
                                    <th className="px-4 py-2 text-left font-medium">Status</th>
                                    <th className="px-4 py-2 text-right font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {providers.map((provider) => (
                                    <tr key={provider.id} className="border-b last:border-0">
                                        <td className="px-4 py-2 font-medium">{provider.name}</td>
                                        <td className="px-4 py-2 text-muted-foreground">{provider.type}</td>
                                        <td className="px-4 py-2">
                                            <Badge variant={provider.is_active ? 'default' : 'secondary'}>
                                                {provider.is_active ? 'Active' : 'Inactive'}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-2 text-right">
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleTest(provider)}
                                                >
                                                    <Zap className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => setDeleteTarget(provider)}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {providers.length === 0 && (
                    <p className="text-sm text-muted-foreground">
                        No providers configured yet.
                    </p>
                )}
            </div>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Add Provider"
                    description="Connect a new cloud provider"
                />

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="provider-name">Provider Name</Label>
                        <Input
                            id="provider-name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            placeholder="e.g. My DigitalOcean"
                            required
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="provider-type">Type</Label>
                        <Select
                            value={form.data.type}
                            onValueChange={(value) => form.setData('type', value)}
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Select provider type" />
                            </SelectTrigger>
                            <SelectContent>
                                {providerTypes.map((type) => (
                                    <SelectItem key={type.value} value={type.value}>
                                        {type.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.type} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="credentials">Credentials (JSON)</Label>
                        <textarea
                            id="credentials"
                            value={form.data.credentials}
                            onChange={(e) => form.setData('credentials', e.target.value)}
                            className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[100px] w-full rounded-md border px-3 py-2 font-mono text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder='{"api_token": "..."}'
                            required
                        />
                        <InputError message={form.errors.credentials} />
                    </div>

                    <div className="flex items-center gap-4">
                        <Button disabled={form.processing}>Add Provider</Button>

                        <Transition
                            show={form.recentlySuccessful}
                            enter="transition ease-in-out"
                            enterFrom="opacity-0"
                            leave="transition ease-in-out"
                            leaveTo="opacity-0"
                        >
                            <p className="text-sm text-neutral-600">Added</p>
                        </Transition>
                    </div>
                </form>
            </div>

            <Dialog
                open={deleteTarget !== null}
                onOpenChange={(open) => !open && setDeleteTarget(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Provider</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete the provider "
                            {deleteTarget?.name}"? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="ghost"
                            onClick={() => setDeleteTarget(null)}
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

Providers.layout = {
    breadcrumbs: [
        {
            title: 'Providers',
            href: '/settings/providers',
        },
    ],
};
