import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { DashboardServiceOverview } from '@/types';

type Props = {
    overview: DashboardServiceOverview;
};

export function ServiceOverviewCard({ overview }: Props) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Services</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-3 text-sm sm:grid-cols-3">
                <div className="rounded-lg border px-3 py-4">
                    <div className="text-2xl font-semibold">
                        {overview.database_instances}
                    </div>
                    <p className="text-muted-foreground">Database instances</p>
                </div>
                <div className="rounded-lg border px-3 py-4">
                    <div className="text-2xl font-semibold">
                        {overview.cache_instances}
                    </div>
                    <p className="text-muted-foreground">Cache instances</p>
                </div>
                <div className="rounded-lg border px-3 py-4">
                    <div className="text-2xl font-semibold">
                        {overview.backups?.count ?? 0}
                    </div>
                    <p className="text-muted-foreground">
                        Backups{' '}
                        {overview.backups?.latest_status
                            ? `· ${overview.backups.latest_status}`
                            : ''}
                    </p>
                </div>
            </CardContent>
        </Card>
    );
}
