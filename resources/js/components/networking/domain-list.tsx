import { router, useForm } from '@inertiajs/react';
import { CheckCircle2, Globe, ShieldCheck, Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import type { Domain } from '@/types';

type Props = {
    domains: Domain[];
    environmentId: string;
};

function certificateVariant(
    status?: string | null,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'active':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'failed':
        case 'expired':
            return 'destructive';
        default:
            return 'outline';
    }
}

export function DomainList({ domains, environmentId }: Props) {
    const form = useForm({
        hostname: '',
        is_primary: false,
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();

        form.post(`/environments/${environmentId}/domains`, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    function verifyDomain(domain: Domain) {
        router.post(
            `/domains/${domain.id}/verify`,
            {},
            { preserveScroll: true },
        );
    }

    function removeDomain(domain: Domain) {
        router.delete(`/domains/${domain.id}`, { preserveScroll: true });
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Domains</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                <form
                    onSubmit={submit}
                    className="grid gap-4 rounded-lg border p-4 md:grid-cols-[1fr,auto]"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="domain-hostname">Hostname</Label>
                        <Input
                            id="domain-hostname"
                            value={form.data.hostname}
                            onChange={(event) =>
                                form.setData('hostname', event.target.value)
                            }
                            placeholder="app.example.com"
                        />
                        <InputError message={form.errors.hostname} />
                    </div>
                    <div className="flex flex-col justify-end gap-3 md:items-end">
                        <div className="flex items-center gap-3 rounded-lg border px-3 py-2 text-sm">
                            <Checkbox
                                id="domain-primary"
                                checked={form.data.is_primary}
                                onCheckedChange={(checked) =>
                                    form.setData('is_primary', checked === true)
                                }
                            />
                            <Label htmlFor="domain-primary">
                                Primary domain
                            </Label>
                        </div>
                        <Button disabled={form.processing}>Add Domain</Button>
                    </div>
                </form>

                {domains.length > 0 ? (
                    <div className="space-y-3">
                        {domains.map((domain) => (
                            <div
                                key={domain.id}
                                className="flex flex-col gap-4 rounded-lg border p-4 md:flex-row md:items-center md:justify-between"
                            >
                                <div className="space-y-2">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <div className="flex items-center gap-2 font-medium">
                                            <Globe className="h-4 w-4 text-muted-foreground" />
                                            {domain.hostname}
                                        </div>
                                        {domain.is_primary ? (
                                            <Badge>Primary</Badge>
                                        ) : null}
                                        <Badge
                                            variant={
                                                domain.is_verified
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {domain.is_verified
                                                ? 'Verified'
                                                : 'Pending DNS'}
                                        </Badge>
                                        <Badge
                                            variant={certificateVariant(
                                                domain.certificate?.status,
                                            )}
                                        >
                                            SSL{' '}
                                            {domain.certificate?.status ??
                                                'unknown'}
                                        </Badge>
                                    </div>
                                    <div className="flex flex-wrap gap-4 text-xs text-muted-foreground">
                                        <span>
                                            Verification token:{' '}
                                            {domain.verification_token ??
                                                'Not available'}
                                        </span>
                                        <span>
                                            Expires:{' '}
                                            {domain.certificate?.expires_at
                                                ? new Date(
                                                      domain.certificate
                                                          .expires_at,
                                                  ).toLocaleDateString()
                                                : 'Waiting for certificate'}
                                        </span>
                                    </div>
                                </div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => verifyDomain(domain)}
                                    >
                                        {domain.is_verified ? (
                                            <CheckCircle2 className="mr-2 h-4 w-4" />
                                        ) : (
                                            <ShieldCheck className="mr-2 h-4 w-4" />
                                        )}
                                        Verify
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => removeDomain(domain)}
                                    >
                                        <Trash2 className="mr-2 h-4 w-4" />
                                        Remove
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="rounded-lg border border-dashed p-6 text-sm text-muted-foreground">
                        No domains assigned yet. Add a hostname, point DNS to a
                        web node, then verify it to let Caddy track HTTPS.
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
