import { Monitor, Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';

const OPTIONS: { value: Appearance; icon: typeof Sun; label: string }[] = [
    { value: 'light', icon: Sun, label: 'Claro' },
    { value: 'dark', icon: Moon, label: 'Oscuro' },
    { value: 'system', icon: Monitor, label: 'Según el sistema' },
];

/**
 * Switches the theme from wherever the user happens to be.
 *
 * The settings screen keeps its own control, but reaching it took four clicks
 * for something people change on a whim, so it also lives in the header.
 */
export function AppearanceToggle() {
    const { appearance, resolvedAppearance, updateAppearance } =
        useAppearance();

    // The icon shows what is on screen, not the setting: under "system" the
    // moon is what tells the user they are looking at the dark theme.
    const CurrentIcon = resolvedAppearance === 'dark' ? Moon : Sun;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Cambiar entre modo claro y oscuro"
                >
                    <CurrentIcon className="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {OPTIONS.map(({ value, icon: Icon, label }) => (
                    <DropdownMenuItem
                        key={value}
                        className="cursor-pointer"
                        onSelect={() => updateAppearance(value)}
                    >
                        <Icon className="mr-2 h-4 w-4" />
                        {label}
                        {appearance === value && (
                            <span className="ml-auto pl-3 text-xs text-muted-foreground">
                                ✓
                            </span>
                        )}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
