import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Props = {
    summary: {
        total: number;
        healthy: number;
        degraded: number;
        maintenance: number;
        other: number;
    };
};

export function ClusterHealthCard({ summary }: Props) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Clusters</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                <div>
                    <div className="text-3xl font-semibold">
                        {summary.total}
                    </div>
                    <p className="text-sm text-muted-foreground">
                        Current cluster footprint
                    </p>
                </div>
                <div className="grid gap-2 text-sm sm:grid-cols-2">
                    {[
                        ['Healthy', summary.healthy],
                        ['Degraded', summary.degraded],
                        ['Maintenance', summary.maintenance],
                        ['Other', summary.other],
                    ].map(([label, count]) => (
                        <div
                            key={label}
                            className="flex items-center justify-between rounded-lg border px-3 py-2"
                        >
                            <span className="text-muted-foreground">
                                {label}
                            </span>
                            <span className="font-medium">{count}</span>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}
