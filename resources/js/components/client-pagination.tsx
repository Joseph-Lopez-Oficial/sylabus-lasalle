import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { PAGE_SIZES } from '@/components/pagination-controls';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

/**
 * Pages a list the page already holds.
 *
 * Unlike the listings, these rows arrive with the screen: the enrolled students
 * sit beside a grading grid that may hold unsaved marks, so asking the server
 * for a page would reload the screen and take those marks with it.
 */
export function useClientPagination<T>(items: T[], initialSize = 15) {
    const [requestedPage, setPage] = useState(1);
    const [size, setSize] = useState(initialSize);

    const lastPage = Math.max(1, Math.ceil(items.length / size));

    // Clamped as it is read rather than corrected afterwards: a list that
    // shrinks below the current page would otherwise render empty for a frame.
    const page = Math.min(requestedPage, lastPage);

    const visible = useMemo(
        () => items.slice((page - 1) * size, page * size),
        [items, page, size],
    );

    return {
        visible,
        page,
        lastPage,
        size,
        total: items.length,
        from: items.length === 0 ? 0 : (page - 1) * size + 1,
        to: Math.min(page * size, items.length),
        setPage,
        setSize: (next: number) => {
            setSize(next);
            setPage(1);
        },
    };
}

type Props = {
    state: ReturnType<typeof useClientPagination<unknown>>;
};

/** The control for a list paged in the browser. */
export function ClientPagination({ state }: Props) {
    const { page, lastPage, size, total, from, to, setPage, setSize } = state;

    // A group that fits on one page has nothing to steer.
    if (total <= PAGE_SIZES[0]) {
        return null;
    }

    return (
        <div className="flex flex-col gap-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center gap-3">
                <span>
                    {from}–{to} de {total} registros
                </span>

                <Select
                    value={String(size)}
                    onValueChange={(value) => setSize(Number(value))}
                >
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
                    onClick={() => setPage(1)}
                    disabled={page === 1}
                    aria-label="Primera página"
                >
                    <ChevronsLeft className="h-4 w-4" />
                </Button>
                <Button
                    variant="outline"
                    size="icon"
                    onClick={() => setPage(page - 1)}
                    disabled={page === 1}
                    aria-label="Página anterior"
                >
                    <ChevronLeft className="h-4 w-4" />
                </Button>

                <span className="px-2">
                    Página {page} de {lastPage}
                </span>

                <Button
                    variant="outline"
                    size="icon"
                    onClick={() => setPage(page + 1)}
                    disabled={page === lastPage}
                    aria-label="Página siguiente"
                >
                    <ChevronRight className="h-4 w-4" />
                </Button>
                <Button
                    variant="outline"
                    size="icon"
                    onClick={() => setPage(lastPage)}
                    disabled={page === lastPage}
                    aria-label="Última página"
                >
                    <ChevronsRight className="h-4 w-4" />
                </Button>
            </div>
        </div>
    );
}
