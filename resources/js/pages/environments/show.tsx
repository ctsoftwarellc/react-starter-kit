import { Head, router, useForm } from '@inertiajs/react';
import { Eye, Pencil, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    Application,
    Cluster,
    Environment,
    EnvironmentVariable,
    ProcessDefinition,
    Secret,
} from '@/types';

type Props = {
    environment: Environment & {
        variables?: EnvironmentVariable[];
        secrets?: Secret[];
        process_definitions?: ProcessDefinition[];
        cluster?: Cluster;
    };
    application: Application;
    clusters: Cluster[];
};

async function revealSecret(
    environmentId: string,
    secretId: string,
): Promise<string> {
    const response = await fetch(
        `/environments/${environmentId}/secrets/${secretId}/reveal`,
        {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        },
    );

    if (!response.ok) {
        throw new Error('Unable to reveal secret');
    }

    const payload = (await response.json()) as { value: string };

    return payload.value;
}

export default function EnvironmentShow({
    environment,
    application,
    clusters,
}: Props) {
    const [editingVariableId, setEditingVariableId] = useState<string | null>(
        null,
    );
    const [editingSecretId, setEditingSecretId] = useState<string | null>(null);
    const [editingProcessId, setEditingProcessId] = useState<string | null>(
        null,
    );
    const [revealedValues, setRevealedValues] = useState<
        Record<string, string>
    >({});
    const [revealingId, setRevealingId] = useState<string | null>(null);

    const settingsForm = useForm({
        cluster_id: environment.cluster_id,
        name: environment.name,
        type: environment.type,
        branch: environment.branch ?? '',
        is_auto_deploy: environment.is_auto_deploy,
    });

    const variableForm = useForm({
        key: '',
        value: '',
        is_build_arg: false,
    });

    const secretForm = useForm({
        key: '',
        value: '',
    });

    const processForm = useForm({
        type: 'web',
        command: '',
        instances: '1',
    });

    const variables = useMemo(
        () => environment.variables ?? [],
        [environment.variables],
    );
    const secrets = useMemo(
        () => environment.secrets ?? [],
        [environment.secrets],
    );
    const processes = useMemo(
        () => environment.process_definitions ?? [],
        [environment.process_definitions],
    );

    function saveSettings(event: React.FormEvent) {
        event.preventDefault();
        settingsForm.put(`/environments/${environment.id}`);
    }

    function submitVariable(event: React.FormEvent) {
        event.preventDefault();

        const url = editingVariableId
            ? `/environments/${environment.id}/variables/${editingVariableId}`
            : `/environments/${environment.id}/variables`;

        const options = {
            onSuccess: () => {
                setEditingVariableId(null);
                variableForm.reset();
            },
        };

        if (editingVariableId) {
            variableForm.put(url, options);
        } else {
            variableForm.post(url, options);
        }
    }

    function startEditVariable(variable: EnvironmentVariable) {
        setEditingVariableId(variable.id);
        variableForm.setData({
            key: variable.key,
            value: variable.value,
            is_build_arg: variable.is_build_arg,
        });
    }

    function deleteVariable(variable: EnvironmentVariable) {
        router.delete(
            `/environments/${environment.id}/variables/${variable.id}`,
        );
    }

    function submitSecret(event: React.FormEvent) {
        event.preventDefault();

        const url = editingSecretId
            ? `/environments/${environment.id}/secrets/${editingSecretId}`
            : `/environments/${environment.id}/secrets`;

        const options = {
            onSuccess: () => {
                setEditingSecretId(null);
                secretForm.reset();
            },
        };

        if (editingSecretId) {
            secretForm.put(url, options);
        } else {
            secretForm.post(url, options);
        }
    }

    function startEditSecret(secret: Secret) {
        setEditingSecretId(secret.id);
        secretForm.setData({
            key: secret.key,
            value: '',
        });
    }

    function deleteSecret(secret: Secret) {
        router.delete(`/environments/${environment.id}/secrets/${secret.id}`);
    }

    async function handleRevealSecret(secret: Secret) {
        setRevealingId(secret.id);

        try {
            const value = await revealSecret(environment.id, secret.id);
            setRevealedValues((current) => ({
                ...current,
                [secret.id]: value,
            }));
        } finally {
            setRevealingId(null);
        }
    }

    function submitProcess(event: React.FormEvent) {
        event.preventDefault();

        const url = editingProcessId
            ? `/environments/${environment.id}/processes/${editingProcessId}`
            : `/environments/${environment.id}/processes`;

        const options = {
            onSuccess: () => {
                setEditingProcessId(null);
                processForm.reset();
                processForm.setData('type', 'web');
                processForm.setData('instances', '1');
            },
        };

        if (editingProcessId) {
            processForm.put(url, options);
        } else {
            processForm.post(url, options);
        }
    }

    function startEditProcess(processDefinition: ProcessDefinition) {
        setEditingProcessId(processDefinition.id);
        processForm.setData({
            type: processDefinition.type,
            command: processDefinition.command,
            instances: String(processDefinition.instances),
        });
    }

    function deleteProcess(processDefinition: ProcessDefinition) {
        router.delete(
            `/environments/${environment.id}/processes/${processDefinition.id}`,
        );
    }

    function deleteEnvironment() {
        router.delete(`/environments/${environment.id}`);
    }

    return (
        <>
            <Head title={`${application.name} - ${environment.name}`} />

            <div className="space-y-6 px-4 py-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div className="mb-3 flex items-center gap-3">
                            <Heading
                                title={environment.name}
                                description={`${application.name} environment`}
                            />
                            <Badge variant="outline">{environment.type}</Badge>
                        </div>
                        <div className="flex flex-wrap gap-2 text-sm text-muted-foreground">
                            <span>
                                Cluster:{' '}
                                {environment.cluster?.name ?? 'Unassigned'}
                            </span>
                            <span>-</span>
                            <span>Branch: {environment.branch ?? '-'}</span>
                            <span>-</span>
                            <span>
                                Auto Deploy:{' '}
                                {environment.is_auto_deploy
                                    ? 'Enabled'
                                    : 'Disabled'}
                            </span>
                        </div>
                    </div>

                    <Button variant="destructive" onClick={deleteEnvironment}>
                        <Trash2 className="mr-2 h-4 w-4" />
                        Delete Environment
                    </Button>
                </div>

                <div className="grid gap-6 xl:grid-cols-[0.95fr,1.05fr]">
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Environment Settings</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form
                                    onSubmit={saveSettings}
                                    className="space-y-4"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="environment-settings-name">
                                            Name
                                        </Label>
                                        <Input
                                            id="environment-settings-name"
                                            value={settingsForm.data.name}
                                            onChange={(event) =>
                                                settingsForm.setData(
                                                    'name',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={settingsForm.errors.name}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="environment-settings-cluster">
                                            Cluster
                                        </Label>
                                        <Select
                                            value={settingsForm.data.cluster_id}
                                            onValueChange={(value) =>
                                                settingsForm.setData(
                                                    'cluster_id',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select cluster" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {clusters.map((cluster) => (
                                                    <SelectItem
                                                        key={cluster.id}
                                                        value={cluster.id}
                                                    >
                                                        {cluster.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={
                                                settingsForm.errors.cluster_id
                                            }
                                        />
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="environment-settings-type">
                                                Type
                                            </Label>
                                            <Select
                                                value={settingsForm.data.type}
                                                onValueChange={(value) =>
                                                    settingsForm.setData(
                                                        'type',
                                                        value as
                                                            | 'production'
                                                            | 'staging'
                                                            | 'preview',
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Select type" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="production">
                                                        Production
                                                    </SelectItem>
                                                    <SelectItem value="staging">
                                                        Staging
                                                    </SelectItem>
                                                    <SelectItem value="preview">
                                                        Preview
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    settingsForm.errors.type
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="environment-settings-branch">
                                                Branch
                                            </Label>
                                            <Input
                                                id="environment-settings-branch"
                                                value={settingsForm.data.branch}
                                                onChange={(event) =>
                                                    settingsForm.setData(
                                                        'branch',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    settingsForm.errors.branch
                                                }
                                            />
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-3">
                                        <Button
                                            disabled={settingsForm.processing}
                                        >
                                            Save Settings
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Environment Variables</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <form
                                    onSubmit={submitVariable}
                                    className="grid gap-4 md:grid-cols-[1fr,1fr,auto]"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="variable-key">
                                            Key
                                        </Label>
                                        <Input
                                            id="variable-key"
                                            value={variableForm.data.key}
                                            onChange={(event) =>
                                                variableForm.setData(
                                                    'key',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={variableForm.errors.key}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="variable-value">
                                            Value
                                        </Label>
                                        <Input
                                            id="variable-value"
                                            value={variableForm.data.value}
                                            onChange={(event) =>
                                                variableForm.setData(
                                                    'value',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={variableForm.errors.value}
                                        />
                                    </div>
                                    <div className="flex items-end gap-2">
                                        <Button
                                            disabled={variableForm.processing}
                                        >
                                            {editingVariableId
                                                ? 'Update'
                                                : 'Add'}
                                        </Button>
                                        {editingVariableId && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() => {
                                                    setEditingVariableId(null);
                                                    variableForm.reset();
                                                }}
                                            >
                                                Cancel
                                            </Button>
                                        )}
                                    </div>
                                </form>

                                <div className="rounded-lg border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Key
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Value
                                                </th>
                                                <th className="px-4 py-2 text-right font-medium">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {variables.length > 0 ? (
                                                variables.map((variable) => (
                                                    <tr
                                                        key={variable.id}
                                                        className="border-b last:border-0"
                                                    >
                                                        <td className="px-4 py-2 font-mono text-xs">
                                                            {variable.key}
                                                        </td>
                                                        <td className="px-4 py-2">
                                                            {variable.value}
                                                        </td>
                                                        <td className="px-4 py-2 text-right">
                                                            <div className="flex justify-end gap-1">
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        startEditVariable(
                                                                            variable,
                                                                        )
                                                                    }
                                                                >
                                                                    <Pencil className="h-4 w-4" />
                                                                </Button>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        deleteVariable(
                                                                            variable,
                                                                        )
                                                                    }
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))
                                            ) : (
                                                <tr>
                                                    <td
                                                        className="px-4 py-6 text-muted-foreground"
                                                        colSpan={3}
                                                    >
                                                        No environment variables
                                                        yet.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Secrets</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <form
                                    onSubmit={submitSecret}
                                    className="grid gap-4 md:grid-cols-[1fr,1fr,auto]"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="secret-key">Key</Label>
                                        <Input
                                            id="secret-key"
                                            value={secretForm.data.key}
                                            onChange={(event) =>
                                                secretForm.setData(
                                                    'key',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={secretForm.errors.key}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="secret-value">
                                            Value
                                        </Label>
                                        <Input
                                            id="secret-value"
                                            value={secretForm.data.value}
                                            onChange={(event) =>
                                                secretForm.setData(
                                                    'value',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder={
                                                editingSecretId
                                                    ? 'Enter a new secret value'
                                                    : ''
                                            }
                                        />
                                        <InputError
                                            message={secretForm.errors.value}
                                        />
                                    </div>
                                    <div className="flex items-end gap-2">
                                        <Button
                                            disabled={secretForm.processing}
                                        >
                                            {editingSecretId ? 'Update' : 'Add'}
                                        </Button>
                                        {editingSecretId && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() => {
                                                    setEditingSecretId(null);
                                                    secretForm.reset();
                                                }}
                                            >
                                                Cancel
                                            </Button>
                                        )}
                                    </div>
                                </form>

                                <div className="rounded-lg border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Key
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Version
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Reveal
                                                </th>
                                                <th className="px-4 py-2 text-right font-medium">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {secrets.length > 0 ? (
                                                secrets.map((secret) => (
                                                    <tr
                                                        key={secret.id}
                                                        className="border-b last:border-0"
                                                    >
                                                        <td className="px-4 py-2 font-mono text-xs">
                                                            {secret.key}
                                                        </td>
                                                        <td className="px-4 py-2">
                                                            v{secret.version}
                                                        </td>
                                                        <td className="px-4 py-2 font-mono text-xs text-muted-foreground">
                                                            {revealedValues[
                                                                secret.id
                                                            ] ?? '-'}
                                                        </td>
                                                        <td className="px-4 py-2 text-right">
                                                            <div className="flex justify-end gap-1">
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    disabled={
                                                                        revealingId ===
                                                                        secret.id
                                                                    }
                                                                    onClick={() =>
                                                                        void handleRevealSecret(
                                                                            secret,
                                                                        )
                                                                    }
                                                                >
                                                                    <Eye className="h-4 w-4" />
                                                                </Button>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        startEditSecret(
                                                                            secret,
                                                                        )
                                                                    }
                                                                >
                                                                    <Pencil className="h-4 w-4" />
                                                                </Button>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        deleteSecret(
                                                                            secret,
                                                                        )
                                                                    }
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))
                                            ) : (
                                                <tr>
                                                    <td
                                                        className="px-4 py-6 text-muted-foreground"
                                                        colSpan={4}
                                                    >
                                                        No secrets yet.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Process Definitions</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <form
                                    onSubmit={submitProcess}
                                    className="grid gap-4 md:grid-cols-[180px,1fr,120px,auto]"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="process-type">
                                            Type
                                        </Label>
                                        <Select
                                            value={processForm.data.type}
                                            onValueChange={(value) =>
                                                processForm.setData(
                                                    'type',
                                                    value as
                                                        | 'web'
                                                        | 'worker'
                                                        | 'scheduler'
                                                        | 'custom',
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="web">
                                                    Web
                                                </SelectItem>
                                                <SelectItem value="worker">
                                                    Worker
                                                </SelectItem>
                                                <SelectItem value="scheduler">
                                                    Scheduler
                                                </SelectItem>
                                                <SelectItem value="custom">
                                                    Custom
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={processForm.errors.type}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="process-command">
                                            Command
                                        </Label>
                                        <Input
                                            id="process-command"
                                            value={processForm.data.command}
                                            onChange={(event) =>
                                                processForm.setData(
                                                    'command',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={processForm.errors.command}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="process-instances">
                                            Instances
                                        </Label>
                                        <Input
                                            id="process-instances"
                                            type="number"
                                            min={1}
                                            value={processForm.data.instances}
                                            onChange={(event) =>
                                                processForm.setData(
                                                    'instances',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                processForm.errors.instances
                                            }
                                        />
                                    </div>
                                    <div className="flex items-end gap-2">
                                        <Button
                                            disabled={processForm.processing}
                                        >
                                            {editingProcessId
                                                ? 'Update'
                                                : 'Add'}
                                        </Button>
                                        {editingProcessId && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() => {
                                                    setEditingProcessId(null);
                                                    processForm.reset();
                                                    processForm.setData(
                                                        'type',
                                                        'web',
                                                    );
                                                    processForm.setData(
                                                        'instances',
                                                        '1',
                                                    );
                                                }}
                                            >
                                                Cancel
                                            </Button>
                                        )}
                                    </div>
                                </form>

                                <div className="rounded-lg border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Type
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Command
                                                </th>
                                                <th className="px-4 py-2 text-left font-medium">
                                                    Instances
                                                </th>
                                                <th className="px-4 py-2 text-right font-medium">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {processes.length > 0 ? (
                                                processes.map(
                                                    (processDefinition) => (
                                                        <tr
                                                            key={
                                                                processDefinition.id
                                                            }
                                                            className="border-b last:border-0"
                                                        >
                                                            <td className="px-4 py-2">
                                                                {
                                                                    processDefinition.type
                                                                }
                                                            </td>
                                                            <td className="px-4 py-2 font-mono text-xs">
                                                                {
                                                                    processDefinition.command
                                                                }
                                                            </td>
                                                            <td className="px-4 py-2">
                                                                {
                                                                    processDefinition.instances
                                                                }
                                                            </td>
                                                            <td className="px-4 py-2 text-right">
                                                                <div className="flex justify-end gap-1">
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() =>
                                                                            startEditProcess(
                                                                                processDefinition,
                                                                            )
                                                                        }
                                                                    >
                                                                        <Pencil className="h-4 w-4" />
                                                                    </Button>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() =>
                                                                            deleteProcess(
                                                                                processDefinition,
                                                                            )
                                                                        }
                                                                    >
                                                                        <Trash2 className="h-4 w-4" />
                                                                    </Button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    ),
                                                )
                                            ) : (
                                                <tr>
                                                    <td
                                                        className="px-4 py-6 text-muted-foreground"
                                                        colSpan={4}
                                                    >
                                                        No process definitions
                                                        yet.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

EnvironmentShow.layout = {
    breadcrumbs: [
        {
            title: 'Projects',
            href: '/projects',
        },
        {
            title: 'Environment',
            href: '/projects',
        },
    ],
};
