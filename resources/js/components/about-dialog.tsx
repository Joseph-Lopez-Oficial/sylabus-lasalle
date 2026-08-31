import { usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { SharedData } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

/** What the system is, who made it, and which version is running. */
export function AboutDialog({ open, onOpenChange }: Props) {
    const { name, version } = usePage<SharedData>().props;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <div className="mb-2 flex items-center gap-3">
                        <AppLogoIcon className="size-11" />
                        <div>
                            <DialogTitle>{name}</DialogTitle>
                            <p className="text-xs text-muted-foreground">
                                Universidad de La Salle
                            </p>
                        </div>
                    </div>
                    <DialogDescription className="text-left">
                        Sistema de gestión del diseño pedagógico y medición de
                        resultados de aprendizaje.
                    </DialogDescription>
                </DialogHeader>

                <dl className="space-y-3 text-sm">
                    <div className="flex justify-between gap-4">
                        <dt className="text-muted-foreground">Versión</dt>
                        <dd className="font-mono">{version}</dd>
                    </div>
                    <div className="flex flex-col gap-1 border-t pt-3">
                        <dt className="text-muted-foreground">Desarrollo</dt>
                        <dd>
                            Joseph López
                            <span className="block text-xs text-muted-foreground">
                                Desarrollador full stack, estudiante de la
                                Universidad de La Salle
                            </span>
                        </dd>
                    </div>
                </dl>
            </DialogContent>
        </Dialog>
    );
}
