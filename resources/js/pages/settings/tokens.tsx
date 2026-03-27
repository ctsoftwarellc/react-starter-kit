import { Transition } from '@headlessui/react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Copy, Trash2 } from 'lucide-react';
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
import type { PersonalAccessToken } from '@/types';

type Props = {
    tokens: PersonalAccessToken[];
};

export default function Tokens({ tokens }: Props) {
    const { flash } = usePage().props as unknown as {
        flash: { plainTextToken?: string };
    };
    const plainTextToken = flash?.plainTextToken ?? null;

    const form = useForm({
        name: '',
    });

    const [deleteTarget, setDeleteTarget] = useState<PersonalAccessToken | null>(
        null,
    );
    const [copied, setCopied] = useState(false);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.post('/settings/tokens', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    function handleDelete() {
        if (!deleteTarget) return;
        router.delete(`/settings/tokens/${deleteTarget.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeleteTarget(null),
        });
    }

    function copyToken() {
        if (plainTextToken) {
            navigator.clipboard.writeText(plainTextToken);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }
    }

    return (
        <>
            <Head title="API Tokens" />

            <h1 className="sr-only">API Tokens</h1>

            {plainTextToken && (
                <div className="rounded-md border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950">
                    <p className="mb-2 text-sm font-medium text-green-800 dark:text-green-200">
                        Your new API token has been created. Please copy it now
                        -- you will not be able to see it again.
                    </p>
                    <div className="flex items-center gap-2">
                        <code className="flex-1 rounded bg-white px-3 py-2 font-mono text-xs break-all dark:bg-gray-900">
                            {plainTextToken}
                        </code>
                        <Button variant="outline" size="sm" onClick={copyToken}>
                            <Copy className="h-4 w-4" />
                            {copied ? 'Copied' : 'Copy'}
                        </Button>
                    </div>
                </div>
            )}

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="API Tokens"
                    description="Manage personal access tokens for API authentication"
                />

                {tokens.length > 0 && (
                    <div className="rounded-md border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-4 py-2 text-left font-medium">
                                        Name
                                    </th>
                                    <th className="px-4 py-2 text-left font-medium">
                                        Last Used
                                    </th>
                                    <th className="px-4 py-2 text-left font-medium">
                                        Expires
                                    </th>
                                    <th className="px-4 py-2 text-right font-medium">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {tokens.map((token) => (
                                    <tr
                                        key={token.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-4 py-2 font-medium">
                                            {token.name}
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground">
                                            {token.last_used_at
                                                ? new Date(
                                                      token.last_used_at,
                                                  ).toLocaleDateString()
                                                : 'Never'}
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground">
                                            {token.expires_at
                                                ? new Date(
                                                      token.expires_at,
                                                  ).toLocaleDateString()
                                                : 'Never'}
                                        </td>
                                        <td className="px-4 py-2 text-right">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    setDeleteTarget(token)
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

                {tokens.length === 0 && (
                    <p className="text-sm text-muted-foreground">
                        No API tokens created yet.
                    </p>
                )}
            </div>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Create Token"
                    description="Create a new personal access token"
                />

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="token-name">Token Name</Label>
                        <Input
                            id="token-name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            placeholder="e.g. CI/CD Pipeline"
                            required
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="flex items-center gap-4">
                        <Button disabled={form.processing}>Create Token</Button>

                        <Transition
                            show={form.recentlySuccessful}
                            enter="transition ease-in-out"
                            enterFrom="opacity-0"
                            leave="transition ease-in-out"
                            leaveTo="opacity-0"
                        >
                            <p className="text-sm text-neutral-600">Created</p>
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
                        <DialogTitle>Revoke Token</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to revoke the token "
                            {deleteTarget?.name}"? Any applications using this
                            token will no longer be able to authenticate.
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
                            Revoke
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

Tokens.layout = {
    breadcrumbs: [
        {
            title: 'API Tokens',
            href: '/settings/tokens',
        },
    ],
};
