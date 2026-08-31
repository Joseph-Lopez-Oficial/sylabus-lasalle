import { usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';
import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    // The name comes from the application's configuration, so there is a single
    // place to change it rather than a literal repeated across the layouts.
    const { name } = usePage<SharedData>().props;

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center">
                <AppLogoIcon className="size-8" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {name}
                </span>
                <span className="truncate text-xs leading-tight text-muted-foreground">
                    Universidad de La Salle
                </span>
            </div>
        </>
    );
}
