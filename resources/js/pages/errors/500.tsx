import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export default function Error500() {
    return (
        <>
            <Head title="Server error" />
            <div className="flex min-h-screen items-center justify-center px-6">
                <div className="max-w-md space-y-4 text-center">
                    <p className="text-sm font-medium text-muted-foreground">
                        500
                    </p>
                    <h1 className="text-3xl font-semibold">
                        Something went wrong
                    </h1>
                    <p className="text-muted-foreground">
                        Helm hit an unexpected error while loading this page.
                    </p>
                    <Button asChild>
                        <Link href="/dashboard">Back to dashboard</Link>
                    </Button>
                </div>
            </div>
        </>
    );
}
