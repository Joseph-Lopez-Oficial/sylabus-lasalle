import type { LucideIcon } from 'lucide-react';
import { Inbox } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Props = {
    title?: string;
    description?: string;
    icon?: LucideIcon;
    children?: ReactNode;
    className?: string;
};

export function EmptyState({
    title = 'Sin resultados',
    description = 'No hay registros para mostrar.',
    icon: Icon = Inbox,
    children,
    className,
}: Props) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed p-12 text-center',
                className,
            )}
        >
            <div className="rounded-full bg-muted p-4">
                <Icon className="h-8 w-8 text-muted-foreground" />
            </div>
            <div>
                <p className="font-medium">{title}</p>
                <p className="mt-1 text-sm text-muted-foreground">{description}</p>
            </div>
            {children && <div className="mt-2">{children}</div>}
        </div>
    );
}
