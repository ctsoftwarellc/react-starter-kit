import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { PaginatedData, Server } from '@/types';

type Props = {
    servers: PaginatedData<Server>;
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

export default function ServerIndex({ servers }: Props) {
    return (
        <>
            <Head title="Servers" />

            <div className="px-4 py-6">
                <div className="mb-8 flex items-center justify-between">
                    <Heading
                        title="Servers"
                        description="Manage your infrastructure servers"
                    />
                    <Button asChild>
                        <Link href="/servers/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Register Server
                        </Link>
                    </Button>
                </div>

                {servers.data.length > 0 ? (
                    <div className="rounded-md border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-4 py-2 text-left font-medium">Name</th>
                                    <th className="px-4 py-2 text-left font-medium">IP</th>
                                    <th className="px-4 py-2 text-left font-medium">Status</th>
                                    <th className="px-4 py-2 text-left font-medium">Provider</th>
                                    <th className="px-4 py-2 text-left font-medium">Clusters</th>
                                    <th className="px-4 py-2 text-left font-medium">Last Heartbeat</th>
                                </tr>
                            </thead>
                            <tbody>
                                {servers.data.map((server) => (
                                    <tr key={server.id} className="border-b last:border-0">
                                        <td className="px-4 py-2">
                                            <Link
                                                href={`/servers/${server.id}`}
                                                className="font-medium text-primary hover:underline"
                                            >
                                                {server.name}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-2 font-mono text-xs text-muted-foreground">
                                            {server.public_ip}
                                        </td>
                                        <td className="px-4 py-2">
                                            <Badge variant={statusVariant(server.status)}>
                                                {server.status}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground">
                                            {server.provider?.name ?? '-'}
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground">
                                            {server.clusters?.length ?? 0}
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground">
                                            {server.last_heartbeat_at
                                                ? new Date(server.last_heartbeat_at).toLocaleString()
                                                : 'Never'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <p className="mb-4 text-muted-foreground">
                                No servers registered yet. Register your first server to get started.
                            </p>
                            <Button asChild>
                                <Link href="/servers/create">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Register Server
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                )}

                {servers.meta.last_page > 1 && (
                    <div className="mt-6 flex justify-center gap-2">
                        {servers.links.prev && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={servers.links.prev}>Previous</Link>
                            </Button>
                        )}
                        <span className="flex items-center px-3 text-sm text-muted-foreground">
                            Page {servers.meta.current_page} of {servers.meta.last_page}
                        </span>
                        {servers.links.next && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={servers.links.next}>Next</Link>
                            </Button>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

ServerIndex.layout = {
    breadcrumbs: [
        {
            title: 'Servers',
            href: '/servers',
        },
    ],
};
