import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export default function Error503() {
    return (
        <>
            <Head title="Service unavailable" />
            <div className="flex min-h-screen items-center justify-center px-6">
                <div className="max-w-md space-y-4 text-center">
                    <p className="text-sm font-medium text-muted-foreground">
                        503
                    </p>
                    <h1 className="text-3xl font-semibold">
                        Service unavailable
                    </h1>
                    <p className="text-muted-foreground">
                        Helm is temporarily unavailable. Try again in a moment.
                    </p>
                    <Button asChild>
                        <Link href="/dashboard">Back to dashboard</Link>
                    </Button>
                </div>
            </div>
        </>
    );
}
