import { router } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { PaginatedResponse } from '@/types/models';

/** The sizes the server accepts; mirrors the trait that validates them. */
export const PAGE_SIZES = [15, 25, 50, 100] as const;

/** Where the chosen size is kept, so a listing does not forget it. */
const SIZE_KEY = 'listing-per-page';

/** The size the user last chose, or the default when they have not. */
export function storedPageSize(): number | undefined {
    try {
        const stored = Number(sessionStorage.getItem(SIZE_KEY));

        return PAGE_SIZES.includes(stored as (typeof PAGE_SIZES)[number])
            ? stored
            : undefined;
    } catch {
        // Private browsing can refuse storage; the default is fine.
        return undefined;
    }
}

type Props = {
    /** The paginator as the server sent it. */
    data: PaginatedResponse<unknown>;
    /** Search and filters, so changing page does not drop them. */
    filters?: Record<string, string | number | undefined>;
};

/**
 * Page numbers around the current one, with gaps marked as ellipsis.
 *
 * With forty-nine pages of students, printing them all is unusable, so only the
 * neighbours plus the two ends are offered.
 */
function pageWindow(current: number, last: number): (number | 'gap')[] {
    if (last <= 7) {
        return Array.from({ length: last }, (_, index) => index + 1);
    }

    const pages: (number | 'gap')[] = [1];

    const from = Math.max(2, current - 1);
    const to = Math.min(last - 1, current + 1);

    if (from > 2) {
        pages.push('gap');
    }

    for (let page = from; page <= to; page++) {
        pages.push(page);
    }

    if (to < last - 1) {
        pages.push('gap');
    }

    pages.push(last);

    return pages;
}

/**
 * The control every paginated listing shares.
 *
 * The page travels in the address, so reloading or sharing the link lands on
 * the same view instead of back at the first page.
 */
export function PaginationControls({ data, filters = {} }: Props) {
    if (!data || !data.total) {
        return null;
    }

    const { current_page: current, last_page: last, per_page: size } = data;

    function go(page: number, perPage?: number) {
        router.get(
            window.location.pathname,
            {
                ...filters,
                page: page > 1 ? page : undefined,
                per_page: perPage ?? filters.per_page ?? undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    }

    function changeSize(value: string) {
        const next = Number(value);

        try {
            sessionStorage.setItem(SIZE_KEY, String(next));
        } catch {
            // Not being able to remember it is no reason to refuse the change.
        }

        // Back to the first page: the old offset means nothing at a new size.
        go(1, next);
    }

    return (
        <div className="flex flex-col gap-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center gap-3">
                <span>
                    {data.from ?? 0}–{data.to ?? 0} de {data.total} registros
                </span>

                <Select value={String(size)} onValueChange={changeSize}>
                    <SelectTrigger className="h-8 w-[130px]">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {PAGE_SIZES.map((option) => (
                            <SelectItem key={option} value={String(option)}>
                                {option} por página
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="flex items-center gap-1">
                <Button
                    variant="outline"
                    size="icon"
                    onClick={() => go(1)}
                    disabled={current === 1}
                    aria-label="Primera página"
                >
                    <ChevronsLeft className="h-4 w-4" />
                </Button>
                <Button
                    variant="outline"
                    size="icon"
                    onClick={() => go(current - 1)}
                    disabled={current === 1}
                    aria-label="Página anterior"
                >
                    <ChevronLeft className="h-4 w-4" />
                </Button>

                {pageWindow(current, last).map((page, index) =>
                    page === 'gap' ? (
                        <span
                            key={`gap-${index}`}
                            className="px-1"
                            aria-hidden="true"
                        >
                            …
                        </span>
                    ) : (
                        <Button
                            key={page}
                            variant={page === current ? 'default' : 'outline'}
                            size="icon"
                            onClick={() => go(page)}
                            aria-label={`Página ${page}`}
                            aria-current={page === current ? 'page' : undefined}
                        >
                            {page}
                        </Button>
                    ),
                )}

                <Button
                    variant="outline"
                    size="icon"
                    onClick={() => go(current + 1)}
                    disabled={current === last}
                    aria-label="Página siguiente"
                >
                    <ChevronRight className="h-4 w-4" />
                </Button>
                <Button
                    variant="outline"
                    size="icon"
                    onClick={() => go(last)}
                    disabled={current === last}
                    aria-label="Última página"
                >
                    <ChevronsRight className="h-4 w-4" />
                </Button>
            </div>
        </div>
    );
}
