import { type LucideIcon } from 'lucide-react';
import { StatusBadge } from './status-badge';

interface MetricCardProps {
    title: string;
    value: string;
    status?: string;
    icon: LucideIcon;
}

export function MetricCard({ title, value, status, icon: Icon }: MetricCardProps) {
    return (
        <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs">
            <div className="flex items-center justify-between gap-3">
                <div className="flex size-10 items-center justify-center rounded-md bg-primary/10 text-primary">
                    <Icon className="size-5" />
                </div>
                {status && <StatusBadge value={status} />}
            </div>
            <p className="mt-4 text-sm text-muted-foreground">{title}</p>
            <p className="mt-1 text-xl font-semibold tracking-normal">{value}</p>
        </section>
    );
}
