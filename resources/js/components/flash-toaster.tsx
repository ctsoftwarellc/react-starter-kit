import { usePage } from '@inertiajs/react';
import { CheckCircle2, CircleAlert, X } from 'lucide-react';
import { useEffect, useState } from 'react';

type ToastTone = 'success' | 'error';

type ToastMessage = {
    id: number;
    tone: ToastTone;
    message: string;
};

type FlashProps = {
    flash?: {
        success?: string;
        error?: string;
    };
};

export function FlashToaster() {
    const { flash } = usePage<FlashProps>().props;
    const [messages, setMessages] = useState<ToastMessage[]>([]);

    useEffect(() => {
        const next: ToastMessage[] = [];

        if (flash?.success) {
            next.push({
                id: Date.now(),
                tone: 'success',
                message: flash.success,
            });
        }

        if (flash?.error) {
            next.push({
                id: Date.now() + 1,
                tone: 'error',
                message: flash.error,
            });
        }

        if (next.length === 0) {
            return;
        }

        setMessages((current) => [...current, ...next]);

        const timers = next.map((toast) =>
            window.setTimeout(() => {
                setMessages((current) =>
                    current.filter((message) => message.id !== toast.id),
                );
            }, 4500),
        );

        return () => {
            timers.forEach((timer) => window.clearTimeout(timer));
        };
    }, [flash?.error, flash?.success]);

    if (messages.length === 0) {
        return null;
    }

    return (
        <div className="pointer-events-none fixed top-4 right-4 z-50 flex w-full max-w-sm flex-col gap-3">
            {messages.map((toast) => {
                const isSuccess = toast.tone === 'success';

                return (
                    <div
                        key={toast.id}
                        className={[
                            'pointer-events-auto flex items-start gap-3 rounded-xl border px-4 py-3 shadow-lg backdrop-blur-sm',
                            isSuccess
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-900'
                                : 'border-rose-200 bg-rose-50 text-rose-900',
                        ].join(' ')}
                    >
                        {isSuccess ? (
                            <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0" />
                        ) : (
                            <CircleAlert className="mt-0.5 h-4 w-4 shrink-0" />
                        )}
                        <p className="flex-1 text-sm font-medium">
                            {toast.message}
                        </p>
                        <button
                            type="button"
                            className="rounded-sm opacity-70 transition hover:opacity-100"
                            onClick={() =>
                                setMessages((current) =>
                                    current.filter(
                                        (message) => message.id !== toast.id,
                                    ),
                                )
                            }
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>
                );
            })}
        </div>
    );
}
