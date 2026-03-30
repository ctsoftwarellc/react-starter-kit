import { Head } from '@inertiajs/react';
import { ClusterHealthCard } from '@/components/dashboard/cluster-health-card';
import { RecentDeploymentsCard } from '@/components/dashboard/recent-deployments-card';
import { RecentPipelineRunsCard } from '@/components/dashboard/recent-pipeline-runs-card';
import { ServerStatusCard } from '@/components/dashboard/server-status-card';
import { ServiceOverviewCard } from '@/components/dashboard/service-overview-card';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import type {
    DashboardRecentDeployment,
    DashboardRecentPipelineRun,
    DashboardServiceOverview,
    DashboardStats,
} from '@/types';

type Props = {
    stats: DashboardStats;
    recent_deployments: DashboardRecentDeployment[];
    recent_pipeline_runs: DashboardRecentPipelineRun[];
    service_overview: DashboardServiceOverview;
};

export default function Dashboard({
    stats,
    recent_deployments,
    recent_pipeline_runs,
    service_overview,
}: Props) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
                <div className="grid gap-4 xl:grid-cols-3">
                    <ServerStatusCard counts={stats.server_counts_by_status} />
                    <ClusterHealthCard summary={stats.cluster_health_summary} />
                    <Card>
                        <CardHeader>
                            <CardTitle>Applications</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-semibold">
                                {stats.application_count}
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Applications configured across all projects
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <ServiceOverviewCard overview={service_overview} />

                <div className="grid gap-4 xl:grid-cols-2">
                    <RecentDeploymentsCard deployments={recent_deployments} />
                    <RecentPipelineRunsCard runs={recent_pipeline_runs} />
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
