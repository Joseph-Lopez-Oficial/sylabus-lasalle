import { router } from '@inertiajs/react';
import {
    type ColumnDef,
    flexRender,
    getCoreRowModel,
    useReactTable,
} from '@tanstack/react-table';
import { ChevronLeft, ChevronRight, Search } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import type { PaginatedResponse } from '@/types';
import { EmptyState } from './empty-state';

type Props<TData> = {
    data: PaginatedResponse<TData>;
    columns: ColumnDef<TData, unknown>[];
    filters?: Record<string, string>;
    searchPlaceholder?: string;
    searchKey?: string;
    className?: string;
};

export function DataTable<TData>({
    data,
    columns,
    filters = {},
    searchPlaceholder = 'Buscar...',
    searchKey = 'search',
    className,
}: Props<TData>) {
    const [search, setSearch] = useState(filters[searchKey] ?? '');

    // Debounced search navigation
    useEffect(() => {
        const timeout = setTimeout(() => {
            if (search !== (filters[searchKey] ?? '')) {
                router.get(
                    window.location.pathname,
                    { ...filters, [searchKey]: search || undefined, page: undefined },
                    { preserveState: true, replace: true },
                );
            }
        }, 400);

        return () => clearTimeout(timeout);
    }, [search, filters, searchKey]);

    const table = useReactTable({
        data: data.data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        manualPagination: true,
        pageCount: data.meta.last_page,
    });

    const navigate = useCallback(
        (url: string | null) => {
            if (!url) return;
            router.get(url, {}, { preserveState: true });
        },
        [],
    );

    return (
        <div className={cn('space-y-4', className)}>
            {/* Search */}
            <div className="relative max-w-sm">
                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    placeholder={searchPlaceholder}
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="pl-9"
                />
            </div>

            {/* Table */}
            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => (
                                    <TableHead key={header.id}>
                                        {header.isPlaceholder
                                            ? null
                                            : flexRender(header.column.columnDef.header, header.getContext())}
                                    </TableHead>
                                ))}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {table.getRowModel().rows.length ? (
                            table.getRowModel().rows.map((row) => (
                                <TableRow key={row.id}>
                                    {row.getVisibleCells().map((cell) => (
                                        <TableCell key={cell.id}>
                                            {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell colSpan={columns.length} className="h-48 p-0">
                                    <EmptyState />
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            {/* Pagination */}
            <div className="flex items-center justify-between text-sm text-muted-foreground">
                <span>
                    {data.meta.from ?? 0}–{data.meta.to ?? 0} de {data.meta.total} registros
                </span>
                <div className="flex items-center gap-1">
                    <Button
                        variant="outline"
                        size="icon"
                        onClick={() => navigate(data.links.prev)}
                        disabled={!data.links.prev}
                        aria-label="Página anterior"
                    >
                        <ChevronLeft className="h-4 w-4" />
                    </Button>
                    <span className="px-2">
                        {data.meta.current_page} / {data.meta.last_page}
                    </span>
                    <Button
                        variant="outline"
                        size="icon"
                        onClick={() => navigate(data.links.next)}
                        disabled={!data.links.next}
                        aria-label="Página siguiente"
                    >
                        <ChevronRight className="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>
    );
}
