import { Transition } from '@headlessui/react';
import { Head, router, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import type { SshKey } from '@/types';

type Props = {
    sshKeys: SshKey[];
};

export default function SshKeys({ sshKeys }: Props) {
    const form = useForm({
        name: '',
        public_key: '',
    });

    const [deleteTarget, setDeleteTarget] = useState<SshKey | null>(null);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.post('/settings/ssh-keys', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    function handleDelete() {
        if (!deleteTarget) return;
        router.delete(`/settings/ssh-keys/${deleteTarget.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeleteTarget(null),
        });
    }

    return (
        <>
            <Head title="SSH Keys" />

            <h1 className="sr-only">SSH Keys</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="SSH Keys"
                    description="Manage SSH keys used for server access"
                />

                {sshKeys.length > 0 && (
                    <div className="rounded-md border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-4 py-2 text-left font-medium">
                                        Name
                                    </th>
                                    <th className="px-4 py-2 text-left font-medium">
                                        Fingerprint
                                    </th>
                                    <th className="px-4 py-2 text-left font-medium">
                                        Added
                                    </th>
                                    <th className="px-4 py-2 text-right font-medium">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {sshKeys.map((key) => (
                                    <tr
                                        key={key.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-4 py-2 font-medium">
                                            {key.name}
                                        </td>
                                        <td className="px-4 py-2 font-mono text-xs text-muted-foreground">
                                            {key.fingerprint}
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground">
                                            {new Date(
                                                key.created_at,
                                            ).toLocaleDateString()}
                                        </td>
                                        <td className="px-4 py-2 text-right">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    setDeleteTarget(key)
                                                }
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {sshKeys.length === 0 && (
                    <p className="text-sm text-muted-foreground">
                        No SSH keys added yet.
                    </p>
                )}
            </div>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Add SSH Key"
                    description="Add a new SSH public key"
                />

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            placeholder="e.g. My Laptop"
                            required
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="public_key">Public Key</Label>
                        <textarea
                            id="public_key"
                            value={form.data.public_key}
                            onChange={(e) =>
                                form.setData('public_key', e.target.value)
                            }
                            className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[100px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="ssh-ed25519 AAAA..."
                            required
                        />
                        <InputError message={form.errors.public_key} />
                    </div>

                    <div className="flex items-center gap-4">
                        <Button disabled={form.processing}>Add SSH Key</Button>

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
                        <DialogTitle>Delete SSH Key</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete the SSH key "
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

SshKeys.layout = {
    breadcrumbs: [
        {
            title: 'SSH Keys',
            href: '/settings/ssh-keys',
        },
    ],
};
