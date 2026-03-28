import { Head, router } from '@inertiajs/react';
import { Activity, Play, Power, ShieldOff, Square } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Server } from '@/types';

type Props = {
    server: Server & {
        agent_commands?: Array<{
            id: string;
            type: string;
            status: string;
            created_at: string;
            completed_at: string | null;
        }>;
    };
};

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'active':
            return 'default';
        case 'pending':
        case 'provisioning':
        case 'bootstrapping':
            return 'secondary';
        case 'failed':
        case 'decommissioned':
            return 'destructive';
        default:
            return 'outline';
    }
}

export default function ServerShow({ server }: Props) {
    function handleBootstrap() {
        router.post(`/servers/${server.id}/bootstrap`);
    }

    function handleDrain() {
        router.post(`/servers/${server.id}/drain`, {}, {
            preserveScroll: true,
        });
    }

    function handleCordon() {
        router.post(`/servers/${server.id}/cordon`, {}, {
            preserveScroll: true,
        });
    }

    function handleActivate() {
        router.post(`/servers/${server.id}/activate`, {}, {
            preserveScroll: true,
        });
    }

    const canBootstrap = ['pending', 'failed'].includes(server.status);
    const canDrain = server.status === 'active';
    const canCordon = server.status === 'active';
    const canActivate = ['draining', 'cordoned', 'maintenance'].includes(server.status);

    const isAgentOnline = server.last_heartbeat_at
        ? new Date(server.last_heartbeat_at).getTime() > Date.now() - 5 * 60 * 1000
        : false;

    return (
        <>
            <Head title={server.name} />

            <div className="px-4 py-6">
                <div className="mb-8 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Heading title={server.name} description={server.hostname} />
                        <Badge variant={statusVariant(server.status)}>
                            {server.status}
                        </Badge>
                    </div>
                    <div className="flex gap-2">
                        {canBootstrap && (
                            <Button onClick={handleBootstrap}>
                                <Play className="mr-2 h-4 w-4" />
                                Bootstrap
                            </Button>
                        )}
                        {canDrain && (
                            <Button variant="outline" onClick={handleDrain}>
                                <Square className="mr-2 h-4 w-4" />
                                Drain
                            </Button>
                        )}
                        {canCordon && (
                            <Button variant="outline" onClick={handleCordon}>
                                <ShieldOff className="mr-2 h-4 w-4" />
                                Cordon
                            </Button>
                        )}
                        {canActivate && (
                            <Button variant="outline" onClick={handleActivate}>
                                <Power className="mr-2 h-4 w-4" />
                                Activate
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Server Details</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="space-y-3 text-sm">
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Public IP</dt>
                                    <dd className="font-mono">{server.public_ip}</dd>
                                </div>
                                {server.private_ip && (
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">Private IP</dt>
                                        <dd className="font-mono">{server.private_ip}</dd>
                                    </div>
                                )}
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">SSH</dt>
                                    <dd className="font-mono">{server.ssh_user}@{server.public_ip}:{server.ssh_port}</dd>
                                </div>
                                {server.os && (
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">OS</dt>
                                        <dd>{server.os}</dd>
                                    </div>
                                )}
                                {server.region && (
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">Region</dt>
                                        <dd>{server.region}</dd>
                                    </div>
                                )}
                                {server.provider && (
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">Provider</dt>
                                        <dd>{server.provider.name}</dd>
                                    </div>
                                )}
                                {server.cpu_cores && (
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">CPU</dt>
                                        <dd>{server.cpu_cores} cores</dd>
                                    </div>
                                )}
                                {server.memory_mb && (
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">Memory</dt>
                                        <dd>{server.memory_mb} MB</dd>
                                    </div>
                                )}
                                {server.disk_gb && (
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">Disk</dt>
                                        <dd>{server.disk_gb} GB</dd>
                                    </div>
                                )}
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Agent Status</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-3">
                                <Activity className={`h-5 w-5 ${isAgentOnline ? 'text-green-500' : 'text-muted-foreground'}`} />
                                <div>
                                    <p className="text-sm font-medium">
                                        {isAgentOnline ? 'Online' : 'Offline'}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {server.last_heartbeat_at
                                            ? `Last seen ${new Date(server.last_heartbeat_at).toLocaleString()}`
                                            : 'No heartbeat received'}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {server.clusters && server.clusters.length > 0 && (
                    <div className="mt-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Cluster Membership</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="rounded-md border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-4 py-2 text-left font-medium">Cluster</th>
                                                <th className="px-4 py-2 text-left font-medium">Role</th>
                                                <th className="px-4 py-2 text-left font-medium">Active</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {server.clusters.map((cluster) => (
                                                <tr key={cluster.id} className="border-b last:border-0">
                                                    <td className="px-4 py-2 font-medium">{cluster.name}</td>
                                                    <td className="px-4 py-2">
                                                        <Badge variant="outline">
                                                            {(cluster as unknown as { pivot: { role: string } }).pivot?.role ?? '-'}
                                                        </Badge>
                                                    </td>
                                                    <td className="px-4 py-2 text-muted-foreground">
                                                        {(cluster as unknown as { pivot: { is_active: boolean } }).pivot?.is_active ? 'Yes' : 'No'}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {server.agent_commands && server.agent_commands.length > 0 && (
                    <div className="mt-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Recent Commands</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="rounded-md border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-4 py-2 text-left font-medium">Type</th>
                                                <th className="px-4 py-2 text-left font-medium">Status</th>
                                                <th className="px-4 py-2 text-left font-medium">Created</th>
                                                <th className="px-4 py-2 text-left font-medium">Completed</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {server.agent_commands.map((command) => (
                                                <tr key={command.id} className="border-b last:border-0">
                                                    <td className="px-4 py-2">{command.type}</td>
                                                    <td className="px-4 py-2">
                                                        <Badge variant={command.status === 'completed' ? 'default' : command.status === 'failed' ? 'destructive' : 'secondary'}>
                                                            {command.status}
                                                        </Badge>
                                                    </td>
                                                    <td className="px-4 py-2 text-muted-foreground">
                                                        {new Date(command.created_at).toLocaleString()}
                                                    </td>
                                                    <td className="px-4 py-2 text-muted-foreground">
                                                        {command.completed_at
                                                            ? new Date(command.completed_at).toLocaleString()
                                                            : '-'}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}
            </div>
        </>
    );
}

ServerShow.layout = {
    breadcrumbs: [
        {
            title: 'Servers',
            href: '/servers',
        },
        {
            title: 'Server',
            href: '/servers',
        },
    ],
};
