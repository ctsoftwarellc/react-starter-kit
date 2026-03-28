import { Head, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Provider } from '@/types';

type Props = {
    providers: Provider[];
};

export default function ServerCreate({ providers }: Props) {
    const form = useForm({
        name: '',
        hostname: '',
        public_ip: '',
        private_ip: '',
        ssh_port: '22',
        ssh_user: 'root',
        provider_id: '',
        os: '',
        region: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.post('/servers');
    }

    return (
        <>
            <Head title="Register Server" />

            <div className="mx-auto max-w-xl px-4 py-6">
                <Heading
                    title="Register Server"
                    description="Register a new server in your infrastructure"
                />

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Server Name</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            placeholder="e.g. web-1"
                            required
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="hostname">Hostname</Label>
                        <Input
                            id="hostname"
                            value={form.data.hostname}
                            onChange={(e) => form.setData('hostname', e.target.value)}
                            placeholder="e.g. web-1.example.com"
                            required
                        />
                        <InputError message={form.errors.hostname} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="public_ip">Public IP</Label>
                            <Input
                                id="public_ip"
                                value={form.data.public_ip}
                                onChange={(e) => form.setData('public_ip', e.target.value)}
                                placeholder="e.g. 1.2.3.4"
                                required
                            />
                            <InputError message={form.errors.public_ip} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="private_ip">
                                Private IP{' '}
                                <span className="text-muted-foreground">(optional)</span>
                            </Label>
                            <Input
                                id="private_ip"
                                value={form.data.private_ip}
                                onChange={(e) => form.setData('private_ip', e.target.value)}
                                placeholder="e.g. 10.0.0.1"
                            />
                            <InputError message={form.errors.private_ip} />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="ssh_port">SSH Port</Label>
                            <Input
                                id="ssh_port"
                                type="number"
                                value={form.data.ssh_port}
                                onChange={(e) => form.setData('ssh_port', e.target.value)}
                                min={1}
                                max={65535}
                            />
                            <InputError message={form.errors.ssh_port} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="ssh_user">SSH User</Label>
                            <Input
                                id="ssh_user"
                                value={form.data.ssh_user}
                                onChange={(e) => form.setData('ssh_user', e.target.value)}
                                placeholder="root"
                            />
                            <InputError message={form.errors.ssh_user} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="provider_id">
                            Provider{' '}
                            <span className="text-muted-foreground">(optional)</span>
                        </Label>
                        <Select
                            value={form.data.provider_id}
                            onValueChange={(value) => form.setData('provider_id', value)}
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Select a provider" />
                            </SelectTrigger>
                            <SelectContent>
                                {providers.map((provider) => (
                                    <SelectItem key={provider.id} value={provider.id}>
                                        {provider.name} ({provider.type})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.provider_id} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="os">
                                OS{' '}
                                <span className="text-muted-foreground">(optional)</span>
                            </Label>
                            <Input
                                id="os"
                                value={form.data.os}
                                onChange={(e) => form.setData('os', e.target.value)}
                                placeholder="e.g. Ubuntu 24.04"
                            />
                            <InputError message={form.errors.os} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="region">
                                Region{' '}
                                <span className="text-muted-foreground">(optional)</span>
                            </Label>
                            <Input
                                id="region"
                                value={form.data.region}
                                onChange={(e) => form.setData('region', e.target.value)}
                                placeholder="e.g. us-east-1"
                            />
                            <InputError message={form.errors.region} />
                        </div>
                    </div>

                    <div className="flex items-center gap-4">
                        <Button disabled={form.processing}>Register Server</Button>
                    </div>
                </form>
            </div>
        </>
    );
}

ServerCreate.layout = {
    breadcrumbs: [
        {
            title: 'Servers',
            href: '/servers',
        },
        {
            title: 'Register',
            href: '/servers/create',
        },
    ],
};
