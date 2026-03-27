import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { AuditLog, PaginatedData } from '@/types';

type Props = {
    logs: PaginatedData<AuditLog>;
};

function formatAuditableType(type: string): string {
    const parts = type.split('\\');
    return parts[parts.length - 1] ?? type;
}

export default function ActivityIndex({ logs }: Props) {
    return (
        <>
            <Head title="Activity" />

            <div className="px-4 py-6">
                <Heading
                    title="Activity"
                    description="View audit log of all actions"
                />

                {logs.data.length > 0 ? (
                    <div className="space-y-3">
                        {logs.data.map((log) => (
                            <div
                                key={log.id}
                                className="flex items-center justify-between rounded-md border px-4 py-3"
                            >
                                <div className="flex items-center gap-3">
                                    <Badge variant="secondary">
                                        {log.action}
                                    </Badge>
                                    <span className="text-sm">
                                        {formatAuditableType(
                                            log.auditable_type,
                                        )}
                                    </span>
                                    {log.auditable_id && (
                                        <span className="font-mono text-xs text-muted-foreground">
                                            {log.auditable_id.substring(0, 8)}
                                        </span>
                                    )}
                                </div>
                                <time className="text-sm text-muted-foreground">
                                    {new Date(
                                        log.created_at,
                                    ).toLocaleString()}
                                </time>
                            </div>
                        ))}
                    </div>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        No activity recorded yet.
                    </p>
                )}

                {logs.meta.last_page > 1 && (
                    <div className="mt-6 flex justify-center gap-2">
                        {logs.links.prev && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={logs.links.prev}>Previous</Link>
                            </Button>
                        )}
                        <span className="flex items-center px-3 text-sm text-muted-foreground">
                            Page {logs.meta.current_page} of{' '}
                            {logs.meta.last_page}
                        </span>
                        {logs.links.next && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={logs.links.next}>Next</Link>
                            </Button>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

ActivityIndex.layout = {
    breadcrumbs: [
        {
            title: 'Activity',
            href: '/activity',
        },
    ],
};
