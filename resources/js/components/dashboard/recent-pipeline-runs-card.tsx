import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { DashboardRecentPipelineRun } from '@/types';

type Props = {
    runs: DashboardRecentPipelineRun[];
};

export function RecentPipelineRunsCard({ runs }: Props) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Recent Pipeline Runs</CardTitle>
            </CardHeader>
            <CardContent>
                {runs.length > 0 ? (
                    <div className="space-y-3">
                        {runs.map((run) => (
                            <Link
                                key={run.id}
                                href={`/pipeline-runs/${run.id}`}
                                className="block rounded-lg border p-3 hover:bg-muted/40"
                            >
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <p className="font-medium">
                                            {run.application ?? 'Unknown app'}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {run.environment ??
                                                'Unknown environment'}{' '}
                                            · {run.trigger_type}
                                        </p>
                                    </div>
                                    <Badge
                                        variant={
                                            run.status === 'succeeded'
                                                ? 'default'
                                                : run.status === 'failed'
                                                  ? 'destructive'
                                                  : 'secondary'
                                        }
                                    >
                                        {run.status}
                                    </Badge>
                                </div>
                            </Link>
                        ))}
                    </div>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        No pipeline runs recorded yet.
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
