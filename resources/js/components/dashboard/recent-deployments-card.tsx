import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { DashboardRecentDeployment } from '@/types';

type Props = {
    deployments: DashboardRecentDeployment[];
};

export function RecentDeploymentsCard({ deployments }: Props) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Recent Deployments</CardTitle>
            </CardHeader>
            <CardContent>
                {deployments.length > 0 ? (
                    <div className="space-y-3">
                        {deployments.map((deployment) => (
                            <Link
                                key={deployment.id}
                                href={`/deployments/${deployment.id}`}
                                className="block rounded-lg border p-3 hover:bg-muted/40"
                            >
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <p className="font-medium">
                                            {deployment.application ??
                                                'Unknown app'}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {deployment.environment ??
                                                'Unknown environment'}
                                        </p>
                                    </div>
                                    <Badge
                                        variant={
                                            deployment.status === 'succeeded'
                                                ? 'default'
                                                : deployment.status === 'failed'
                                                  ? 'destructive'
                                                  : 'secondary'
                                        }
                                    >
                                        {deployment.status}
                                    </Badge>
                                </div>
                            </Link>
                        ))}
                    </div>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        No deployments recorded yet.
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
