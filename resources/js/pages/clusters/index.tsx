import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { Cluster, PaginatedData } from '@/types';

type Props = {
    clusters: PaginatedData<Cluster>;
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

export default function ClusterIndex({ clusters }: Props) {
    return (
        <>
            <Head title="Clusters" />

            <div className="px-4 py-6">
                <div className="mb-8 flex items-center justify-between">
                    <Heading
                        title="Clusters"
                        description="Manage your server clusters"
                    />
                    <Button asChild>
                        <Link href="/clusters/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Create Cluster
                        </Link>
                    </Button>
                </div>

                {clusters.data.length > 0 ? (
                    <div className="rounded-md border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-4 py-2 text-left font-medium">Name</th>
                                    <th className="px-4 py-2 text-left font-medium">Status</th>
                                    <th className="px-4 py-2 text-left font-medium">Nodes</th>
                                    <th className="px-4 py-2 text-left font-medium">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                {clusters.data.map((cluster) => (
                                    <tr key={cluster.id} className="border-b last:border-0">
                                        <td className="px-4 py-2">
                                            <Link
                                                href={`/clusters/${cluster.slug}`}
                                                className="font-medium text-primary hover:underline"
                                            >
                                                {cluster.name}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-2">
                                            <Badge variant={statusVariant(cluster.status)}>
                                                {cluster.status}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground">
                                            {cluster.servers?.length ?? 0} nodes
                                            {cluster.node_counts && Object.keys(cluster.node_counts).length > 0 && (
                                                <span className="ml-2 text-xs">
                                                    ({Object.entries(cluster.node_counts).map(([role, count]) => `${count} ${role}`).join(', ')})
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground">
                                            {new Date(cluster.created_at).toLocaleDateString()}
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
                                No clusters yet. Create your first cluster to organize your servers.
                            </p>
                            <Button asChild>
                                <Link href="/clusters/create">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Create Cluster
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                )}

                {clusters.meta.last_page > 1 && (
                    <div className="mt-6 flex justify-center gap-2">
                        {clusters.links.prev && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={clusters.links.prev}>Previous</Link>
                            </Button>
                        )}
                        <span className="flex items-center px-3 text-sm text-muted-foreground">
                            Page {clusters.meta.current_page} of {clusters.meta.last_page}
                        </span>
                        {clusters.links.next && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={clusters.links.next}>Next</Link>
                            </Button>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

ClusterIndex.layout = {
    breadcrumbs: [
        {
            title: 'Clusters',
            href: '/clusters',
        },
    ],
};
