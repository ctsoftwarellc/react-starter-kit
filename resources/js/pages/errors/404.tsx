import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export default function Error404() {
    return (
        <>
            <Head title="Page not found" />
            <div className="flex min-h-screen items-center justify-center px-6">
                <div className="max-w-md space-y-4 text-center">
                    <p className="text-sm font-medium text-muted-foreground">
                        404
                    </p>
                    <h1 className="text-3xl font-semibold">Page not found</h1>
                    <p className="text-muted-foreground">
                        The page you requested does not exist or was moved.
                    </p>
                    <Button asChild>
                        <Link href="/dashboard">Back to dashboard</Link>
                    </Button>
                </div>
            </div>
        </>
    );
}
