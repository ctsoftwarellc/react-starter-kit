import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Props = {
    counts: Record<string, number>;
};

export function ServerStatusCard({ counts }: Props) {
    const total = Object.values(counts).reduce((sum, value) => sum + value, 0);

    return (
        <Card>
            <CardHeader>
                <CardTitle>Servers</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                <div>
                    <div className="text-3xl font-semibold">{total}</div>
                    <p className="text-sm text-muted-foreground">
                        Total registered nodes
                    </p>
                </div>
                <div className="grid gap-2 text-sm sm:grid-cols-2">
                    {Object.entries(counts).length > 0 ? (
                        Object.entries(counts).map(([status, count]) => (
                            <div
                                key={status}
                                className="flex items-center justify-between rounded-lg border px-3 py-2"
                            >
                                <span className="text-muted-foreground capitalize">
                                    {status}
                                </span>
                                <span className="font-medium">{count}</span>
                            </div>
                        ))
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            No servers registered yet.
                        </p>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
