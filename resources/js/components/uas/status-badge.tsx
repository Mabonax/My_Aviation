import { cn } from '@/lib/utils';

const statusClasses: Record<string, string> = {
    active: 'border-success/30 bg-success/10 text-success-foreground',
    valid: 'border-success/30 bg-success/10 text-success-foreground',
    draft: 'border-info/30 bg-info/10 text-info-foreground',
    unverified: 'border-warning/30 bg-warning/10 text-warning-foreground',
    inactive: 'border-muted bg-muted text-muted-foreground',
    suspended: 'border-destructive/30 bg-destructive/10 text-destructive',
    expired: 'border-destructive/30 bg-destructive/10 text-destructive',
    blocked: 'border-destructive/30 bg-destructive/10 text-destructive',
    'in progress': 'border-info/30 bg-info/10 text-info-foreground',
    'not started': 'border-muted bg-muted text-muted-foreground',
};

export function StatusBadge({ value, className }: { value: string; className?: string }) {
    const key = value.toLowerCase();

    return <span className={cn('inline-flex items-center rounded-md border px-2 py-1 text-xs font-medium capitalize', statusClasses[key] ?? statusClasses.draft, className)}>{value.replace('_', ' ')}</span>;
}
