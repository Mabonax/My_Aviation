import { Button } from '@/components/ui/button';
import { type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';

interface EmptyStateProps {
    icon: LucideIcon;
    title: string;
    description: string;
    action?: ReactNode;
}

export function EmptyState({ icon: Icon, title, description, action }: EmptyStateProps) {
    return (
        <div className="flex min-h-64 flex-col items-center justify-center gap-4 px-4 text-center">
            <div className="flex size-12 items-center justify-center rounded-lg border bg-muted text-muted-foreground">
                <Icon className="size-6" />
            </div>
            <div>
                <p className="font-medium">{title}</p>
                <p className="mt-1 max-w-md text-sm leading-6 text-muted-foreground">{description}</p>
            </div>
            {action && <Button asChild>{action}</Button>}
        </div>
    );
}
