import { Head, router, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Cluster, Server } from '@/types';

type ServerWithPivot = Server & {
    pivot: {
        id: string;
        role: string;
        is_active: boolean;
        sort_order: number;
    };
};

type Props = {
    cluster: Omit<Cluster, 'servers'> & {
        servers?: ServerWithPivot[];
    };
    availableServers: Server[];
};

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'active':
            return 'default';
        case 'pending':
        case 'provisioning':
        case 'updating':
        case 'scaling':
            return 'secondary';
        case 'degraded':
        case 'decommissioned':
            return 'destructive';
        default:
            return 'outline';
    }
}

const roles = ['web', 'worker', 'db', 'cache', 'queue', 'bastion'] as const;

export default function ClusterShow({ cluster, availableServers }: Props) {
    const [removeTarget, setRemoveTarget] = useState<Server | null>(null);

    const addForm = useForm({
        server_id: '',
        role: '',
    });

    function handleAddNode(e: React.FormEvent) {
        e.preventDefault();
        addForm.post(`/clusters/${cluster.slug}/nodes`, {
            preserveScroll: true,
            onSuccess: () => addForm.reset(),
        });
    }

    function handleRemoveNode() {
        if (!removeTarget) return;
        router.delete(`/clusters/${cluster.slug}/nodes/${removeTarget.id}`, {
            preserveScroll: true,
            onSuccess: () => setRemoveTarget(null),
        });
    }

    return (
        <>
            <Head title={cluster.name} />

            <div className="px-4 py-6">
                <div className="mb-8 flex items-center gap-4">
                    <Heading title={cluster.name} description={`Slug: ${cluster.slug}`} />
                    <Badge variant={statusVariant(cluster.status)}>
                        {cluster.status}
                    </Badge>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Nodes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {cluster.servers && cluster.servers.length > 0 ? (
                            <div className="rounded-md border">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50">
                                            <th className="px-4 py-2 text-left font-medium">Server</th>
                                            <th className="px-4 py-2 text-left font-medium">IP</th>
                                            <th className="px-4 py-2 text-left font-medium">Role</th>
                                            <th className="px-4 py-2 text-left font-medium">Status</th>
                                            <th className="px-4 py-2 text-right font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {cluster.servers.map((server) => (
                                            <tr key={server.id} className="border-b last:border-0">
                                                <td className="px-4 py-2 font-medium">{server.name}</td>
                                                <td className="px-4 py-2 font-mono text-xs text-muted-foreground">
                                                    {server.public_ip}
                                                </td>
                                                <td className="px-4 py-2">
                                                    <Badge variant="outline">{server.pivot.role}</Badge>
                                                </td>
                                                <td className="px-4 py-2">
                                                    <Badge variant={statusVariant(server.status)}>
                                                        {server.status}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-2 text-right">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => setRemoveTarget(server)}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                No nodes in this cluster yet.
                            </p>
                        )}
                    </CardContent>
                </Card>

                {availableServers.length > 0 && (
                    <Card className="mt-6">
                        <CardHeader>
                            <CardTitle>Add Node</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleAddNode} className="flex items-end gap-4">
                                <div className="grid flex-1 gap-2">
                                    <Label htmlFor="server_id">Server</Label>
                                    <Select
                                        value={addForm.data.server_id}
                                        onValueChange={(value) => addForm.setData('server_id', value)}
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="Select a server" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableServers.map((server) => (
                                                <SelectItem key={server.id} value={server.id}>
                                                    {server.name} ({server.public_ip})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={addForm.errors.server_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="role">Role</Label>
                                    <Select
                                        value={addForm.data.role}
                                        onValueChange={(value) => addForm.setData('role', value)}
                                    >
                                        <SelectTrigger className="w-[180px]">
                                            <SelectValue placeholder="Select a role" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {roles.map((role) => (
                                                <SelectItem key={role} value={role}>
                                                    {role}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={addForm.errors.role} />
                                </div>

                                <Button disabled={addForm.processing}>Add Node</Button>
                            </form>
                        </CardContent>
                    </Card>
                )}
            </div>

            <Dialog
                open={removeTarget !== null}
                onOpenChange={(open) => !open && setRemoveTarget(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Remove Node</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to remove "{removeTarget?.name}" from this cluster?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setRemoveTarget(null)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={handleRemoveNode}>
                            Remove
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

ClusterShow.layout = {
    breadcrumbs: [
        {
            title: 'Clusters',
            href: '/clusters',
        },
        {
            title: 'Cluster',
            href: '/clusters',
        },
    ],
};
